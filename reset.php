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
 * Landing page of the e-mailed link: lets the user choose a new password.
 *
 * Core's /login/forgot_password.php cannot be used for this when $CFG->forgottenpasswordurl
 * points to this plugin, because core redirects every request (including ?token=...) to that URL.
 * This page mirrors core's token handling and then delegates to core_login_process_password_set(),
 * which validates the token (expiry, single use), applies the password policy and logs the user in.
 *
 * Links already e-mailed keep working even if the plugin is disabled afterwards.
 *
 * @package    local_custom_password_recovery
 * @copyright  2026 CEAD/UnB
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/login/lib.php');
require_once($CFG->dirroot . '/login/set_password_form.php');

$token = optional_param('token', '', PARAM_ALPHANUM);
$pageurl = new moodle_url('/local/custom_password_recovery/reset.php');

$PAGE->set_url($pageurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('passwordforgotten'));
$PAGE->set_heading(format_string($SITE->fullname));

if (isloggedin() && !isguestuser()) {
    redirect(new moodle_url('/'), get_string('loginalready'), 5);
}

// Same pattern as core: the token from the e-mail is moved into the session for exactly one
// request and the browser is redirected, so the token does not stay in the address bar/history.
$sessionkey = 'local_custom_password_recovery_token';
$tokeninsession = false;
if (!empty($SESSION->$sessionkey)) {
    $token = $SESSION->$sessionkey;
    unset($SESSION->$sessionkey);
    $tokeninsession = true;
}

if ($token === '') {
    redirect(new moodle_url('/local/custom_password_recovery/index.php'));
}

if (!$tokeninsession && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $SESSION->$sessionkey = $token;
    redirect($pageurl);
}

// Shows the "set password" form (GET) or processes it (POST, token sent as a hidden field).
core_login_process_password_set($token);
