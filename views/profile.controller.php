<?PHP

/**
 * @package mod_jobtracker
 * @category mod
 * @author Valery Fremaux
 * @date 02/11/2014
 *
 * Controller for all "profile" related views
 *
 * @usecase register
 * @usecase unregister
 * @usecase editwatch (form)
 * @usecase updatewatch
 * @usecase saveprefs
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/jobtracker
}

/******************************** register to an job **************************/
elseif ($action == 'register') {
    $jobid = optional_param('jobid', '', PARAM_INT);
    if (!$DB->get_record('jobtracker_jobcc', array('jobtrackerid' => $jobtracker->id, 'jobid' => $jobid, 'userid' => $USER->id))){
        $cc->jobtrackerid = $jobtracker->id;
        $cc->jobid = $jobid;
        $cc->userid = $USER->id;
        $cc->events = (isset($USER->jobtrackerprefs->eventmask)) ? $USER->jobtrackerprefs->eventmask : JOBTRACK_ALL_EVENTS ;
        $DB->insert_record('jobtracker_jobcc', $cc);
    }
}
/******************************** unregister a watch on an job **************************/
elseif ($action == 'unregister') {
    $jobid = required_param('jobid', PARAM_INT);
    $ccid = required_param('ccid', PARAM_INT);
    if (!$DB->delete_records ('jobtracker_jobcc', 'jobtrackerid', $jobtracker->id, 'jobid', $jobid, 'userid', $ccid)) {
        $e->job = $jobtracker->ticketprefix.$jobid;
        $e->userid = $ccid;
        print_error('errorcannotdeletecarboncopyforuser', 'jobtracker', $e);
    }
}
/******************************** unregister all my watches **************************/
elseif ($action == 'unregisterall') {
    $userid = required_param('userid', PARAM_INT);
    if (! $DB->delete_records ('jobtracker_jobcc', 'jobtrackerid', $jobtracker->id, 'userid', $userid)) {
        print_error('errorcannotdeletecarboncopies', 'jobtracker', $userid);
    }
}
/************************** ask for editing the watchers configuration **************************/
elseif ($action == 'editwatch') {
    $ccid = optional_param('ccid', '', PARAM_INT);
    if (!$form = $DB->get_record('jobtracker_jobcc', array('id' => $ccid))) {
        print_error('errorcannoteditwatch', 'jobtracker');
    }
    $job = $DB->get_record('jobtracker_job', array('id' => $form->jobid));
    $form->company = $job->company;

    include "views/editwatch.html";
    return -1;
}
/********************************* update a watchers config for an job **************************/
elseif ($action == 'updatewatch') {
    $cc = new StdClass();
    $cc->id = required_param('ccid', PARAM_INT);
    $open = optional_param('open', '', PARAM_INT);
    $shortlist = optional_param('shortlist', '', PARAM_INT);
    $waitingevent = optional_param('waitingevent', '', PARAM_INT);
    $torefresh = optional_param('torefresh', '', PARAM_INT);
    $meetingscheduled = optional_param('meetingscheduled', '', PARAM_INT);
    $meetingdone = optional_param('meetingdone', '', PARAM_INT);
    $concluded = optional_param('concluded', '', PARAM_INT);
    $dead = optional_param('dead', '', PARAM_INT);
    $oncomment = optional_param('oncomment', '', PARAM_INT);
    $cc->events = $DB->get_field('jobtracker_jobcc', 'events', array('id' => $cc->id));
    if (is_numeric($open))
        $cc->events = ($open === 1) ? $cc->events | JOBTRACK_EVENT_OPEN : $cc->events & ~JOBTRACK_EVENT_OPEN;
    if (is_numeric($shortlist))
        $cc->events = ($shortlist === 1) ? $cc->events | JOBTRACK_EVENT_SHORTLIST : $cc->events & ~JOBTRACK_EVENT_SHORTLIST;
    if (is_numeric($waitingevent))
        $cc->events = ($waitingevent === 1) ? $cc->events | JOBTRACK_EVENT_WAITINGEVENT : $cc->events & ~JOBTRACK_EVENT_WAITINGEVENT;
    if (is_numeric($torefresh))
        $cc->events = ($torefresh === 1) ? $cc->events | JOBTRACK_EVENT_TOREFRESH : $cc->events & ~JOBTRACK_EVENT_TOREFRESH;
    if (is_numeric($meetingscheduled))
        $cc->events = ($meetingscheduled === 1) ? $cc->events | JOBTRACK_EVENT_MEETINGSCHEDULED : $cc->events & ~JOBTRACK_EVENT_MEETINGSCHEDULED;
    if (is_numeric($meetingdone))
        $cc->events = ($meetingdone === 1) ? $cc->events | JOBTRACK_EVENT_MEETINGDONE : $cc->events & ~JOBTRACK_EVENT_MEETINGDONE;
    if (is_numeric($concluded))
        $cc->events = ($concluded === 1) ? $cc->events | JOBTRACK_EVENT_CONCLUDED : $cc->events & ~JOBTRACK_EVENT_CONCLUDED;
    if (is_numeric($dead))
        $cc->events = ($dead === 1) ? $cc->events | JOBTRACK_EVENT_DEAD : $cc->events & ~JOBTRACK_EVENT_DEAD;
    if (is_numeric($oncomment))
        $cc->events = ($oncomment === 1) ? $cc->events | JOBTRACK_ON_COMMENT : $cc->events & ~JOBTRACK_ON_COMMENT;

    if (!$DB->update_record('jobtracker_jobcc', $cc)){
        print_error('errorcannotupdatewatcher', 'jobtracker', $url.'&amp;view=profile');
    }
}
