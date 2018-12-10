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
 * @package mod-jobtracker
 * @category mod
 * @author Valery Fremaux
 * @date 18/11/2014
 *
 * This page handles forms for editing options of elements such as checks, radio
 * buttons or dropdown options.
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/jobtracker/lib.php');
require_once($CFG->dirroot.'/mod/jobtracker/locallib.php');

$elmid = optional_param('elementid', 0, PARAM_INT);
$elmoptionid = optional_param('elementoptionid', 0, PARAM_INT);
$id = required_param('id', PARAM_INT); // course module ID
$action = optional_param('what', '', PARAM_TEXT);

if ($id) {
    if (! $cm = $DB->get_record('course_modules', array('id' => $id))) {
        print_error('errorcoursemodid', 'jobtracker');
    }
    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('errorcoursemisconfigured', 'jobtracker');
    }
    if (! $jobtracker = $DB->get_record('jobtracker', array('id' => $cm->instance))) {
        print_error('errormoduleincorrect', 'jobtracker');
    }
} else {
    if (! $jobtracker = $DB->get_record('jobtracker', array('id' => $a))) {
        print_error('errormoduleincorrect', 'jobtracker');
    }
    if (! $course = $DB->get_record('course', array('id' => $jobtracker->course))) {
        print_error('errorcoursemisconfigured', 'jobtracker');
    }
    if (! $cm = get_coursemodule_from_instance("jobtracker", $jobtracker->id, $course->id)) {
        print_error('errorcoursemodid', 'jobtracker');
    }
}

// Security.
require_login($course, true, $cm);

if ($elmoptionid) {
    $elementoption = $DB->get_record('jobtracker_elementitem', array('id' => $elmoptionid));
    $elmid = $elementoption->elementid;
}

if ($elmid) {
    $element = $DB->get_record('jobtracker_element', array('id' => $elmid));
} else {
    print_error('errorbadelementid', 'jobtracker');
}

$url = new moodle_url('/mod/jobtracker/editelementoptions.php', array('id' => $cm->id, 'elementid' => $elmid));
$returnurl = new moodle_url('/mod/jobtracker/editelement.php', array('id' => $cm->id, 'type' => $element->type, 'elementid' => $elmid));
$trackerurl = new moodle_url('/mod/jobtracker/view.php', array('id' => $id, 'view' => 'admin', 'screen' => 'manageelements'));
$context = context_module::instance($cm->id);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(format_string($jobtracker->name));
$PAGE->set_heading(format_string($jobtracker->name));
$PAGE->navbar->add(get_string('elementadmin', 'jobtracker'), $returnurl);
if ($elmid) {
    $PAGE->navbar->add(get_string('updateelementoption', 'jobtracker'), $returnurl);
} else {
    $PAGE->navbar->add(get_string('createelementoption', 'jobtracker'), $returnurl);
}

require_once($CFG->dirroot.'/mod/jobtracker/forms/updateoption_form.php');

$form = new UpdateOptionForm();

if (!empty($action)) {
    include($CFG->dirroot.'/mod/jobtracker/elementoptions.controller.php');
}

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    global $DB;

    if ($data->elementoptionid) {
        $data->id = $data->elementoptionid;
        unset($data->elementoptionid);

        $DB->update_record('jobtracker_elementitem', $data);
    } else {
        $data->sortorder = $DB->get_field('jobtracker_elementitem', 'MAX(sortorder)', array('elementid' => $data->elementid));
        $data->sortorder++;
        $DB->insert_record('jobtracker_elementitem', $data);
    }

    redirect($url);
}

echo $OUTPUT->header();

$options = $DB->get_records('jobtracker_elementitem', array('elementid' => $elmid), 'sortorder');

echo $OUTPUT->heading(get_string('element', 'jobtracker'));

$element = $DB->get_record('jobtracker_element', array('id' => $elmid));
echo get_string('name').': '.$element->name;
echo '<br/>';
echo get_string('description').': '.format_string($element->description);

echo $OUTPUT->heading(get_string('options', 'jobtracker'));

if ($options) {

    $descriptionstr = get_string('description');
    $codestr = get_string('name');

    $table = new html_table();
    $table->header = array($codestr, $descriptionstr, '');
    $table->size = array('40%', '40%', '20%');
    $table->width = '100%';

    $i = 0;
    $count = count($options);
    foreach ($options as $option) {
        $params = array('id' => $id, 'elementoptionid' => $option->id, 'what' => 'edit');
        $editurl = new moodle_url('/mod/jobtracker/editelementoptions.php', $params);
        $cmds = '<a href="'.$editurl.'" >'.$OUTPUT->pix_icon('/t/edit', get_string('edit')).'</a>';

        $params = array('id' => $id, 'elementoptionid' => $option->id, 'what' => 'delete');
        $deleteurl = new moodle_url('/mod/jobtracker/editelementoptions.php', $params);
        $cmds .= '<a href="'.$deleteurl.'" >'.$OUTPUT->pix_icon('/t/delete', get_string('delete')).'</a>';

        if ($i > 0) {
            $params = array('id' => $id, 'elementoptionid' => $option->id, 'what' => 'up');
            $upurl = new moodle_url('/mod/jobtracker/editelementoptions.php', $params);
            $cmds .= ' <a href="'.$upurl.'" >'.$OUTPUT->pix_icon('/t/up', get_string('up')).'</a>';
        }

        $i++;

        if ($i < $count) {
            $params = array('id' => $id, 'elementoptionid' => $option->id, 'what' => 'down');
            $downurl = new moodle_url('/mod/jobtracker/editelementoptions.php', $params);
            $cmds .= ' <a href="'.$downurl.'" >'.$OUTPUT->pix_icon('/t/down', get_string('down')).'</a>';
        }

        $table->data[] = array($option->name, $option->description, $cmds);

    }

    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('nooptions', 'jobtracker'));
}

if (isset($elementoption) && ($action == 'edit')) {

    echo $OUTPUT->heading(get_string('editoption', 'jobtracker'));

    $elementoption->elementoptionid = $elementoption->id;
    $elementoption->id = $cm->id;
    $form->set_data($elementoption);
} else {

    echo $OUTPUT->heading(get_string('newoption', 'jobtracker'));

    $newoption = new StdClass();
    $newoption->id = $cm->id;
    $newoption->elementid = $elmid;
    $form->set_data($newoption);
}
$form->display();

echo '<center>';
echo $OUTPUT->single_button($trackerurl, get_string('finish', 'jobtracker'));
echo '</center>';

echo $OUTPUT->footer();