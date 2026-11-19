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
 * Plugin settings.
 *
 * @package    local_custom_password_recovery
 * @copyright  2026 CEAD/UnB
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $c = 'local_custom_password_recovery';
    $settings = new admin_settingpage($c, new lang_string('pluginname', $c));
    $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        $a = new stdClass();
        $a->pageurl = (new moodle_url('/local/custom_password_recovery/index.php'))->out(false);
        $a->authsettings = (new moodle_url('/admin/settings.php', ['section' => 'manageauths']))->out(false);
        $a->minutes = \local_custom_password_recovery\manager::get_link_lifetime_minutes();
        $settings->add(new admin_setting_heading("$c/setupinfo", '', new lang_string('setupinfo', $c, $a)));

        $settings->add(new admin_setting_configcheckbox("$c/enabled",
            new lang_string('enabled', $c), new lang_string('enabled_desc', $c), 1));

        // Custom profile fields that may hold the secondary e-mail.
        $choices = ['' => new lang_string('none')];
        $fields = $DB->get_records('user_info_field', null, 'sortorder ASC', 'id, shortname, name');
        foreach ($fields as $field) {
            $choices[$field->shortname] = format_string($field->name) . ' (' . $field->shortname . ')';
        }
        $settings->add(new admin_setting_configselect("$c/secondaryemailfield",
            new lang_string('secondaryemailfield', $c), new lang_string('secondaryemailfield_desc', $c), '', $choices));

        $settings->add(new admin_setting_configselect("$c/identifiermode",
            new lang_string('identifiermode', $c), new lang_string('identifiermode_desc', $c), 'username', [
                'username' => new lang_string('identifiermode_username', $c),
                'cpf' => new lang_string('identifiermode_cpf', $c),
            ]));

        $settings->add(new admin_setting_configcheckbox("$c/maskemails",
            new lang_string('maskemails', $c), new lang_string('maskemails_desc', $c), 1));

        $settings->add(new admin_setting_heading("$c/securityheading",
            new lang_string('securityheading', $c), ''));

        $settings->add(new admin_setting_configtext("$c/maxperip",
            new lang_string('maxperip', $c), new lang_string('maxperip_desc', $c), 10, PARAM_INT));

        $settings->add(new admin_setting_configtext("$c/maxperaccount",
            new lang_string('maxperaccount', $c), new lang_string('maxperaccount_desc', $c), 3, PARAM_INT));

        $settings->add(new admin_setting_configduration("$c/ratewindow",
            new lang_string('ratewindow', $c), new lang_string('ratewindow_desc', $c), HOURSECS, MINSECS));

        $settings->add(new admin_setting_configcheckbox("$c/userecaptcha",
            new lang_string('userecaptcha', $c), new lang_string('userecaptcha_desc', $c), 0));

        $settings->add(new admin_setting_configtextarea("$c/contactinfo",
            new lang_string('contactinfo', $c), new lang_string('contactinfo_desc', $c), '', PARAM_TEXT));
    }
}
