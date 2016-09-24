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
 * @package jobtracker
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @date 02/11/2014
 *
 * A generic class for collecting all that is common to all elements
 */

abstract class jobtrackerelement {
    var $id;
    var $course;
    var $usedid;
    var $name;
    var $description;
    var $format;
    var $type;
    var $param1;
    var $param2;
    var $param3;
    var $sortorder;
    var $maxorder;
    var $value;
    var $options;
    var $jobtracker;
    var $active;
    var $canbemodifiedby;
    var $context;

    function __construct(&$jobtracker, $elementid = null, $used = false) {
        global $DB;

        $this->id = $elementid;

        if ($elementid && $used) {
            $elmusedrec = $DB->get_record('jobtracker_elementused', array('id' => $elementid));
            $this->usedid = $elementid;
            $elementid = $elmusedrec->elementid;
            $this->active = $elmusedrec->active;
            $this->sortorder = $elmusedrec->sortorder;
            $this->canbemodifiedby = $elmusedrec->canbemodifiedby;
        }

        if ($elementid) {
            $elmrec = $DB->get_record('jobtracker_element', array('id' => $elementid));
            $this->id = $elmrec->id;
            $this->name = $elmrec->name;
            $this->description = $elmrec->description;
            $this->course = $elmrec->course;
            $this->type = $elmrec->type;
            $this->param1 = $elmrec->param1;
            $this->param2 = $elmrec->param2;
            $this->param3 = $elmrec->param3;
        }

        $this->options = null;
        $this->value = null;
        $this->jobtracker = $jobtracker;
    }

    function hasoptions() {
        return $this->options !== null;
    }

    function getoption($optionid) {
        return $this->options[$optionid];
    }

    function setoptions($options) {
        $this->options = $options;
    }

    function setcontext(&$context) {
        $this->context = $context;
    }

    /**
     * in case we have options (such as checkboxes or radio lists, get options from db.
     * this is backcalled by specific type constructors after core construction.
     *
     */
    function setoptionsfromdb() {
        global $DB;

        if (isset($this->id)) {
            $this->options = $DB->get_records_select('jobtracker_elementitem', " elementid = ? AND active = 1 ORDER BY sortorder", array($this->id));
            if ($this->options) {
                foreach ($this->options as $option) {
                    $this->maxorder = max($option->sortorder, $this->maxorder);
                }
            } else {
                $this->maxorder = 0;
            }
        } else {
            print_error ('errorinvalidelementID', 'jobtracker');
        }
    }

    /**
     *
     *
     */
    function getvalue($jobid) {
        global $CFG, $DB;

        if (!$jobid) return '';
        $sql = "
            SELECT 
                elementitemid
            FROM
                {jobtracker_jobattribute}
            WHERE
                elementid = {$this->id} AND
                jobid = {$jobid}
        ";
        $this->value = $DB->get_field_sql($sql);
        return($this->value);
    }

    function getname() {
        return $this->name;
    }

