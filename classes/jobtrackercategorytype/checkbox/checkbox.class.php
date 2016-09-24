<?php

/**
 * @package jobtracker
 * @author Clifford Tham
 * @review Valery Fremaux / 1.8
 * @date 02/12/2007
 *
 * A class implementing a checkbox element
 */
require_once $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/jobtrackerelement.class.php';

class checkboxelement extends jobtrackerelement {

    function __construct(&$jobtracker, $id = null, $used = false) {
        parent::__construct($jobtracker, $id, $used);
        $this->setoptionsfromdb();
    }

    function edit($jobid = 0) {
        $this->getvalue($jobid);
        $values = explode(',', $this->value); // whatever the form ... revert to an array.
        if (isset($this->options)) {
            $optionsstrs = array();
            foreach ($this->options as $option) {
                if (in_array($option->id, $values)) {
                    echo html_writer::empty_tag('input', array('type' => 'checkbox', 'name' => 'element'.$this->name.$option->id, 'value' => 1, 'checked' => 'checked'));
                } else {
                    echo html_writer::empty_tag('input', array('type' => 'checkbox', 'name' => 'element'.$this->name.$option->id, 'value' => 1));
                }
                echo format_string($option->description);
                echo html_writer::empty_tag('br');
            }
        }
    }

    function view($jobid = 0) {
        $this->getvalue($jobid); // loads $this->value with current value for this issue
        if (!empty($this->value)) {
            $values = explode(',',$this->value);
            foreach ($values as $selected) {
                echo format_string($this->options[$selected]->description) . "<br/>\n";
            }
        }
    }

    function add_form_element(&$form) {
        if (isset($this->options)){
            $form->addElement('header', "head{$this->name}", $this->description);
            $form->setExpanded("head{$this->name}");
            foreach ($this->options as $option) {
                $form->addElement('checkbox', "element{$this->name}{$option->id}", $option->description);
                $form->setType("element{$this->name}{$option->id}", PARAM_TEXT);
            }
        }
    }

    function set_data(&$defaults, $jobid = 0) {
        if ($jobid) {
            if (!empty($this->options)) {
                $elmvalues = $this->getvalue($jobid);
                $values = explode(',', $elmvalues);
                if (!empty($values)) {
                    foreach ($values as $v) {
                        if (array_key_exists($v, $this->options)) { // check option still exists
                            $elementname = "element{$this->name}{$option->id}";
                            $defaults->$elementname = 1;
                        }
                    }
                }
            }
        }
    }

    function formprocess(&$data, $options = null) {
        global $DB;

        if (!$attribute = $DB->get_record('jobtracker_jobattribute', array('elementid' => $this->id, 'jobtrackerid' => $data->jobtrackerid, 'jobid' => $data->jobid))){
            $attribute = new StdClass();
            $attribute->jobtrackerid = $data->jobtrackerid;
            $attribute->jobid = $data->jobid;
            $attribute->elementid = $this->id;
        }

        $elmvalues = array();
        if (!empty($this->options)) {
            foreach ($this->options as $optid => $opt) {
                $elmname = 'element'.$this->name.$optid;
                $data->$elmname = optional_param($elmname, '', PARAM_TEXT);
                if (!empty($data->$elmname)) {
                    $elmvalues[] = $optid;
                }
            }
        }

        $attribute->elementitemid = implode(',', $elmvalues); // in this case we have elementitem id or idlist 
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

