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

require('../../../config.php');
require_once($CFG->dirroot.'/mod/jobtracker/locallib.php');

$action = required_param('what', PARAM_TEXT);
$jobid = required_param('jobid', PARAM_INT);

if (!$job = $DB->get_record('jobtracker_job', array('id' => $jobid))) {
    die;
}
if (!$jobtracker = $DB->get_record('jobtracker', array('id' => $job->jobtrackerid))) {
    die;
}
if (!$cm = get_coursemodule_from_instance('jobtracker', $job->jobtrackerid)) {
    die;
}
if (!$course = $DB->get_record('course', array('id' => $jobtracker->course))) {
    mtrace(get_string('coursemisconf'));
    die;
}

require_login($course);

if ($action == 'updatestatus') {
    $STATUSKEYS = jobtracker_get_statuskeys($jobtracker, $cm);
    $statusid = required_param('status', PARAM_INT);
    $oldvalue = $DB->get_field('jobtracker_job', 'status', array('id' => $job->id));
    if ($oldvalue != $statusid) {
        $DB->set_field('jobtracker_job', 'status', $statusid, array('id' => $job->id));

        if ($jobtracker->allownotifications) {
            jobtracker_notifyccs_changestate($job->id, $jobtracker);
        }

        // Log state change.
        $stc = new StdClass;
        $stc->userid = $USER->id;
        $stc->jobid = $job->id;
        $stc->jobtrackerid = $jobtracker->id;
        $stc->timechange = time();
        $stc->statusfrom = $oldvalue;
        $stc->statusto = $statusid;
        $DB->insert_record('jobtracker_state_change', $stc);
    }

    $currentstatus = $STATUSKEYS[$statusid];
    $currentstatuscode = $STATUSCODES[$statusid];

    $str = '';
    $str .= '<div class="jobtracker-status status-'.$currentstatuscode.'">'.$currentstatus.'</div>';

    echo $str;
}