    function optionlistview($cm) {
        global $CFG, $COURSE, $OUTPUT;

        $strname = get_string('name');
        $strdescription = get_string('description');
        $strsortorder = get_string('sortorder', 'jobtracker');
        $straction = get_string('action');
        $table = new html_table();
        $table->width = "800";
        $table->size = array(100,110,240,75,75);
        $table->head = array('', "<b>$strname</b>","<b>$strdescription</b>","<b>$straction</b>");
        if (!empty($this->options)) {
            foreach ($this->options as $option) {
                $actionurl = new moodle_url('/mod/jobtracker/view.php', array('id' => $cm->id, 'view' => 'admin', 'what' => 'editelementoption', 'optionid' => $option->id, 'elementid' => $option->elementid));
                $actions  = '<a href=\"'.$actionurl.'" title="'.get_string('edit')."\"><img src=\"".$OUTPUT->pix_url('/t/edit', 'core')."\" /></a>&nbsp;" ;
                $img = ($option->sortorder > 1) ? 'up' : 'up_shadow' ;
                $actionurl = new moodle_url('/mod/jobtracker/view.php', array('id' => $cm->id, 'view' => 'admin', 'what' => 'moveelementoptionup', 'optionid' => $option->id, 'elementid' => $option->elementid));
                $actions .= '<a href="'.$actionurl.'" title="'.get_string('up').'"><img src="'.$OUTPUT->pix_url("{$img}", 'mod_jobtracker').'"></a>&nbsp;';
                $img = ($option->sortorder < $this->maxorder) ? 'down' : 'down_shadow' ;
                $actionurl = new moodle_url('/mod/jobtracker/view.php', array('id' => $cm->id, 'view' => 'admin', 'what' => 'moveelementoptiondown', 'optionid' => $option->id, 'elementid' => $option->elementid));
                $actions .= '<a href="'.$actionurl.'" title="'.get_string('down').'"><img src="'.$OUTPUT->pix_url("{$img}", 'mod_jobtracker').'"></a>&nbsp;';
                $actionurl = new moodle_url('/mod/jobtracker/view.php', array('id' => $cm->id, 'view' => 'admin', 'what' => 'deleteelementoption', 'optionid' => $option->id, 'elementid' => $option->elementid));
                $actions .= '<a href="'.$actionurl.'" title="'.get_string('delete')."\"><img src=\"".$OUTPUT->pix_url('/t/delete', 'core').'"></a>';
                $table->data[] = array('<b> '.get_string('option', 'jobtracker').' '.$option->sortorder.':</b>',$option->name, format_string($option->description, true, $COURSE->id), $actions);
            }
        }
        echo html_writer::table($table);
    }

    function viewsearch() {
        $this->edit();
    }

    function viewquery() {
        $this->view(true);
    }

    /**
     * given a jobtracker and an element form key in a static context, 
     * build a suitable jobtrackerelement object that represents it.
     */
    static function find_instance(&$jobtracker, $elementkey) {
        global $DB;

        $elmname = preg_replace('/^element/', '', $elementkey);

        $sql = "
            SELECT 
                e.*,
                eu.id as usedid
            FROM
                {jobtracker_element} e,
                {jobtracker_elementused} eu
            WHERE
                e.id = eu.elementid AND
                eu.jobtrackerid = ? AND
                e.name = ?
        ";

        if ($element = $DB->get_record_sql($sql, array($jobtracker->id, $elmname))) {

            $eltypeconstuctor = $element->type.'element';
            $instance = new $eltypeconstuctor($jobtracker, $element->id);
            return $element;
        }

        return null;
    }

    abstract function add_form_element(&$mform);

    abstract function formprocess(&$data);

    /**
     * given a jobtracker and an id of a used element in a static context, 
     * build a suitable jobtrackerelement object that represents it.
     * what we need to knwo is the type of the element to call the adequate
     * constructor.
     */
    static function find_instance_by_usedid(&$jobtracker, $usedid) {
        global $DB, $CFG;

        $sql = "
            SELECT 
                eu.id,
                e.type
            FROM
                {jobtracker_element} e,
                {jobtracker_elementused} eu
            WHERE
                e.id = eu.elementid AND
                eu.id = ?
        ";

        if ($element = $DB->get_record_sql($sql, array($usedid))) {

            $eltypeconstructor = $element->type.'element';
            include_once $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/'.$element->type.'/'.$element->type.'.class.php';
            $instance = new $eltypeconstructor($jobtracker, $usedid, true);
            return $instance;
        }

        return null;
    }

    /**
     * given a jobtracker and an id of a used element in a static context, 
     * build a suitable jobtrackerelement object that represents it.
     * what we need to knwo is the type of the element to call the adequate
     * constructor.
     */
    static function find_instance_by_id(&$jobtracker, $id) {
        global $DB, $CFG;

        if ($element = $DB->get_record('jobtracker_element', array('id' => $id), 'id, type', 'id')) {
            $eltypeconstructor = $element->type.'element';
            include_once $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/'.$element->type.'/'.$element->type.'.class.php';
            $instance = new $eltypeconstructor($jobtracker, $id, false);
            return $instance;
        }

        return null;
    }
}
