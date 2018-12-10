<?php

require('../../config.php');
require_once($CFG->dirroot.'/mod/jobtracker/lib.php');
require_once($CFG->dirroot.'/mod/jobtracker/locallib.php');
require_once($CFG->dirroot.'/mod/jobtracker/forms/addcomment_form.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$t  = optional_param('t', 0, PARAM_INT);  // jobtracker ID
$jobid  = required_param('jobid', PARAM_INT);  // jobtracker ID

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
    
    if (! $jobtracker = $DB->get_record('jobtracker', array('id' => $t))) {
        print_error('errormoduleincorrect', 'jobtracker');
    }
    
    if (! $course = $DB->get_record('course', array('id' => $jobtracker->course))) {
        print_error('errorcoursemisconfigured', 'jobtracker');
    }
    if (! $cm = get_coursemodule_from_instance("jobtracker", $jobtracker->id, $course->id)) {
        print_error('errorcoursemodid', 'jobtracker');
    }
}

$context = context_module::instance($cm->id);

require_course_login($course->id, false, $cm);
require_capability('mod/jobtracker:comment', $context);

if (!$job = $DB->get_record('jobtracker_job', array('id' => $jobid))){
    print_error('errorbadjobid', 'jobtracker');
}

// Page setup.
$url = new moodle_url('/mod/jobtracker/addcomment.php', array('id' => $id, 'jobid' => $jobid));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(format_string($jobtracker->name));
$PAGE->set_heading(format_string($jobtracker->name));
$PAGE->set_headingmenu(navmenu($course, $cm));

add_to_log($course->id, 'jobtracker', "commentjob", "view.php?id={$cm->id}", "$jobtracker->id", $cm->id);

$form = new AddCommentForm($url->out_omit_querystring(), array('jobid' => $jobid, 'cmid' => $id));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/jobtracker/view.php', array('id' => $id, 'view' => 'view', 'screen' => 'viewanopportunity', 'jobid' => $jobid)));
}
 
if ($data = $form->get_data()) {
    $comment = new StdClass();
    $comment->comment = $data->comment_editor['text'];
    $comment->commentformat = $data->comment_editor['format'];
    $comment->userid = $USER->id;
    $comment->jobtrackerid = $jobtracker->id;
    $comment->jobid = $jobid;
    $comment->datecreated = time();
    if (!$comment->id = $DB->insert_record('jobtracker_jobcomment', $comment)) {
        print_error('cannotwritecomment', 'jobtracker');
    }

    if ($jobtracker->allownotifications) {
        jobtracker_notifyccs_comment($jobid, $comment->comment, $jobtracker);
    }
    jobtracker_register_cc($jobtracker, $job, $USER->id);

    // stores files
    $data = file_postupdate_standard_editor($data, 'comment', $form->editoroptions, $context, 'mod_jobtracker', 'jobcomment', $comment->id);
    // update back reencoded field text content
    $DB->set_field('jobtracker_jobcomment', 'comment', $data->comment, array('id' => $comment->id));
    redirect(new moodle_url('/mod/jobtracker/view.php', array('id' => $id, 'view' => 'view', 'screen' => 'viewanopportunity', 'jobid' => $jobid)));
}

echo $OUTPUT->header();

echo $OUTPUT->heading($job->company);

$renderer = $PAGE->get_renderer('jobtracker');
echo $renderer->job_abstract($job, $cm);

echo $OUTPUT->heading(get_string('addacomment', 'jobtracker'));

$form->display();

echo $OUTPUT->footer();