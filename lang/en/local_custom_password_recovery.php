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

/**
 * English strings.
 *
 * @package    local_custom_password_recovery
 * @copyright  2026 CEAD/UnB
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Custom password recovery';
$string['privacy:metadata'] = 'The plugin does not store personal data. Reset tokens are stored in the core password reset table and rate-limit counters are temporary cache entries.';
$string['cachedef_ratelimit'] = 'Password recovery request counters (rate limiting)';
$string['eventrecoverylinksent'] = 'Password recovery link sent';

// Page.
$string['pagetitle'] = 'Password recovery';
$string['formintro'] = 'Enter your CPF (username). We will send a link to set a new password to the e-mail addresses registered in your account.';
$string['identifier'] = 'CPF (username)';
$string['sendlink'] = 'Send recovery link';
$string['backtologin'] = 'Back to login page';
$string['result_sent'] = 'A password reset link was sent to:';
$string['result_sent_details'] = 'The link is valid for {$a} minutes and can be used only once. Your current password keeps working until you set a new one. If you cannot find the message, check your spam folder.';
$string['result_notfound'] = 'We could not find an account with the information provided. Please check that you typed it correctly.';
$string['result_cannotreset'] = 'The password of this account cannot be reset through this form.';
$string['result_noemail'] = 'This account has no valid e-mail address registered, so the link could not be sent.';
$string['result_ratelimited'] = 'Too many requests in a short period. Please wait a while and try again.';
$string['result_sendfailed'] = 'The e-mail could not be sent right now. Please try again later.';
$string['disabledmessage'] = 'Password recovery is temporarily unavailable. Please contact support.';

// E-mail.
$string['emailsubject'] = '{$a->sitename}: password reset request';
$string['emailbodytext'] = 'Hi {$a->fullname},

A password reset was requested for your account \'{$a->username}\' on {$a->sitename}.

To choose a new password, open the link below (valid for {$a->minutes} minutes, single use):

{$a->link}

If you did not make this request, just ignore this message: your current password is still valid and nothing was changed.

{$a->signoff}';
$string['emailbodyhtml'] = '<p>Hi {$a->fullname},</p>
<p>A password reset was requested for your account <strong>{$a->username}</strong> on {$a->sitename}.</p>
<p>To choose a new password, click the link below (valid for {$a->minutes} minutes, single use):</p>
<p><a href="{$a->link}">{$a->link}</a></p>
<p>If you did not make this request, just ignore this message: your current password is still valid and nothing was changed.</p>
<p>{$a->signoff}</p>';

// Settings.
$string['setupinfo'] = '<div class="alert alert-info"><p><strong>Setup:</strong> to make the "Forgotten your username or password?" link on the login page open this plugin, go to <a href="{$a->authsettings}">Site administration &gt; Plugins &gt; Authentication &gt; Manage authentication</a> and set <em>Forgotten password URL</em> (forgottenpasswordurl) to:</p><p><code>{$a->pageurl}</code></p><p>With that setting, the standard page /login/forgot_password.php (and the Moodle app) also send users here, so this plugin fully replaces the standard recovery. The e-mailed link opens this plugin\'s own reset page.</p><p>The reset link lifetime comes from the core setting <em>pwresettime</em> (currently {$a->minutes} minutes).</p></div>';
$string['enabled'] = 'Enabled';
$string['enabled_desc'] = 'When disabled, the page redirects to the standard Moodle password recovery page.';
$string['secondaryemailfield'] = 'Secondary e-mail profile field';
$string['secondaryemailfield_desc'] = 'Custom user profile field holding the secondary (personal) e-mail. The link is sent to the account e-mail and to this address.';
$string['identifiermode'] = 'Accepted identifier';
$string['identifiermode_desc'] = 'How the typed value is matched against the username. In both modes CPF formatting (dots, dashes, spaces) is removed.';
$string['identifiermode_username'] = 'Any username (CPF or not)';
$string['identifiermode_cpf'] = 'Only valid CPFs (check digits are validated)';
$string['maskemails'] = 'Mask e-mail addresses on screen';
$string['maskemails_desc'] = 'Shows addresses partially hidden (e.g. jo*****@unb.br). Recommended: anyone who knows a CPF can use the form, so showing full addresses would expose personal data.';
$string['securityheading'] = 'Abuse protection';
$string['maxperip'] = 'Requests per IP';
$string['maxperip_desc'] = 'Maximum requests (successful or not) from the same IP address within the time window. 0 disables the limit.';
$string['maxperaccount'] = 'E-mails per account';
$string['maxperaccount_desc'] = 'Maximum recovery e-mails sent to the same account within the time window. 0 disables the limit.';
$string['ratewindow'] = 'Time window';
$string['ratewindow_desc'] = 'Window used by the limits above (maximum 1 day).';
$string['userecaptcha'] = 'Use reCAPTCHA';
$string['userecaptcha_desc'] = 'Adds a reCAPTCHA to the form. Requires the reCAPTCHA keys (recaptchapublickey / recaptchaprivatekey) in Site administration &gt; Plugins &gt; Authentication &gt; Manage authentication.';
$string['contactinfo'] = 'Support contact';
$string['contactinfo_desc'] = 'Text shown below error messages, e.g. "If the problem persists, contact support at support@example.com".';
