<?php

/**
 * @package jobtracker
 * @author Clifford Tham
 * @review Valery Fremaux / 1.8
 * @date 02/12/2007
 *
 * A class implementing a radio button (exclusive choice) element horizontally displayed
 */

require_once $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/jobtrackerelement.class.php';

class radiohorizelement extends jobtrackerelement {

    function __construct(&$jobtracker, $id = null, $used = false) {
        parent::__construct($jobtracker, $id, $used);
        $this->setoptionsfromdb();
    }
    
    function view($jobid = 0) {
        $this->getvalue($jobid);

        $optbynames = array();
        foreach ($this->options as $opt) {
            $optbynames[$opt->name] = format_string($opt->description);
        }

        if (!empty($this->options) && !empty($this->value) && array_key_exists($this->value, $optbynames)) {
            echo $optbynames[$this->value];
        }
    }

    function edit($jobid = 0) {
        global $OUTPUT;
        
        $this->getvalue($jobid);
        if (isset($this->options)) {
            $optbynames = array();
            foreach ($this->options as $opt) {
                $optbynames[$opt->name] = format_string($opt->description);
            }

            foreach ($optbynames as $name => $option) {
                if ($this->value == $name) {
                    echo html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'element'.$this->name, 'value' => $name, 'checked' => 'checked'));
                } else {
                    echo html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'element'.$this->name, 'value' => $name));
                }
                echo format_string($option);
                echo html_writer::empty_tag('br');
            }
        }
    }

    function add_form_element(&$form) {
        if (isset($this->options)) {
            $group = array();
            $form->addElement('header', "head{$this->name}", $this->description);
            $form->setExpanded("head{$this->name}");
            foreach ($this->options as $option) {
                $group[] = &$form->createElement('radio', 'element'.$this->name, '', $option->description, $option->name);
                $form->setType('element'.$this->name, PARAM_TEXT);
            }
            
            $form->addGroup($group, 'element' . $this->name.'_set', '', false);
        }
    }

    function set_data(&$defaults, $jobid = 0) {
        if ($jobid) {
            if (!empty($this->options)) {
                $elmvalues = $this->getvalue($jobid);
                $values = explode(',', $elmvalues);
                if (!empty($values)) {
                    foreach ($values as $v) {
                        if (array_key_exists($v, $this->options)) {
                            // Check option still exists.
                            $elementname = "element{$this->name}{$option->id}";
                            $defaults->$elementname = 1;
                        }
                    }
                }
            }
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
        $data->$elmname = optional_param($elmname, '', PARAM_TEXT);
        $attribute->elementitemid = $data->$elmname; // in this case we have elementitem id or idlist 
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
