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

class EditJobForm extends moodleform {

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

        $jobtracker = $this->_customdata['jobtracker'];

        $this->context = context_module::instance($this->_customdata['cmid']);
        $maxfiles = 99;                // TODO: add some setting
        $maxbytes = $COURSE->maxbytes; // TODO: add some setting
        $this->editoroptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $this->context);

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'return');
        $mform->setType('return', PARAM_TEXT);

        $mform->addElement('hidden', 'jobid');
        $mform->setType('jobid', PARAM_INT);

        $statusses = jobtracker_get_statuskeys($jobtracker, $cm = null);
        $mform->addElement('select', 'status', get_string('status', 'jobtracker'), $statusses, array('class' => 'status-'.$this->_customdata['currentstate']));

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
        $mform->setExpanded('h1');
        $mform->addElement('editor', 'notes', get_string('notes', 'jobtracker'));

        $mform->addElement('header', 'h2', get_string('resolution', 'jobtracker'));
        $mform->setExpanded('h2');
        $mform->addElement('editor', 'resolution', get_string('resolution', 'jobtracker'));

        $this->add_action_buttons();
    }

    function validation($data, $files = null) {
    }

    function set_data($defaults) {
        global $COURSE;

        $resolution_draftid_editor = file_get_submitted_draft_itemid('resolution_editor');
        $currenttext = file_prepare_draft_area($resolution_draftid_editor, $this->context->id, 'mod_jobtracker', 'resolution_editor', $defaults->id, $this->editoroptions, $defaults->resolution);
        $defaults = file_prepare_standard_editor($defaults, 'resolution', $this->editoroptions, $this->context, 'mod_jobtracker', 'jobresolution', $defaults->id);
        $defaults->resolution = array('text' => $currenttext, 'format' => $defaults->resolutionformat, 'itemid' => $resolution_draftid_editor);

        $notes_draftid_editor = file_get_submitted_draft_itemid('notes_editor');
        $currenttext = file_prepare_draft_area($notes_draftid_editor, $this->context->id, 'mod_jobtracker', 'notes_editor', $defaults->id, $this->editoroptions, $defaults->notes);
        $defaults = file_prepare_standard_editor($defaults, 'notes', $this->editoroptions, $this->context, 'mod_jobtracker', 'jobnotes', $defaults->id);
        $defaults->notes = array('text' => $currenttext, 'format' => $defaults->notesformat, 'itemid' => $notes_draftid_editor);

        // something to prepare for each element ? 
        if (!empty($this->elements)) {
            foreach ($this->elements as $element) {
                $element->set_data($defaults);
            }
        }

        parent::set_data($defaults);
    }
}