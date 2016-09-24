<?php

/**
 * @package jobtracker
 * @author Valery Fremaux
 *
 * A class implementing a textarea element and all its representations
 */
require_once($CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/jobtrackerelement.class.php');

class timepickerelement extends jobtrackerelement {

    function __construct(&$jobtracker, $id = null, $used = false) {
        parent::__construct($jobtracker, $id, $used);
    }

    function view($jobid = 0) {
        $this->getvalue($jobid);
        echo userdate($this->value);
    }

    function add_form_element(&$form) {
        $form->addElement('header', "head{$this->name}", $this->description);
        $form->setExpanded("head{$this->name}");

        if ($this->param1) {
            $form->addElement('date_time_selector', "element{$this->name}", '');
        } else {
            $form->addElement('date_selector', "element{$this->name}", '');
        }
    }

    function set_data(&$defaults, $jobid = 0) {
        $elementname = "element{$this->name}";
        if ($jobid) {
            $defaults->$elementname = $this->getvalue($jobid);
        } else {
            $defaults->$elementname = '';
        }
    }

    function formprocess(&$data) {
        global $DB;

        if (!$attribute = $DB->get_record('jobtracker_jobattribute', array('elementid' => $this->id, 'jobtrackerid' => $data->jobtrackerid, 'jobid' => $data->jobid))) {
            $attribute = new StdClass();
            $attribute->jobtrackerid = $data->jobtrackerid;
            $attribute->jobid = $data->jobid;
            $attribute->elementid = $this->id;
        }

        $elmname = 'element'.$this->name;
        $timepicked = required_param_array($elmname, PARAM_TEXT);
        if ($this->param1) {
            // datetime
            $data->$elmname = mktime($timepicked['second'],$timepicked['minute'],$timepicked['hour'],$timepicked['month'], $timepicked['day'], $timepicked['year']);
        } else {
            // dateonly
            $data->$elmname = mktime(0,0,0,$timepicked['month'], $timepicked['day'], $timepicked['year']);
        }
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
