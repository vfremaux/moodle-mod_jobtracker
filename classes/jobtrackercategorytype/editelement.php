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
    if (! $cm = get_coursemodule_from_id('jobtracker', $id)) {
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

if ($elmid) {
    $element = $DB->get_record('jobtracker_element', array('id' => $elmid));
    $elmtype = $element->type;
}
if (!$elmtype) {
    print_error('badelementtype', 'jobtracker');
}

$url = new moodle_url('/mod/jobtracker/editelement.php', array('id' => $cm->id));
$context = context_module::instance($cm->id);

$formclassname = 'jobtracker_element_'.$elmtype.'_form';
require_once($CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/'.$elmtype.'/'.$formclassname.'.php');

$form = new $formclassname();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/jobtracker/view.php', array('id' => $id, 'view' => 'admin', 'screen' => 'manageelements')));
}

