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
 * This page prints a particular instance of a jobtracker and handles
 * top level interactions
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/jobtracker/lib.php');
require_once($CFG->dirroot.'/mod/jobtracker/locallib.php');

$elmtype = optional_param('type', '', PARAM_TEXT);
$elmid = optional_param('elementid', 0, PARAM_INT);
$id = required_param('id', PARAM_INT); // course module ID

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

// Security
require_login($course, true, $cm);

if ($elmid) {
    $element = $DB->get_record('jobtracker_element', array('id' => $elmid));
    $elmtype = $element->type;
}
if (!$elmtype) {
    print_error('badelementtype', 'jobtracker');
}

$url = new moodle_url('/mod/jobtracker/editelement.php', array('id' => $cm->id, 'elementtype' => $elmtype, 'elementid' => $elmid));
$returnurl = new moodle_url('/mod/jobtracker/view.php', array('id' => $id, 'view' => 'admin', 'screen' => 'manageelements'));
$context = context_module::instance($cm->id);

$PAGE->set_context($context);
$PAGE->set_title(format_string($jobtracker->name));
$PAGE->set_heading(format_string($jobtracker->name));
$PAGE->navbar->add(get_string('elementadmin', 'jobtracker'), $returnurl);
if ($elmid) {
    $PAGE->navbar->add(get_string('updateelement', 'jobtracker'), $returnurl);
} else {
    $PAGE->navbar->add(get_string('createelement', 'jobtracker'), $returnurl);
}
$PAGE->set_url($url);
$PAGE->set_button($OUTPUT->update_module_button($cm->id, 'jobtracker'));

$formclassname = 'jobtracker_element_'.$elmtype.'_form';
require_once($CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/'.$elmtype.'/'.$formclassname.'.php');
$form = new $formclassname($url->out_omit_querystring(), array('elementid' => $elmid));

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    global $DB;

    // transfer some data from form to storage through attr mapping
    if (isset($form->attr_mapping)) {
        foreach($form->attr_mapping as $attr => $key) {
            $element->$attr = $data->$key;
        }
    }

    $data->course = ($data->shared) ? 0 : $COURSE->id;
    unset($data->shared);

    if ($data->elementid) {
        $elmid = $data->id = $data->elementid;
        unset($data->elementid);
        $DB->update_record('jobtracker_element', $data);
    } else {
        $elmid = $DB->insert_record('jobtracker_element', $data);
    }

    $elementobj = jobtracker_get_element($jobtracker, $elmid, $data->type);

    if ($elementobj->hasoptions()) {
        // Bounces to the option editor.
        $editoptionsurl = new moodle_url('/mod/jobtracker/editelementoptions.php', array('id' => $id, 'elementid' => $elmid));
        redirect($editoptionsurl);
    }

    // Bounces back to the element management.
    redirect($returnurl);
}

echo $OUTPUT->header();

$typestr = get_string($elmtype, 'jobtracker');

if (!$elmid) {
    $formheading = get_string('newelement', 'jobtracker', $typestr);
} else {
    $formheading = get_string('updateelement', 'jobtracker', $typestr);
}

echo $OUTPUT->heading($formheading);

if (isset($element)) {
    $element->elementid = $element->id;
    $element->id = $id;
    $form->set_data($element);
}
$form->display();

echo $OUTPUT->footer();