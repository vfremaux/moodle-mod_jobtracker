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

class mod_jobtracker_renderer extends plugin_renderer_base {

    /**
     * Prints an ajax driver status picker
     */
    function status_select(&$jobtracker, &$job, $changeable = false) {
        global $STATUSKEYS, $OUTPUT, $CFG;

        $FULLSTATUSKEYS = jobtracker_get_statuskeys($jobtracker);
        $JOBTRACK_STATUSCODES = jobtracker_get_statuscodes();

        $currentstatus = $FULLSTATUSKEYS[$job->status];
        $currentstatuscode = $JOBTRACK_STATUSCODES[$job->status];

        $str = '';

        $str .= '<div id="status-select-'.$job->id.'">';
        $str .= '<div class="jobtracker-status status-'.$currentstatuscode.'">'.$FULLSTATUSKEYS[0 + $job->status].'</div>';
        $str .= '</div>';
        $str .= '<br/>';
        $str .= html_writer::select($STATUSKEYS, "status{$job->id}", 0, array(), array('onchange' => 'ajax_send_select_status(\''.$CFG->wwwroot.'\', \''.$job->id.'\', this)'));

        return $str;
    }

    /**
     * Prints an ajax driver status picker
     */
    function status_picker($job, $changeable = false) {
        global $STATUSKEYS, $FULLSTATUSKEYS, $STATUSCODES, $OUTPUT, $CFG;

        $currentstatus = $FULLSTATUSKEYS[$job->status];
        $currentstatuscode = $STATUSCODES[$job->status];

        $str = '';

        $str .= '<ul id="status-picker-'.$job->id.'" class="jobtracker-status-list">';
        $str .= '<li>';
        $str .= '<ul class="dropdown-menu jobtracker">';
        $str .= '<li id="status-picker-current-'.$job->id.'" class="jobtracker-status-'.$currentstatuscode.'"><a href="#">'.$currentstatus.'<img src="'.$OUTPUT->pix_url('arrow', 'jobtracker').'"/></a>';
        $str .= '<ul>';
        foreach ($STATUSKEYS as $statusid => $status) {
            $statuscode = $STATUSCODES[$statusid];
            if ($changeable && ($statusid != $job->status)) {
                $str .= '<li class="jobtracker-status-list-item active jobtracker-status-'.$statuscode.'"><a href="" onclick="ajax_send_status(\''.$CFG->wwwroot.'\', \''.$job->id.'\', \''.$statusid.'\', \''.$statuscode.'\')">'.$FULLSTATUSKEYS[$statusid].'</a></li>';
            } else {
                $selected = ($statusid != $job->status) ? 'selected' : '';
                $str .= '<li class="jobtracker-status-list-item '.$selected.' jobtracker-status-'.$statuscode.'"><div>'.$FULLSTATUSKEYS[$statusid].'</div></li>';
            }
        }
        $str .= '</ul>';
        $str .= '</li>';
        $str .= '</ul>';
        $str .= '</li>';
        $str .= '</ul>';
        // $status = $FULLSTATUSKEYS[0 + $job->status].'<br/>'.html_writer::select($STATUSKEYS, "status{$job->id}", 0, array(), array('onchange' => "document.forms['manageform'].schanged{$job->id}.value = 1;"));

        return $str;
    }

    function addjob_button($cmid) {
        global $USER, $OUTPUT;

        $addurl = new moodle_url('/mod/jobtracker/addjob.php', array('id' => $cmid));

        $str = $OUTPUT->box_start('', 'jobtracker-addjob-button');
        $str .= $OUTPUT->single_button($addurl, get_string('addajob', 'jobtracker'));
        $str .= $OUTPUT->box_end();

        return $str;
    }

