<?php

/**
 * @package mod-jobtracker
 * @category mod
 * @author Valery Fremaux
 * @date 02/11/2014
 *
 * Controller for all "view" related views
 * 
 * // @usecase submitanjob // gone away 
 * @usecase updateanjob
 * @usecase delete
 * @usecase updatelist
 * @usecase addcomment (form)
 * @usecase doaddcomment
 * @usecase usequery
 * @usecase register
 * @usecase unregister
 * @usecase cascade
 * @usecase distribute
 * @usecase raisepriority
 * @usecase lowerpriority
 * @usecase raisetotop
 * @usecase lowertobottom
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/jobtracker
}

/************************************* update an opportunity *****************************/
elseif ($action == 'updateanopportunity') {
    $job = new StdClass;
    $job->id = required_param('jobid', PARAM_INT);
    $job->jobid = $job->id;
    $job->status = required_param('status', PARAM_INT);
    $job->company = required_param('company', PARAM_TEXT);
    $job->contact = required_param('contact', PARAM_TEXT);
    $job->contactphone = required_param('contactphone', PARAM_TEXT);
    $job->contactmail = required_param('contactmail', PARAM_TEXT);
    $editoroptions = array('maxfiles' => 99, 'maxbytes' => $COURSE->maxbytes, 'context' => $context);

    $job->resolution_editor = required_param_array('resolution_editor', PARAM_CLEANHTML);
    $job->resolutionformat = $job->resolution_editor['format'];

    $job->resolution = file_save_draft_area_files($job->resolution_editor['itemid'], $context->id, 'mod_jobtracker', 'jobresolution', $job->id, $editoroptions, $job->resolution_editor['text']);

    $job->timecreated = required_param('timecreated', PARAM_INT);

    $job->jobtrackerid = $jobtracker->id;

    $job->timemodified = time();

    $DB->update_record('jobtracker_job', $job);

    // if not CCed, the assignee should be.
    jobtracker_register_cc($jobtracker, $job, $job->followedby);

    // Send state change notification.
    if ($oldrecord->status != $job->status) {
        jobtracker_notifyccs_changestate($job->id, $jobtracker);

        // Log state change.
        $stc = new StdClass;
        $stc->userid = $USER->id;
        $stc->jobid = $job->id;
        $stc->jobtrackerid = $jobtracker->id;
        $stc->timechange = time();
        $stc->statusfrom = $oldrecord->status;
        $stc->statusto = $job->status;
        $DB->insert_record('jobtracker_state_change', $stc);
    }

    jobtracker_clearelements($job->id);
    jobtracker_recordelements($job, $job);

}
/************************************* delete an job record *****************************/
elseif ($action == 'delete') {
    $jobid = required_param('jobid', PARAM_INT);
    $maxpriority = $DB->get_field('jobtracker_job', 'resolutionpriority', array('id' => $jobid));

    $DB->delete_records('jobtracker_job', array('id' => $jobid));
    $attributeids = $DB->get_records('jobtracker_jobattribute', array('jobid' => $jobid), 'id', 'id,id');
    $DB->delete_records('jobtracker_jobattribute', array('jobid' => $jobid));
    $commentids = $DB->get_records('jobtracker_jobcomment', array('jobid' => $jobid), 'id', 'id,id');
    $DB->delete_records('jobtracker_jobcomment', array('jobid' => $jobid));
    $DB->delete_records('jobtracker_state_change', array('jobid' => $jobid));

    // lower priority of every job above
    $sql = "
        UPDATE
            {jobtracker_job}
        SET
            resolutionpriority = resolutionpriority - 1
        WHERE
            jobtrackerid = ? AND
            resolutionpriority > ?
    ";

    $DB->execute($sql, array($jobtracker->id, $maxpriority));

    // todo : send notification to all cced

    $DB->delete_records('jobtracker_jobcc', array('jobid' => $jobid));

    // clear all associated fileareas;

    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_jobtracker', 'jobresolution', $jobid);

    if ($attributeids) {
        foreach ($attributeids as $attributeid => $void) {
            $fs->delete_area_files($context->id, 'mod_jobtracker', 'jobattribute', $jobid);
        }
    }

    if ($commentids) {
        foreach($commentids as $commentid => $void){
            $fs->delete_area_files($context->id, 'mod_jobtracker', 'jobcomment', $commentid);
        }
    }
}
/************************************* updating list and status *****************************/
elseif ($action == 'updatelist') {

    $keys = array_keys($_POST);                                // get the key value of all the fields submitted
    $statuskeys = preg_grep('/status./' , $keys);              // filter out only the status

    foreach ($statuskeys as $akey) {
        $jobid = str_replace('status', '', $akey);
        $haschanged = optional_param('schanged'.$jobid, 0, PARAM_INT);
        $newstatus = required_param($akey, PARAM_INT);
        if ($haschanged) {
            jobtracker_update_status($jobid, $newstatus);
        }
    }

    // Reorder priority field and discard newly resolved or abandonned.
    jobtracker_update_priority_stack($jobtracker);
}
/************************************ reactivates a stored search *****************************/
elseif ($action == 'usequery') {
    $queryid = required_param('queryid', PARAM_INT);
    $fields = jobtracker_extractsearchparametersfromdb($queryid);
}
/******************************* unregister administratively a user *****************************/
elseif ($action == 'unregister') {
    $jobid = required_param('jobid', PARAM_INT);
    $ccid = optional_param('ccid', $USER->id, PARAM_INT);
    $DB->delete_records ('jobtracker_jobcc', array('jobtrackerid' => $jobtracker->id, 'jobid' => $jobid, 'userid' => $ccid));
}
elseif ($action == 'register') {
    $jobid = required_param('jobid', PARAM_INT);
    $ccid = optional_param('ccid', $USER->id, PARAM_INT);
    $job = $DB->get_record('jobtracker_job', array('id' => $jobid));
    jobtracker_register_cc($jobtracker, $job, $ccid);
}
/********************************* raises the priority **************************/
elseif ($action == 'raisepriority') {
    $jobid = required_param('jobid', PARAM_INT);
    $job = $DB->get_record('jobtracker_job', array('id' => $jobid));
    $nextjob = $DB->get_record('jobtracker_job', array('jobtrackerid' => $jobtracker->id, 'resolutionpriority' => $job->resolutionpriority + 1));
    if ($nextjob) {
        $job->resolutionpriority++;
        $nextjob->resolutionpriority--;
        $DB->update_record('jobtracker_job', $job);
        $DB->update_record('jobtracker_job', $nextjob);
    }
    jobtracker_update_priority_stack($jobtracker);
}
/********************************* raises the priority at top of list **************************/
elseif ($action == 'raisetotop') {
    $jobid = required_param('jobid', PARAM_INT);
    $job = $DB->get_record('jobtracker_job', array('id' => $jobid));
    $maxpriority = $DB->get_field('jobtracker_job', 'resolutionpriority', array('id' => $jobid));

    if ($job->resolutionpriority != $maxpriority) {
        // lower everyone above
        $sql = "
            UPDATE 
                {$CFG->dbprefix}jobtracker_job
            SET 
                resolutionpriority = resolutionpriority - 1
            WHERE
                jobtrackerid = ? AND
                resolutionpriority > ?
        ";
        $DB->execute($sql, array($jobtracker->id, $job->resolutionpriority));
        // update to max priority
        $job->resolutionpriority = $maxpriority;
        $DB->update_record('jobtracker_job', $job);
    }
    jobtracker_update_priority_stack($jobtracker);
}
/********************************* lowers the priority of the job **************************/
elseif ($action == 'lowerpriority') {
    $jobid = required_param('jobid', PARAM_INT);
    $job = $DB->get_record('jobtracker_job', array('id' => $jobid));
    if ($job->resolutionpriority > 0) {
        $nextjob = $DB->get_record('jobtracker_job', array('jobtrackerid' => $jobtracker->id, 'resolutionpriority' => $job->resolutionpriority - 1));
        $job->resolutionpriority--;
        $nextjob->resolutionpriority++;
        $DB->update_record('jobtracker_job', $job);
        $DB->update_record('jobtracker_job', $nextjob);
    }
    jobtracker_update_priority_stack($jobtracker);
}
/********************************* raises the priority at top of list **************************/
elseif ($action == 'lowertobottom') {
    $jobid = required_param('jobid', PARAM_INT);
    $job = $DB->get_record('jobtracker_job', array('id' => $jobid));

    if ($job->resolutionpriority > 0){
        // raise everyone beneath
        $sql = "
            UPDATE
                {$CFG->dbprefix}jobtracker_job
            SET
                resolutionpriority = resolutionpriority + 1
            WHERE
                jobtrackerid = ? AND
                resolutionpriority < ?
        ";
        $DB->execute($sql, array($jobtracker->id, $job->resolutionpriority));
        // Update to min priority.
        $job->resolutionpriority = 0;
        $DB->update_record('jobtracker_job', $job);
    }
    jobtracker_update_priority_stack($jobtracker);
}
