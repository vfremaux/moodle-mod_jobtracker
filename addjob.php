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
 * @author Valery Fremaux (valery@club-internet.fr)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @version Moodle 2.2
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/jobtracker/lib.php');
require_once($CFG->dirroot.'/mod/jobtracker/locallib.php');
require_once($CFG->dirroot.'/mod/jobtracker/forms/registerjob_form.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // jobtracker ID

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
    if (! $cm = get_coursemodule_from_instance('jobtracker', $jobtracker->id, $course->id)) {
        print_error('errorcoursemodid', 'jobtracker');
    }
}

$screen = jobtracker_resolve_screen($jobtracker, $cm);
$view = jobtracker_resolve_view($jobtracker, $cm);

$context = context_module::instance($cm->id);

require_course_login($course->id, false, $cm);
require_capability('mod/jobtracker:report', $context);

// setting page
$url = new moodle_url('/mod/jobtracker/addjob.php', array('id' => $id));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(format_string($jobtracker->name));
$PAGE->set_heading(format_string($jobtracker->name));
$PAGE->set_headingmenu(navmenu($course, $cm));

$form = new RegisterJobForm($url->out_omit_querystring(), array('cmid' => $id, 'jobtrackerid' => $jobtracker->id));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/jobtracker/view.php', array('id' => $cm->id, 'view' => 'view')));
}
if ($data = $form->get_data()) {

    $jobs = array();
    if (!empty($data->userids) && has_capability('mod/jobtracker:follow', $context)) {
        foreach($data->userids as $targetuserid) {
            // Register a job for each target.
            $job = clone($data);
            unset($job->userids);
            $job->userid = $targetuserid;
            $job->status = 0 + JOBTRACK_POSTED;
            $jobs[] = $job;
        }
    } else {
        // Process a single registering.
        $job = clone($data);
        unset($job->userids);
        $job->userid = $USER->id;
        $job->status = 0 + JOBTRACK_OPEN;
        $jobs[] = $job;
    }

    foreach ($jobs as $job) {
        // Register a single job.
        if (!$job = jobtracker_submitanopportunity($jobtracker, $job)) {
            print_error('errorcannotsubmitticket', 'jobtracker');
        }

        $event = \mod_jobtracker\event\jobtracker_jobregistered::create_from_job($jobtracker, $job->id);
        $event->add_record_snapshot('jobtracker', $jobtracker);
        $event->trigger();

        // Log state change.
        $stc = new StdClass;
        $stc->userid = $USER->id;
        $stc->jobid = $job->id;
        $stc->jobtrackerid = $jobtracker->id;
        $stc->timechange = time();
        $stc->statusfrom = JOBTRACK_POSTED;
        $stc->statusto = $job->status;
        $DB->insert_record('jobtracker_state_change', $stc);

        if ($jobtracker->allownotifications) {
            if ($job->userid == $USER->id) {
                // Notify all mentors if i'm owner.
               jobtracker_notify_submission($job, $cm, $jobtracker);
            } else {
                // Notify mentee if posted by a mentor.
               jobtracker_notify_proposal($job, $cm, $jobtracker);
            }
        }
    }

    redirect(new moodle_url('/mod/jobtracker/view.php', array('id' => $cm->id, 'view' => 'view')));
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('addajob', 'jobtracker'));
$form->display();

echo $OUTPUT->footer();