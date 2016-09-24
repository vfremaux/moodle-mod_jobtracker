<?php

require_once($CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/jobtracker_element_form.php');

class jobtracker_element_timepicker_form extends jobtracker_moodle_form {

    public $attr_mapping = array('param1' => 'enabletimepart');

    function definition() {
        $this->start_form();

        $mform = $this->_form;

        $mform->addElement('hidden', 'type');
        $mform->setDefault('type', 'timepicker');
        $mform->setType('type', PARAM_TEXT);

        $mform = $this->_form;
        $mform->addElement('checkbox', 'enabletimepart', get_string('enabletimepart', 'jobtracker'), 0);

        $this->end_form();
    }

    function validation($data, $files) {
        return parent::validation($data, $files);
    }
}