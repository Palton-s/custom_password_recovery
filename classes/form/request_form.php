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

namespace local_custom_password_recovery\form;

use local_custom_password_recovery\manager;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Password recovery request form (sesskey/CSRF protection is provided by moodleform).
 *
 * @package    local_custom_password_recovery
 * @copyright  2026 CEAD/UnB
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class request_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $component = manager::COMPONENT;

        $mform->addElement('static', 'intro', '', get_string('formintro', $component));

        $mform->addElement('text', 'identifier', get_string('identifier', $component),
            ['autocomplete' => 'off', 'maxlength' => 100, 'size' => 30]);
        $mform->setType('identifier', PARAM_RAW_TRIMMED);
        $mform->addRule('identifier', get_string('required'), 'required', null, 'client');

        if (manager::captcha_enabled()) {
            $mform->addElement('recaptcha', 'recaptcha_element', get_string('security_question', 'auth'));
        }

        $this->add_action_buttons(true, get_string('sendlink', $component));
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (trim((string)($data['identifier'] ?? '')) === '') {
            $errors['identifier'] = get_string('required');
        }

        if (manager::captcha_enabled()) {
            $element = $this->_form->getElement('recaptcha_element');
            $response = $this->_form->_submitValues['g-recaptcha-response'] ?? '';
            if ($response === '') {
                $errors['recaptcha_element'] = get_string('missingrecaptchachallengefield');
            } else if (!$element->verify($response)) {
                $errors['recaptcha_element'] = get_string('incorrectpleasetryagain', 'auth');
            }
        }

        return $errors;
    }
}
