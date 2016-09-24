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

class RegisterJobForm extends moodleform {

    var $elements;
    var $editoroptions;
    var $context;

    /**
     * Dynamically defines the form using elements setup in jobtracker instance
     *
     *
     */
    function definition() {
        global $DB, $COURSE, $USER;

        $jobtrackerid = $this->_customdata['jobtrackerid'];

        $jobtracker = $DB->get_record('jobtracker', array('id' => $jobtrackerid));

        $this->context = context_module::instance($this->_customdata['cmid']);
        $maxfiles = 99;                // TODO: add some setting
        $maxbytes = $COURSE->maxbytes; // TODO: add some setting
        $this->editoroptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $this->context);

        $mform = $this->_form;

        $mform->addElement('hidden', 'id', $this->_customdata['cmid']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'jobtrackerid', $jobtrackerid);
        $mform->setType('jobtrackerid', PARAM_INT);

        // Add target users chooser for the opportunity.
        if (has_capability('mod/jobtracker:follow', $this->context)) {
            $cm = $DB->get_record('course_modules', array('id' => $this->_customdata['cmid']));
            if (groups_get_activity_groupmode($cm) != NOGROUPS) {
                $mygroups = groups_get_my_groups();
                $reporters = get_users_by_capability($this->context, 'mod/jobtracker:report', 'u.id, u.firstname, u.lastname', 'lastname,firstname', '', '', $mygroups);
            } else {
                $reporters = get_users_by_capability($this->context, 'mod/jobtracker:report', 'u.id, u.firstname, u.lastname', 'lastname,firstname');
            }
            if ($reporters) {
                foreach($reporters as $r) {
                    $reportermenu[$r->id] = fullname($r);
                }
            }
            $select = $mform->addElement('select', 'userids', get_string('forusers', 'jobtracker'), $reportermenu);
            $select->setMultiple(true);
            $mform->setDefault('userids', array($USER->id));
            $mform->setType('userids', PARAM_INT);
        }

        $mform->addElement('text', 'company', get_string('company', 'jobtracker'), array('size' => 80));
        $mform->setType('company', PARAM_TEXT);
        $mform->addRule('company', null, 'required', null, 'client');

        $mform->addElement('text', 'contact', get_string('contact', 'jobtracker'), array('size' => 80));
        $mform->setType('contact', PARAM_TEXT);
        $mform->addRule('contact', null, 'required', null, 'client');

        $mform->addElement('text', 'contactphone', get_string('contactphone', 'jobtracker'), array('size' => 80));
        $mform->setType('contactphone', PARAM_TEXT);
        $mform->addRule('contactphone', null, 'required', null, 'client');

        $mform->addElement('text', 'contactmail', get_string('contactmail', 'jobtracker'), array('size' => 80));
        $mform->setType('contactmail', PARAM_TEXT);

        $mform->addElement('text', 'position', get_string('position', 'jobtracker'), array('size' => 80));
        $mform->setType('position', PARAM_TEXT);
        $mform->addRule('position', null, 'required', null, 'client');

        jobtracker_loadelementsused($jobtracker, $this->elements);

        if (!empty($this->elements)) {
            foreach ($this->elements as $element) {
                $element->add_form_element($mform);
            }
        }

        $mform->addElement('header', 'h1', get_string('notes', 'jobtracker'));
        $mform->addElement('editor', 'notes', get_string('notes', 'jobtracker'));

        $this->add_action_buttons();
    }

    function validation($data, $files = null) {
    }

    function set_data($defaults) {
        global $COURSE;

        // something to prepare for each element ? 
        if (!empty($this->elements)) {
            foreach ($this->elements as $element) {
                $element->set_data($defaults);
            }
        }

        parent::set_data($defaults);
    }
}