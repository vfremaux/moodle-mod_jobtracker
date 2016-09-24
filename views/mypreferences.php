<?php

require_once($CFG->dirroot.'/mod/jobtracker/forms/preferences_form.php');

$formdata = new StdClass();
$formdata->open = @$USER->jobtrackerprefs->eventmask & JOBTRACK_EVENT_OPEN;
$formdata->shortlist = @$USER->jobtrackerprefs->eventmask & JOBTRACK_EVENT_SHORTLIST;
$formdata->waitingevent = @$USER->jobtrackerprefs->eventmask & JOBTRACK_EVENT_WAITINGEVENT;
$formdata->torefresh = @$USER->jobtrackerprefs->eventmask & JOBTRACK_EVENT_TOREFRESH;
$formdata->meetingscheduled = @$USER->jobtrackerprefs->eventmask & JOBTRACK_EVENT_MEETINGSCHEDULED;
$formdata->meetingdone = @$USER->jobtrackerprefs->eventmask & JOBTRACK_EVENT_MEETINGDONE;
$formdata->concluded = @$USER->jobtrackerprefs->eventmask & JOBTRACK_EVENT_CONCLUDED;
$formdata->dead = @$USER->jobtrackerprefs->eventmask & JOBTRACK_EVENT_DEAD;
$formdata->oncomment = @$USER->jobtrackerprefs->eventmask & JOBTRACK_ON_COMMENT;

$mform = new PreferencesForm($url, array('jobtracker' => $jobtracker, 'storedprefs' => $formdata));

$formdata->id = $cm->id;

if ($data = $mform->get_data()) {

    $pref = new StdClass();
    $pref->jobtrackerid = $jobtracker->id;
    $pref->userid = $USER->id;
    $pref->name = 'eventmask';
    $pref->value = $data->open * JOBTRACK_EVENT_OPEN +
                   $data->shortlist * JOBTRACK_EVENT_SHORTLIST +
                   $data->waitingevent * JOBTRACK_EVENT_WAITINGEVENT +
                   $data->torefresh * JOBTRACK_EVENT_TOREFRESH +
                   $data->meetingscheduled * JOBTRACK_EVENT_MEETINGSCHEDULED +
                   $data->meetingdone * JOBTRACK_EVENT_MEETINGDONE +
                   $data->concluded * JOBTRACK_EVENT_CONCLUDED +
                   $data->dead * JOBTRACK_EVENT_DEAD+
                   $data->oncomment * JOBTRACK_ON_COMMENT;
    if (!$oldpref = $DB->get_record('jobtracker_preferences', array('jobtrackerid' => $jobtracker->id, 'userid' => $USER->id, 'name' => 'eventmask'))) {
        if (!$DB->insert_record('jobtracker_preferences', $pref)) {
            print_error('errorcannotsaveprefs', 'jobtracker', $url.'&amp;view=profile');
        }
    } else {
        $pref->id = $oldpref->id;
        if (!$DB->update_record('jobtracker_preferences', $pref)) {
            print_error('errorcannotupdateprefs', 'jobtracker', $url.'&amp;view=profile');
        }
    }
}

$mform->set_data($formdata);
echo $OUTPUT->box_start('', 'jobtracker-preferences-form');
$mform->display();
echo $OUTPUT->box_end();
