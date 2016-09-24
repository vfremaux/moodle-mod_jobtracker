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
 * This page prints a job as an editable form
 */
require('../../config.php');
require_once($CFG->dirroot.'/mod/jobtracker/lib.php');
require_once($CFG->dirroot.'/mod/jobtracker/locallib.php');

$id = required_param('id', PARAM_INT); // course module ID
$jobid = required_param('jobid', PARAM_INT);
$returnscreen = optional_param('return', 'viewanopportunity', PARAM_TEXT);

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

if ($jobid) {
    $job = $DB->get_record('jobtracker_job', array('id' => $jobid));
}

$url = new moodle_url('/mod/jobtracker/editajob.php', array('id' => $cm->id, 'jobid' => $jobid));
$returnurl = new moodle_url('/mod/jobtracker/view.php', array('id' => $id, 'view' => 'view', 'screen' => $returnscreen, 'jobid' => $jobid));

$context = context_module::instance($cm->id);

$PAGE->set_context($context);
$PAGE->set_title(format_string($jobtracker->name));
$PAGE->set_heading(format_string($jobtracker->name));
$PAGE->navbar->add(get_string('jobedit', 'jobtracker'));
$PAGE->set_url($url);
$PAGE->set_button($OUTPUT->update_module_button($cm->id, 'jobtracker'));

require_once($CFG->dirroot.'/mod/jobtracker/forms/editjob_form.php');

$form = new EditJobForm($url->out_omit_querystring(), array('jobid' => $jobid, 'cmid' => $id, 'jobtracker' => $jobtracker, 'currentstate' => $STATUSLABELS[$job->status]));

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {

    $oldrecord = $DB->get_record('jobtracker_job', array('id' => $data->jobid));

    $data->id = $data->jobid;
    $editoroptions = array('maxfiles' => 99, 'maxbytes' => $COURSE->maxbytes, 'context' => $context);

    $resolution_draftid_editor = file_get_submitted_draft_itemid('resolution_editor');
    $data->resolution = file_save_draft_area_files($resolution_draftid_editor, $context->id, 'mod_jobtracker', 'jobresolution', $data->id, $editoroptions, $data->resolution['text']);
    $notes_draftid_editor = file_get_submitted_draft_itemid('notes_editor');
    $data->notes = file_save_draft_area_files($notes_draftid_editor, $context->id, 'mod_jobtracker', 'jobnotes', $data->id, $editoroptions, $data->notes['text']);

    $data->jobtrackerid = $jobtracker->id;

    $data->timemodified = time();

    $DB->update_record('jobtracker_job', $data);

    // Send state change notification.
    if ($oldrecord->status != $data->status) {
        if ($jobtracker->allownotifications) {
            jobtracker_notifyccs_changestate($data->id, $jobtracker);
        }

        // Log state change.
        $stc = new StdClass;
        $stc->userid = $USER->id;
        $stc->jobid = $data->id;
        $stc->jobtrackerid = $jobtracker->id;
        $stc->timechange = time();
        $stc->statusfrom = $oldrecord->status;
        $stc->statusto = $data->status;
        $DB->insert_record('jobtracker_state_change', $stc);

        $event = \mod_jobtracker\event\jobtracker_jobupdated::create_from_job($jobtracker, $data->id);
        $event->add_record_snapshot('jobtracker', $jobtracker);
        $event->add_record_snapshot('jobtracker_job', $data);
        $event->trigger();
    }

    jobtracker_clearelements($data->id);
    jobtracker_recordelements($data, $data);

    // Bounces back to job view.
    redirect($returnurl);
}

echo $OUTPUT->header();

$formheading = get_string('jobedit', 'jobtracker');
echo $OUTPUT->heading($formheading);

$job->jobid = $job->id;
$job->id = $id; // cmid
$job->return = $returnscreen; // screen to return to
$form->set_data($job);
$form->display();

echo $OUTPUT->footer();