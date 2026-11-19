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
 * Public password recovery page.
 *
 * @package    local_custom_password_recovery
 * @copyright  2026 CEAD/UnB
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_custom_password_recovery\form\request_form;
use local_custom_password_recovery\manager;

$component = manager::COMPONENT;
$pageurl = new moodle_url('/local/custom_password_recovery/index.php');

$PAGE->set_url($pageurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('pagetitle', $component));
$PAGE->set_heading(format_string($SITE->fullname));

if (isloggedin() && !isguestuser()) {
    redirect(new moodle_url('/'), get_string('loginalready'), 5);
}

if (!get_config($component, 'enabled')) {
    // Fall back to core's page, unless core is configured to send people here (that would loop).
    if (empty($CFG->forgottenpasswordurl)) {
        redirect(new moodle_url('/login/forgot_password.php'));
    }
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('disabledmessage', $component), \core\output\notification::NOTIFY_WARNING);
    echo $OUTPUT->footer();
    die();
}

// Post/Redirect/Get: the result of the last request is kept in the session for one page view.
$sessionkey = 'local_custom_password_recovery_result';
$result = null;
if (!empty($SESSION->$sessionkey)) {
    $result = $SESSION->$sessionkey;
    unset($SESSION->$sessionkey);
}

$form = new request_form($pageurl);
if ($form->is_cancelled()) {
    redirect(get_login_url());
} else if ($data = $form->get_data()) {
    $SESSION->$sessionkey = manager::process_request((string)$data->identifier, (string)getremoteaddr());
    redirect($pageurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pagetitle', $component));

if ($result && $result['status'] === manager::STATUS_SENT) {
    $list = html_writer::alist(array_map('s', $result['emails']));
    $message = html_writer::tag('p', get_string('result_sent', $component))
        . $list
        . html_writer::tag('p', get_string('result_sent_details', $component, $result['minutes']));
    echo $OUTPUT->notification($message, \core\output\notification::NOTIFY_SUCCESS, false);
    echo html_writer::div(
        html_writer::link(get_login_url(), get_string('backtologin', $component), ['class' => 'btn btn-primary']),
        'mt-3'
    );
} else {
    if ($result) {
        $message = html_writer::tag('p', get_string('result_' . $result['status'], $component));
        $contact = trim((string)get_config($component, 'contactinfo'));
        if ($contact !== '' && $result['status'] !== manager::STATUS_RATELIMITED) {
            $message .= html_writer::tag('p', format_text($contact, FORMAT_PLAIN, ['para' => false]));
        }
        echo $OUTPUT->notification($message, \core\output\notification::NOTIFY_ERROR, false);
    }
    $form->display();
}

echo $OUTPUT->footer();