    function menus(&$cm, &$jobtracker, $view, $screen, $userid = 0) {
        global $OUTPUT, $DB, $USER;

        if (!$userid) $userid = $USER->id;

        $context = context_module::instance($cm->id);

        if ($userid) {
            $totaljobs = $DB->count_records_select('jobtracker_job', "jobtrackerid = ? AND status <> ".JOBTRACK_CONCLUDED." AND status <> ".JOBTRACK_DEAD." AND userid = ? ", array($jobtracker->id, $userid));
            $totalresolvedjobs = $DB->count_records_select('jobtracker_job', "jobtrackerid = ? AND (status = ".JOBTRACK_CONCLUDED." OR status = ".JOBTRACK_DEAD.") AND userid = ? ", array($jobtracker->id, $userid));
        } else {
            // TODO : need filter against group access constraints
            $totaljobs = $DB->count_records_select('jobtracker_job', "jobtrackerid = ? AND status <> ".JOBTRACK_CONCLUDED." AND status <> ".JOBTRACK_DEAD, array($jobtracker->id));
            $totalresolvedjobs = $DB->count_records_select('jobtracker_job', "jobtrackerid = ? AND (status = ".JOBTRACK_CONCLUDED." OR status = ".JOBTRACK_DEAD.")", array($jobtracker->id));
        }

        // View opportunity list
        $rows[0][] = new tabobject('view', "view.php?id={$cm->id}&amp;view=view", get_string('view', 'jobtracker').' ('.$totaljobs.' '.get_string('jobs','jobtracker').')');

        // View resolved opportunity list
        $rows[0][] = new tabobject('resolved', "view.php?id={$cm->id}&amp;view=resolved", get_string('resolvedplural', 'jobtracker').' ('.$totalresolvedjobs.' '.get_string('jobs','jobtracker').')');

        // User manageable profile.
        if (has_capability('mod/jobtracker:follow', $context)) {
            // Only followers can use subscriptions
            $rows[0][] = new tabobject('profile', "view.php?id={$cm->id}&amp;view=profile", get_string('profile', 'jobtracker'));
        }

        // Activity reports.
        if (has_capability('mod/jobtracker:viewreports', $context)) {
            $rows[0][] = new tabobject('reports', "view.php?id={$cm->id}&amp;view=reports", get_string('reports', 'jobtracker'));
        }
        
        // Activity form configuration.
        if (has_capability('mod/jobtracker:configure', $context)) {
            $rows[0][] = new tabobject('admin', "view.php?id={$cm->id}&amp;view=admin", get_string('administration', 'jobtracker'));
        }

        //
        $myticketsstr = get_string('myjobs', 'jobtracker');

        // Submenus.

        $selected = null;
        $activated = null;
        switch ($view) {
            case 'profile':
                if (!preg_match("/mypreferences|mywatches/", $screen)) {
                    $screen = 'mypreferences';
                }
                $rows[1][] = new tabobject('mypreferences', "view.php?id={$cm->id}&amp;view=profile&amp;screen=mypreferences", get_string('mypreferences', 'jobtracker'));
                $rows[1][] = new tabobject('mywatches', "view.php?id={$cm->id}&amp;view=profile&amp;screen=mywatches", get_string('mywatches', 'jobtracker'));
            break;
            case 'reports':
                if (!preg_match("/followed|status|evolution|print/", $screen)) {
                    $screen = 'followed';
                }
                $rows[1][] = new tabobject('followed', "view.php?id={$cm->id}&amp;view=reports&amp;screen=followed", get_string('myfollowed', 'jobtracker'));
                $rows[1][] = new tabobject('status', "view.php?id={$cm->id}&amp;view=reports&amp;screen=status", get_string('status', 'jobtracker'));
                $rows[1][] = new tabobject('evolution', "view.php?id={$cm->id}&amp;view=reports&amp;screen=evolution", get_string('evolution', 'jobtracker'));
                $rows[1][] = new tabobject('print', "view.php?id={$cm->id}&amp;view=reports&amp;screen=print", get_string('print', 'jobtracker'));
            break;
            case 'admin':
                if (!preg_match("/summary|manageelements/", $screen)) {
                    $screen = 'summary';
                }
                $rows[1][] = new tabobject('summary', "view.php?id={$cm->id}&amp;view=admin&amp;screen=summary", get_string('summary', 'jobtracker'));
                $rows[1][] = new tabobject('manageelements', "view.php?id={$cm->id}&amp;view=admin&amp;screen=manageelements", get_string('manageelements', 'jobtracker'));
                break;
            default:
        }
        if (!empty($screen)) {
            $selected = $screen;
            $activated = array($view);
        } else {
            $selected = $view;
        }
        $str = $OUTPUT->container_start('mod-header');
        $str .= print_tabs($rows, $selected, '', $activated, true);
        $str .= '<br/>';
        $str .= $OUTPUT->container_end();

        return $str;
    }

