<?php

require_once($CFG->libdir.'/formslib.php');

abstract class jobtracker_moodle_form extends moodleform {

    function start_form() {

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'elementid');
        $mform->setType('elementid', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name'), '');
        $mform->setType('name', PARAM_CLEANHTML);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('textarea', 'description', get_string('description'));
    }

    function end_form() {
        $mform = $this->_form;
        $mform->addElement('checkbox', 'shared', '', get_string('sharethiselement', 'jobtracker'));
        $mform->setDefault('shared', true);
        $this->add_action_buttons();
    }

    function validation($data, $files) {

        $errors = array();

        if (empty($data['name'])) {
            $errors['name'] = get_string('namecannotbeblank', 'jobtracker');
        }

        return $errors;
    }

    function set_data($defaults) {

        // transfer some data from storage to form through attr mapping
        if (isset($this->attr_mapping)) {
            foreach($this->attr_mapping as $attr => $key) {
                $defaults->$key = $defaults->$attr;
                unset($defaults->$attr);
            }
        }
        parent::set_data($defaults);
    }

}