<?php

/**
 * @package jobtracker
 * @author Clifford Tham
 * @review Valery Fremaux / 1.8
 * @date 02/12/2007
 *
 * A class implementing a dropdown element
 */
require_once $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/jobtrackerelement.class.php';

class dropdownelement extends jobtrackerelement {

    var $multiple;

    function dropdownelement(&$jobtracker, $id = null, $used = false) {
        parent::__construct($jobtracker, $id, $used);
        $this->setoptionsfromdb();
    }

    function view($jobid = 0) {

        $this->getvalue($jobid); // loads $this->value with current value for this issue
        if (isset($this->options)) {
            $optionstrs = array();
            foreach ($this->options as $option) {
                if ($this->value != null) {
                    if ($this->value == $option->name) {
                        $optionstrs[] = format_string($option->description);
                    }
                }
            }
            echo implode(', ', $optionstrs);
        }
    }

    function edit($jobid = 0) {

        $this->getvalue($jobid);

        $values = explode(',', $this->value); // whatever the form ... revert to an array.

        if (isset($this->options)) {
            foreach($this->options as $optionobj) {
                $selectoptions[$optionobj->name] = $optionobj->description;
            }
            echo html_writer::select($selectoptions, $this->name, $values, array('' => 'choosedots'));
            echo html_writer::empty_tag('br');
        }
    }

    function add_form_element(&$form) {

        if (isset($this->options)) {
            foreach ($this->options as $option) {
                $optionsmenu[$option->id] = format_string($option->description);
            }

            $form->addElement('header', "head{$this->name}", $this->description);
            $form->setExpanded("head{$this->name}");
            $form->addElement('select', $this->name, format_string($this->description), $optionsmenu);
        }
    }

    function set_data(&$defaults, $jobid = 0) {
        if ($jobid) {

            $elementname = $this->name;

            if (!empty($this->options)) {
                $values = $this->getvalue($jobid);
                if ($multiple && is_array($values)) {
                    foreach ($values as $v) {
                        if (array_key_exists($v, $this->options)) {
                            // Check option still exists.
                            $choice[] = $v;
                        }
                        if (!empty($choice)) {
                            $defaults->$elementname = $choice;
                        }
                    }
                } else {
                    $v = $values; // single value
                    if (array_key_exists($v, $this->options)) {
                        // Check option still exists.
                        $defaults->$elementname = $v;
                    }
                }
            }
        }
    }

    function formprocess(&$data) {
        global $DB;

        $sqlparams = array('elementid' => $this->id, 'jobtrackerid' => $data->jobtrackerid, 'jobid' => $data->jobid);
        if (!$attribute = $DB->get_record('jobtracker_jobattribute', $sqlparams)) {
            $attribute = new StdClass();
            $attribute->jobtrackerid = $data->jobtrackerid;
            $attribute->jobid = $data->jobid;
            $attribute->elementid = $this->id;
        }

        $elmname = $this->name;

        if (!$this->multiple) {
            $value = optional_param($elmname, '', PARAM_TEXT);
            $attribute->elementitemid = $value;
        } else {
            $valuearr = optional_param_array($elmname, '', PARAM_TEXT);
            if (is_array($data->$elmname)) {
                $attribute->elementitemid = implode(',', $valuearr);
            } else {
                $attribute->elementitemid = $data->$elmname;
            }
        }

        $attribute->timemodified = time();

        if (!isset($attribute->id)) {
            $DB->insert_record('jobtracker_jobattribute', $attribute);
        } else {
            $DB->update_record('jobtracker_jobattribute', $attribute);
        }
    }
}