    function job_abstract(&$job, &$cm) {
        global $OUTPUT;
        
        $context = context_module::instance($cm->id);

        $str = '';

        $str .= $OUTPUT->box($job->position, 'job-position');
        $str .= $OUTPUT->box($job->contact, 'job-contact');
        $notes = file_rewrite_pluginfile_urls($job->notes, 'pluginfile.php', $context->id, 'mod_jobtracker', 'jobnotes', $job->id);
        $str .= $OUTPUT->box(format_text($notes, FORMAT_HTML), 'jobtracker-job-notes');

        return $str;
    }

    function job_body(&$jobtracker, &$cm, $job) {
        global $OUTPUT, $STATUSCODES, $STATUSKEYS;

        $context = context_module::instance($cm->id);
        $str = '';

        $buttons = '';
        if (jobtracker_can_edit($jobtracker, $context, $job)) {
            $editurl = new moodle_url('/mod/jobtracker/editajob.php', array('id' => $cm->id, 'view' => 'view', 'screen' => 'editanopportunity', 'jobid' => $job->id));
            $buttons .= $OUTPUT->single_button($editurl, get_string('turneditingon', 'jobtracker'));
        }
        $listurl = new moodle_url('/mod/jobtracker/view.php', array('id' => $cm->id, 'view' => 'view', 'screen' => 'browse'));
        $buttons .= $OUTPUT->single_button($listurl, get_string('backtolist', 'jobtracker'));
        $str .= '<tr>';
        $str .= '<td colspan="4" align="right">';
        $str .= $buttons;
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr valign="top">';
        $str .= '<td colspan="4" align="left" class="jobtracker-job-company">';
        $str .= format_string($job->company);
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr valign="top">';
        $str .= '<td colspan="4" align="left" class="jobtracker-job-position">';
        $str .= $jobtracker->ticketprefix.$job->id.' : '.$job->position;
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr valign="top">';
        $str .= '<td align="right" class="jobtracker-job-param">';
        $str .= '<b>'.get_string('contact', 'jobtracker').':</b>';
        $str .= '</td>';
        $str .= '<td align="left" class="jobtracker-job-contact">';
        $str .= $job->contact;
        $str .= '</td>';
        $str .= '<td align="left" class="jobtracker-job-contactphone">';
        $str .= $job->contactphone;
        $str .= '</td>';
        $str .= '<td align="left" class="jobtracker-job-contactmail">';
        $str .= $job->contactmail;
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr valign="top">';
        $str .= '<td align="right" width="25%" class="jobtracker-job-param" >';
        $str .= '<b>'.get_string('status', 'jobtracker').':</b>';
        $str .= '</td>';
        $str .= '<td width="25%" class="status-'.$STATUSCODES[$job->status].'">';
        $str .= '<b>'.$STATUSKEYS[$job->status].'</b>';
        $str .= '</td>';
        $str .= '<td align="right" width="25%" class="jobtracker-job-param" >';
        $str .= '<b>'.get_string('timecreated', 'jobtracker').':</b>';
        $str .= '</td>';
        $str .= '<td width="25%">';
        $str .= userdate($job->timecreated);
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr valign="top">';
        $str .= '<td align="right" height="200" class="jobtracker-job-param">';
        $str .= '<b>'.get_string('notes', 'jobtracker').':</b>';
        $str .= '</td>';
        $str .= '<td align="left" colspan="3" width="75%">';
        $str .= format_text($job->notes);
        $str .= '</td>';
        $str .= '</tr>';

        return $str;
    }
    
    function history_detail(&$statehistory, $initialviewmode) {
        global $OUTPUT, $DB, $STATUSKEYS;

        $STATUSCODES = jobtracker_get_statuscodes();

        $str = '';

        $str .= '<tr>';
        $str .= '<td colspan="4" align="center" width="100%">';
        $str .= '<table id="jobhistory" class="'.$initialviewmode.'" width="100%">';
        $str .= '<tr valign="top">';
        $str .= '<td width="50%">';
        $str .= $OUTPUT->heading(get_string('statehistory', 'jobtracker'));
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr>';

        $str .= '<td width="50%">';
        $str .= '<table width="100%">';
        if (!empty($statehistory)) {
            foreach ($statehistory as $state) {
                $bywhom = $DB->get_record('user', array('id' => $state->userid));
                $str .= '<tr valign="top">';
                $str .= '<td align="left">';
                $str .= userdate($state->timechange);
                $str .= '</td>';
                $str .= '<td align="left">';
                $str .= jobtracker_print_user($bywhom);
                $str .= '</td>';
                $str .= '<td align="left">';
                $str .= '<span class="status-'.$STATUSCODES[$state->statusfrom].'">'.$STATUSKEYS[$state->statusfrom].'</span>';
                $str .= '</td>';
                $str .= '<td align="left">';
                $str .= '>>';
                $str .= '</td>';
                $str .= '<td align="left">';
                $str .= '<span class="status-'.$STATUSCODES[$state->statusto].'">'.$STATUSKEYS[$state->statusto].'</span>';
                $str .= '</td>';
                $str .= '</tr>';
            }
        }

        $str .= '</table>';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';

        $str .= '</td>';
        $str .= '</tr>';

        return $str;
    }

