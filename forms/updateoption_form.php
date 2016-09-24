<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod_jobtracker
 * @category mod
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

require_once $CFG->libdir.'/formslib.php';

class UpdateOptionForm extends moodleform {

    /**
     * Dynamically defines the form
     */
    function definition() {
        global $DB, $COURSE, $USER;

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'elementid');
        $mform->setType('elementid', PARAM_INT);

        $mform->addElement('hidden', 'elementoptionid');
        $mform->setType('elementoptionid', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name'), array('size' => 40));
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'description', get_string('description'), array('size' => 80));
        $mform->setType('description', PARAM_CLEANHTML);

        $this->add_action_buttons();
    }

    function validation($data, $files = null) {
        global $DB;

        $errors = array();

        if (empty($data['name'])) {
            $errors['name'] = get_string('erroremptyoptionname', 'jobtracker');
        }

        if ($DB->get_record('jobtracker_elementitem', array('elementid' => $data['elementid'], 'name' => $data['name']))) {
            $errors['name'] = get_string('erroralreadyused', 'jobtracker');
        }
    }

}