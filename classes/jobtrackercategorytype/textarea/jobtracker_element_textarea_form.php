<?php

require_once($CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/jobtracker_element_form.php');

class jobtracker_element_textarea_form extends jobtracker_moodle_form {

    function definition() {
        $this->start_form();

        $mform = $this->_form;

        $mform->addElement('hidden', 'type');
        $mform->setDefault('type', 'textarea');
        $mform->setType('type', PARAM_TEXT);

        $this->end_form();
    }

    function validation($data, $files) {
        return parent::validation($data, $files);
    }
}