    function mentees_tracks(&$jobtracker, &$followed, $options = array()) {
        global $DB, $OUTPUT;

        $menteesstr = get_string('students');
        $opportunitiesstr = get_string('jobs', 'jobtracker');

        $table = new html_table();
        $table->head = array($menteesstr, $opportunitiesstr);
        $table->size = array('20%', '*');
        $table->width = '100%';
        $table->attributes['class'] = 'jobtracker-tracks generaltable';

        foreach ($followed as $f) {
            $followeduser = $DB->get_record('user', array('id' => $f->id));
            $userlinkurl = new moodle_url('/user/view.php', array('id' => $f->id));
            $name = $OUTPUT->user_picture($followeduser).' <a href="'.$userlinkurl.'">'.fullname($f).'</a>';
            $joblist = $this->job_track($jobtracker, $f->id, $options);
            $table->data[] = array($name, '<div class="jobtracker-joblist">'.$joblist.'</div>');
        }

        return html_writer::table($table);
    }

    function job_track(&$jobtracker, $userid, $options = array()) {
        global $DB, $OUTPUT;

        $STATUSKEYS = jobtracker_get_statuskeys(null);
        $STATUSCODES = jobtracker_get_statuscodes();

        $jobs = $DB->get_records('jobtracker_job', array('userid' => $userid, 'jobtrackerid' => $jobtracker->id));
        if (!$jobs) {
            return get_string('nojobs', 'jobtracker');
        } else {
            $str = '';
            $str .= '<div class="fluid-container"><div class="fluid-row">';
            $upurl = $OUTPUT->pix_url('t/up');
            $downurl = $OUTPUT->pix_url('t/down');
            if (empty($options['nochanges'])) {
                $str .= '<div width="1" class="jobtracker-track-controls span2"><img class="tracks-hidden" id="trackctl_'.$userid.'" src="'.$upurl.'" onclick="toggletrackvisibility('.$userid.', \''.$upurl.'\', \''.$downurl.'\')" /></div>';
            }
            $str .= '<div class="jobtracker-track-track span10">';
            foreach ($jobs as $job) {
                $currentstatuscode = $STATUSCODES[$job->status];
                $joblinkurl = new moodle_url('/mod/jobtracker/view.php', array('view' => 'viewanopportunity', 'jobid' => $job->id));
                $trackdivs = '';
                if (empty($options['nochanges'])) {
                    $track = $DB->get_records('jobtracker_state_change', array('jobid' => $job->id), 'timechange');
                    array_shift($track); // remove firstone
                    if (!empty($track)) {
                        foreach ($track as $t) {
                            $elmstatuscode = $STATUSCODES[$t->statusfrom];
                            $trackdivs .= '<div title="'.$STATUSKEYS[$t->statusfrom].' ('.userdate($t->timechange).')" class="jobtracker-track-trackelm status-'.$elmstatuscode.' track-'.$userid.' hidden"></div>';
                        }
                    }
                }
                if (!empty($options['compact'])) {
                    $finalstate = '<div title="'.$STATUSKEYS[$job->status].' ('.$job->company.')" class="jobtracker-track-cell status-'.$currentstatuscode.'">'.$job->id.'</div>';
                    $str .= '<div class="jobtracker-track-stack">'.$finalstate.'</div>';
                } else {
                    $finalstate = '<div title="'.$STATUSKEYS[$job->status].'" class="jobtracker-track-cell status-'.$currentstatuscode.'"><a href="'.$joblinkurl.'">'.$job->id.'</a></div>';
                    $str .= '<div class="jobtracker-track-stack">'.$trackdivs.$finalstate.'</div>';
                }
            }
            $str .= '</div>';
            $str .= '</div></div>';
        }
        
        return $str;
    }
}