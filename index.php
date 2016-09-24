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
 * @package mod_jobtracker
 * @category mod
 * @author Valery Fremaux
 * @date 02/11/2014
 *
 * This page lists all the instances of jobtracker in a particular course
 * Replace jobtracker with the name of your module
 */
require('../../config.php');
require_once($CFG->dirroot.'/mod/jobtracker/lib.php');

$id = required_param('id', PARAM_INT);   // course
$url = new moodle_url('/mod/jobtracker/index.php', array('id' => $id));
$PAGE->set_url($url);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_login($course->id);

// Trigger instances list viewed event.
$event = \mod_jobtracker\event\course_module_instance_list_viewed::create(array('context' => $context));
$event->add_record_snapshot('course', $course);
$event->trigger();

// Get all required strings.

$strjobtrackers = get_string('modulenameplural', 'jobtracker');
$strjobtracker  = get_string('modulename', 'jobtracker');

// Print the header.

$PAGE->set_title($strjobtrackers);
$PAGE->set_heading('');
$PAGE->navbar->add($strjobtrackers);
$PAGE->set_focuscontrol('');
$PAGE->set_cacheable(true);
$PAGE->set_button('');
$PAGE->set_headingmenu(navmenu($course));

echo $OUTPUT->header();

// Get all the appropriate data.

if (! $jobtrackers = get_all_instances_in_course('jobtracker', $course)) {
    echo $OUTPUT->notification(get_string('nojobtrackers', 'jobtracker'), new moodle_url('/course/view.php', array('id' => $course->id)));
    die;
}

// Print the list of instances (your module will probably extend this).

$timenow = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic  = get_string('topic');

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $strname);
    $table->align = array ('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left', 'left');
}

foreach ($jobtrackers as $jobtracker) {
    $jobtrackername = format_string($jobtracker->name);
    if (!$jobtracker->visible) {
        // Show dimmed if the mod is hidden.
        $link = "<a class=\"dimmed\" href=\"view.php?id={$jobtracker->coursemodule}\">{$jobtrackername}</a>";
    } else {
        // Show normal if the mod is visible.
        $link = "<a href=\"view.php?id={$jobtracker->coursemodule}\">{$jobtrackername}</a>";
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array ($jobtracker->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

echo '<br />';

echo html_writer::table($table);

// Finish the page.

echo $OUTPUT->footer($course);
