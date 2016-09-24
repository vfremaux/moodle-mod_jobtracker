<?php

/**
 * @package jobtracker
 * @author Clifford Tham
 * @review Valery Fremaux / 1.8
 * @date 17/12/2007
 *
 * A class implementing a textfield element
 */
require_once($CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/jobtrackerelement.class.php');

class textelement extends jobtrackerelement {

    function __construct(&$jobtracker, $id = null, $used = false) {
        parent::__construct($jobtracker, $id, $used);
    }

    function view($jobid = 0) {
        $this->getvalue($jobid);
        echo format_text(format_string($this->value), $this->format);
    }

    function edit($jobid = 0) {
        $this->getvalue($jobid);
        echo html_writer::empty_tag('input', array('type' => 'text', 'name' => 'element'.$this->name, 'value' => format_string($this->value), 'size' => 80));
    }

    function add_form_element(&$form) {
        $form->addElement('header', "header{$this->name}", '');
        $form->setExpanded("head{$this->name}");
        $form->addElement('text', "element{$this->name}", $this->description, array('size' => 80));
        $form->setType("element{$this->name}", PARAM_TEXT);
    }

    function set_data(&$defaults, $jobid = 0) {
        if ($jobid) {
            $elementname = "element{$this->name}";
            $defaults->$elementname = $this->getvalue($jobid);
        } else {
            $defaults->$elementname = '';
        }
    }

    /**
     * updates or creates the element instance for this issue
     */
    function formprocess(&$data) {
        global $DB;

        if (!$attribute = $DB->get_record('jobtracker_jobattribute', array('elementid' => $this->id, 'jobtrackerid' => $data->jobtrackerid, 'jobid' => $data->jobid))) {
            $attribute = new StdClass();
            $attribute->jobtrackerid = $data->jobtrackerid;
            $attribute->jobid = $data->jobid;
            $attribute->elementid = $this->id;
        }

        $elmname = 'element'.$this->name;
        $data->$elmname = required_param($elmname, PARAM_TEXT);
        $attribute->elementitemid = $data->$elmname; // in this case true value in element id
        $attribute->timemodified = time();

        if (!isset($attribute->id)) {
            $attribute->id = $DB->insert_record('jobtracker_jobattribute', $attribute);
            if (empty($attribute->id)) {
                print_error('erroraddjobattribute', 'jobtracker', '', 2);
            }
        } else {
            $DB->update_record('jobtracker_jobattribute', $attribute);
        }
    }
}

