<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_custom_password_recovery;

use context_system;
use core_text;
use core_user;
use moodle_url;
use stdClass;

/**
 * Password recovery business logic.
 *
 * The plugin never changes the user's password. It only creates a reset token
 * in core's {user_password_resets} table and e-mails a link to reset.php?token=...,
 * which delegates to core_login_process_password_set(): it validates expiry
 * ($CFG->pwresettime), lets the user choose a new password and invalidates the token.
 *
 * @package    local_custom_password_recovery
 * @copyright  2026 CEAD/UnB
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /** @var string Component name. */
    const COMPONENT = 'local_custom_password_recovery';

    /** @var string Link sent successfully. */
    const STATUS_SENT = 'sent';
    /** @var string No matching account (or invalid identifier). */
    const STATUS_NOTFOUND = 'notfound';
    /** @var string Account exists but cannot reset its password here. */
    const STATUS_CANNOTRESET = 'cannotreset';
    /** @var string Account has no valid e-mail address. */
    const STATUS_NOEMAIL = 'noemail';
    /** @var string Too many requests. */
    const STATUS_RATELIMITED = 'ratelimited';
    /** @var string E-mail delivery failed. */
    const STATUS_SENDFAILED = 'sendfailed';

    /** @var string Identifier mode: any username (formatted CPF is normalised). */
    const MODE_USERNAME = 'username';
    /** @var string Identifier mode: only valid CPFs are accepted. */
    const MODE_CPF = 'cpf';

    /**
     * Processes a password recovery request.
     *
     * @param string $rawidentifier What the visitor typed (CPF / username).
     * @param string $ip Remote IP address, used for rate limiting.
     * @return array ['status' => string, 'emails' => string[] (already masked if configured), 'minutes' => int]
     */
    public static function process_request(string $rawidentifier, string $ip): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/login/lib.php');

        $config = get_config(self::COMPONENT);
        $window = (int)($config->ratewindow ?? HOURSECS);

        // 1) Per-IP limit: counts every request, including unknown identifiers (slows enumeration).
        if (!rate_limiter::hit('ip_' . sha1($ip), (int)($config->maxperip ?? 10), $window)) {
            return self::result(self::STATUS_RATELIMITED);
        }

        // 2) Identify the account.
        $username = self::normalise_identifier($rawidentifier, $config->identifiermode ?? self::MODE_USERNAME);
        if ($username === null) {
            return self::result(self::STATUS_NOTFOUND);
        }
        $user = $DB->get_record('user', [
            'username' => $username,
            'mnethostid' => $CFG->mnet_localhost_id,
            'deleted' => 0,
        ]);
        if (!$user || isguestuser($user)) {
            return self::result(self::STATUS_NOTFOUND);
        }
        if (!self::can_reset_password($user)) {
            return self::result(self::STATUS_CANNOTRESET);
        }

        // 3) Recipients: primary e-mail + secondary e-mail from the configured profile field.
        $emails = self::get_recipient_emails($user, (string)($config->secondaryemailfield ?? ''));
        if (empty($emails)) {
            return self::result(self::STATUS_NOEMAIL);
        }

        // 4) Per-account limit (avoids flooding a user's mailbox).
        if (!rate_limiter::hit('user_' . $user->id, (int)($config->maxperaccount ?? 3), $window)) {
            return self::result(self::STATUS_RATELIMITED);
        }

        // 5) Only the newest link is valid: drop older tokens and create a new one.
        $DB->delete_records('user_password_resets', ['userid' => $user->id]);
        $reset = core_login_generate_password_reset($user);

        // 6) Send the link.
        $sent = self::send_reset_emails($user, $reset, $emails);
        if (empty($sent)) {
            $DB->delete_records('user_password_resets', ['id' => $reset->id]);
            return self::result(self::STATUS_SENDFAILED);
        }

        event\recovery_link_sent::create([
            'context' => context_system::instance(),
            'objectid' => $reset->id,
            'relateduserid' => $user->id,
            'other' => ['emailcount' => count($sent)],
        ])->trigger();

        $mask = !isset($config->maskemails) || !empty($config->maskemails);
        $display = array_map(function($e) use ($mask) {
            return $mask ? self::mask_email($e) : $e;
        }, $sent);

        return self::result(self::STATUS_SENT, $display);
    }

    /**
     * Builds a result array.
     *
     * @param string $status
     * @param string[] $emails
     * @return array
     */
    protected static function result(string $status, array $emails = []): array {
        return [
            'status' => $status,
            'emails' => $emails,
            'minutes' => self::get_link_lifetime_minutes(),
        ];
    }

    /**
     * Link lifetime in minutes, from core's $CFG->pwresettime.
     *
     * @return int
     */
    public static function get_link_lifetime_minutes(): int {
        global $CFG;
        $seconds = isset($CFG->pwresettime) ? (int)$CFG->pwresettime : 1800;
        return max(1, (int)floor($seconds / 60));
    }

    /**
     * Converts what the visitor typed into a username to look up.
     *
     * @param string $raw
     * @param string $mode One of the MODE_* constants.
     * @return string|null Normalised username, or null if the input is not acceptable.
     */
    public static function normalise_identifier(string $raw, string $mode): ?string {
        $raw = trim($raw);
        if ($raw === '' || core_text::strlen($raw) > 100) {
            return null;
        }

        // Looks like a (possibly formatted) number, e.g. "123.456.789-09": keep digits only.
        $numeric = (bool)preg_match('/^[\d.\-\s\/]+$/', $raw);
        if ($numeric) {
            $raw = preg_replace('/\D+/', '', $raw);
        }

        if ($mode === self::MODE_CPF) {
            return ($numeric && self::is_valid_cpf($raw)) ? $raw : null;
        }

        $username = core_text::strtolower($raw);
        return $username === '' ? null : $username;
    }

    /**
     * Validates a CPF (11 digits + check digits).
     *
     * @param string $cpf Digits only.
     * @return bool
     */
    public static function is_valid_cpf(string $cpf): bool {
        if (!preg_match('/^\d{11}$/', $cpf) || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int)$cpf[$i] * (($t + 1) - $i);
            }
            $digit = ((10 * $sum) % 11) % 10;
            if ((int)$cpf[$t] !== $digit) {
                return false;
            }
        }
        return true;
    }

    /**
     * Same checks core performs before allowing a password reset.
     *
     * @param stdClass $user
     * @return bool
     */
    public static function can_reset_password(stdClass $user): bool {
        if (empty($user->confirmed) || !empty($user->suspended) || $user->auth === 'nologin') {
            return false;
        }
        if (!is_enabled_auth($user->auth)) {
            return false;
        }
        $auth = get_auth_plugin($user->auth);
        if (!$auth->can_reset_password()) {
            return false;
        }
        return has_capability('moodle/user:changeownpassword', context_system::instance(), $user->id);
    }

    /**
     * Returns the valid, de-duplicated e-mail addresses of the user.
     *
     * @param stdClass $user
     * @param string $fieldshortname Short name of the custom profile field holding the secondary e-mail ('' = none).
     * @return string[]
     */
    public static function get_recipient_emails(stdClass $user, string $fieldshortname): array {
        global $DB;

        $candidates = [(string)$user->email];
        if ($fieldshortname !== '') {
            $value = $DB->get_field_sql(
                "SELECT d.data
                   FROM {user_info_data} d
                   JOIN {user_info_field} f ON f.id = d.fieldid
                  WHERE f.shortname = :shortname AND d.userid = :userid",
                ['shortname' => $fieldshortname, 'userid' => $user->id]
            );
            if ($value !== false) {
                $candidates[] = (string)$value;
            }
        }

        $emails = [];
        $seen = [];
        foreach ($candidates as $email) {
            $email = trim($email);
            if ($email === '' || !validate_email($email)) {
                continue;
            }
            $key = core_text::strtolower($email);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $emails[] = $email;
        }
        return $emails;
    }

    /**
     * Sends the reset link to each address.
     *
     * @param stdClass $user
     * @param stdClass $reset Record from {user_password_resets}.
     * @param string[] $emails
     * @return string[] Addresses that were accepted for delivery.
     */
    protected static function send_reset_emails(stdClass $user, stdClass $reset, array $emails): array {
        global $CFG;

        $lang = !empty($user->lang) ? $user->lang : $CFG->lang;
        $strman = get_string_manager();
        $link = (new moodle_url('/local/custom_password_recovery/reset.php', ['token' => $reset->token]))->out(false);

        $a = new stdClass();
        $a->fullname = fullname($user);
        $a->username = $user->username;
        $a->sitename = format_string(get_site()->fullname, true, ['context' => context_system::instance()]);
        $a->minutes = self::get_link_lifetime_minutes();
        $a->signoff = generate_email_signoff();
        $a->link = $link;
        $subject = $strman->get_string('emailsubject', self::COMPONENT, $a, $lang);
        $text = $strman->get_string('emailbodytext', self::COMPONENT, $a, $lang);

        $ahtml = clone $a;
        foreach (['fullname', 'username', 'sitename', 'link'] as $field) {
            $ahtml->$field = s($ahtml->$field);
        }
        $ahtml->signoff = nl2br(s($a->signoff));
        $html = $strman->get_string('emailbodyhtml', self::COMPONENT, $ahtml, $lang);

        $from = core_user::get_support_user();
        $sent = [];
        foreach ($emails as $email) {
            $recipient = clone $user;
            $recipient->email = $email;
            $recipient->mailformat = 1;
            if (email_to_user($recipient, $from, $subject, $text, $html)) {
                $sent[] = $email;
            }
        }
        return $sent;
    }

    /**
     * Masks the local part of an e-mail address, e.g. "joaosilva@unb.br" => "jo*****@unb.br".
     *
     * @param string $email
     * @return string
     */
    public static function mask_email(string $email): string {
        $email = trim($email);
        $at = strrpos($email, '@');
        if ($at === false) {
            return str_repeat('*', 5);
        }
        $local = substr($email, 0, $at);
        $domain = substr($email, $at + 1);
        $keep = min(2, max(1, core_text::strlen($local) - 1));
        return core_text::substr($local, 0, $keep) . '*****@' . $domain;
    }

    /**
     * Whether reCAPTCHA should be shown on the request form.
     *
     * @return bool
     */
    public static function captcha_enabled(): bool {
        global $CFG;
        return !empty(get_config(self::COMPONENT, 'userecaptcha'))
            && !empty($CFG->recaptchapublickey)
            && !empty($CFG->recaptchaprivatekey);
    }
}
