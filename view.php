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
$PAGE->requires->js('/mod/jobtracker/js/js.js');

// Check for required parameters - Course Module Id, jobtrackerID.

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$a = optional_param('a', 0, PARAM_INT);  // jobtracker ID
$jobid = optional_param('jobid', '', PARAM_INT);  // job ID
$userid = optional_param('userid', 0, PARAM_INT); // current userid to see data for

$action = optional_param('what', '', PARAM_ALPHA);

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

$screen = jobtracker_resolve_screen($jobtracker, $cm);
$view = jobtracker_resolve_view($jobtracker, $cm);

$url = new moodle_url('/mod/jobtracker/view.php', array('id' => $cm->id));
$context = context_module::instance($cm->id);

$groupmode = groups_get_activity_groupmode($cm);

// redirect (before outputting) traps
if ($view == 'view' && (empty($screen) || $screen == 'viewanopportunity' || $screen == 'editanopportunity') && empty($jobid)) {
    redirect(new moodle_url('/mod/jobtracker/view.php', array('id' => $cm->id, 'view' => 'view', 'screen' => 'browse')));
}
if ($view == 'reportanopportunity') {
    redirect(new moodle_url('/mod/jobtracker/registeropportunity.php', array('id' => $id)));
}

// implicit routing
if ($jobid) {
    $view = 'view';
    if (empty($screen)) $screen = 'viewanopportunity';
}

// Security.

require_course_login($course->id, true, $cm);

if (has_capability('mod/jobtracker:workon', $context, $USER->id, false)) {
    $userid = $USER->id;
} elseif ($userid && !jobtracker_can_see_user($userid)) {
    print_error('targetusernotallowed', 'jobtracker');
}


// Logging.

// Trigger module viewed event.
$eventparams = array(
    'objectid' => $jobtracker->id,
    'context' => $context,
);

$event = \mod_jobtracker\event\course_module_viewed::create($eventparams);
$event->add_record_snapshot('jobtracker', $jobtracker);
$event->trigger();

jobtracker_loadpreferences($jobtracker->id, $USER->id);

/// Search controller - special implementation
// TODO : consider incorporing this controller back into standard MVC
if ($action == 'searchforopportunities') {
    $search = optional_param('search', null, PARAM_CLEANHTML);
    $saveasreport = optional_param('saveasreport', null, PARAM_CLEANHTML);

    if (!empty($search)) {       //search for issues
        jobtracker_searchforissues($jobtracker, $cm->id);
    } elseif (!empty ($saveasreport)) {        //save search as a report
        jobtracker_saveasreport($jobtracker->id);
    }
} elseif ($action == 'viewreport') {
    jobtracker_viewreport($jobtracker->id);
} elseif ($action == 'clearsearch') {
    if (jobtracker_clearsearchcookies($jobtracker->id)) {
        $returnview = ($jobtracker->supportmode == 'bugjobtracker') ? 'browse' : 'mytickets' ;
        redirect(new moodle_url('/mod/jobtracker/view.php', array('id' => $cm->id, 'view' => 'view', 'screen' => $returnview)));
    }
}

$strjobtrackers = get_string('modulenameplural', 'jobtracker');
$strjobtracker  = get_string('modulename', 'jobtracker');

include_once $CFG->dirroot.'/local/vflibs/jqplotlib.php';
local_vflibs_require_jqplot_libs();

$PAGE->set_context($context);

$PAGE->set_title(format_string($jobtracker->name));
$PAGE->set_heading(format_string($jobtracker->name));
$PAGE->set_url($url);

$renderer = $PAGE->get_renderer('jobtracker');

if ($screen == 'print') {
    $PAGE->set_pagelayout('embedded');
}

$out = $OUTPUT->header();

$out .= $OUTPUT->box_start('', 'jobtracker-view');
$out .= $renderer->menus($cm, $jobtracker, $view, $screen);

//=====================================================================
// Print the main part of the page
//
//=====================================================================
/// routing to appropriate view against situation
// echo "routing : $view:$screen:$action ";

if ($view == 'view') {
    echo $out;
    $result = 0 ;
    if ($action != '') {
        $result = include "views/view.controller.php";
    }
    if ($result != -1){
        switch($screen){
            case 'viewanopportunity' :
                ///If user it trying to view an issue, check to see if user has privileges to view this issue
                if (!has_any_capability(array('mod/jobtracker:seeopportunities','mod/jobtracker:follow','mod/jobtracker:manage'), $context)) {
                    print_error('errornoaccessjobs', 'jobtracker');
                } else {
                    include "views/viewajob.html";
                }
                break;
            case 'editanopportunity' :
                if (!has_capability('mod/jobtracker:manage', $context)) {
                    print_error('errornoaccessopportunities', 'jobtracker');
                } else {
                    include "views/editajob.html";
                }
                break;
            default:
                $resolved = 0;
                if ($userid) {
                    include $CFG->dirroot.'/mod/jobtracker/views/viewuserjobslist.php';
                } else {
                    include $CFG->dirroot.'/mod/jobtracker/views/viewjoblist.php';
                }
        }
    }
} elseif ($view == 'resolved') {
    echo $out;
    $result = 0 ;
    if ($action != ''){
        $result = include $CFG->dirroot.'/mod/jobtracker/views/view.controller.php';
    }
    if ($result != -1) {
        $resolved = 1;
        if ($userid) {
            include $CFG->dirroot.'/mod/jobtracker/views/viewuserjobslist.php';
        } else {
            include $CFG->dirroot.'/mod/jobtracker/views/viewjoblist.php';
        }
    }
} elseif ($view == 'reports') {
    echo $out;
    $result = 0;
    if ($result != -1) {
        switch ($screen) {
            case 'followed':
                include $CFG->dirroot.'/mod/jobtracker/report/followed.php';
                break;
            case 'status':
                include $CFG->dirroot.'/mod/jobtracker/report/status.html'; 
                break;
            case 'evolution':
                include $CFG->dirroot.'/mod/jobtracker/report/evolution.html';
                break;
            case 'print':
                include $CFG->dirroot.'/mod/jobtracker/report/print.html';
                break;
        }
    }
} elseif ($view == 'admin') {
    echo $out;
    $result = 0;
    if ($action != '') {
        $result = include $CFG->dirroot.'/mod/jobtracker/views/admin.controller.php';
    }
    if ($result != -1) {
        switch ($screen) {
            case 'manageelements':
                include $CFG->dirroot.'/mod/jobtracker/views/admin_manageelements.html';
                break;
            default: // Summary.
                include $CFG->dirroot.'/mod/jobtracker/views/admin_summary.html'; 
                break;
        }
    }
} elseif ($view == 'profile') {
    echo $out;
    $result = 0;
    if ($action != '') {
        $result = include $CFG->dirroot.'/mod/jobtracker/views/profile.controller.php';
    }
    if ($result != -1) {
        switch($screen) {
            case 'mypreferences' :
                include $CFG->dirroot.'/mod/jobtracker/views/mypreferences.php';
                break;
            case 'mywatches' :
                include $CFG->dirroot.'/mod/jobtracker/views/mywatches.html';
                break;
        }
    }
} else {
    echo $out;
    print_error('errorfindingaction', 'jobtracker', $action);
}
echo $OUTPUT->box_end();

if ($course->format == 'page') {
    include_once($CFG->dirroot.'/course/format/page/xlib.php');
    page_print_page_format_navigation($cm, $backtocourse = false);
}

echo $OUTPUT->footer();
