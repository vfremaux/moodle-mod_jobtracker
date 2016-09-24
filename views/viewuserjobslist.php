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
 * A view of owned jobs
 * @package mod-jobtracker
 * @category mod
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @date 02/11/2014
 *
 * Print Bug List
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/jobtracker
}

$renderer = $PAGE->get_renderer('jobtracker');

include_once($CFG->libdir.'/tablelib.php');

$limit = 20;
$page = optional_param('page', 1, PARAM_INT);

if ($page <= 0) {
    $page = 1;
}

list($sql, $sqlcount) = jobtracker_get_listsql($jobtracker, $resolved);
$numrecords = $DB->count_records_sql($sqlcount);

// display list of my jobs

$renderer = $PAGE->get_renderer('jobtracker');

echo $renderer->addjob_button($cm->id);
?>

<form name="manageform" action="view.php" method="post">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="what" value="updatelist" />
<input type="hidden" name="view" value="resolved" />
<?php

/// define table object
$prioritystr = get_string('priority', 'jobtracker');
$jobnumberstr = get_string('jobnumber', 'jobtracker');
$companystr = get_string('company', 'jobtracker');
$timecreatedstr = get_string('timecreated', 'jobtracker');
$statusstr = get_string('status', 'jobtracker');
$watchesstr = get_string('watches', 'jobtracker');
$actionstr = '';

if (!empty($jobtracker->parent)) {
    $transferstr = get_string('transfer', 'jobtracker');
    if (has_capability('mod/jobtracker:viewpriority', $context) && !$resolved) {
        $tablecolumns = array('resolutionpriority', 'id', 'company', 'timecreated', 'status', 'watches', 'transfered', 'action');
        $tableheaders = array("<b>$prioritystr</b>", "<b>$jobnumberstr</b>", "<b>$companystr</b>", "<b>$timecreatedstr</b>", "<b>$statusstr</b>", "<b>$watchesstr</b>", "<b>$transferstr</b>", "<b>$actionstr</b>");
    } else {
        $tablecolumns = array('id', 'company', 'timecreated', 'status', 'watches', 'transfered', 'action');
        $tableheaders = array("<b>$jobnumberstr</b>", "<b>$companystr</b>", "<b>$timecreatedstr</b>", "<b>$statusstr</b>", "<b>$watchesstr</b>", "<b>$transferstr</b>", "<b>$actionstr</b>");
    }
} else {
    if (has_capability('mod/jobtracker:viewpriority', $context) && !$resolved){
        $tablecolumns = array('resolutionpriority', 'id', 'company', 'timecreated', 'status', 'watches',  'action');
        $tableheaders = array("<b>$prioritystr</b>", '', "<b>$companystr</b>", "<b>$timecreatedstr</b>", "<b>$statusstr</b>", "<b>$watchesstr</b>", "<b>$actionstr</b>");
    } else {
        $tablecolumns = array('id', 'company', 'timecreated', 'status', 'watches',  'action');
        $tableheaders = array('', "<b>$companystr</b>", "<b>$timecreatedstr</b>", "<b>$statusstr</b>", "<b>$watchesstr</b>", "<b>$actionstr</b>");
    }
}

$table = new flexible_table('mod-jobtracker-joblist');
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);

$table->define_baseurl(new moodle_url('/mod/jobtracker/view.php', array('id' => $cm->id, 'view' => $view, 'screen' => $screen)));

$table->sortable(true, 'timecreated', SORT_DESC); //sorted by timecreated by default
$table->collapsible(true);
$table->initialbars(true);

// allow column hiding
// $table->column_suppress('userid');
// $table->column_suppress('watches');

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'jobs');
$table->set_attribute('class', 'joblist');
$table->set_attribute('width', '100%');

$table->column_class('resolutionpriority', 'list-priority');
$table->column_class('id', 'list-jobnumber');
$table->column_class('company', 'list-company');
$table->column_class('timecreated', 'timelabel');
$table->column_class('watches', 'list-watches');
$table->column_class('status', 'list-status');
$table->column_class('action', 'list-action');

$table->setup();

/// set list length limits
/*
if ($limit > $numrecords){
    $offset = 0;
}
else{
    $offset = $limit * ($page - 1);
}
$sql = $sql . ' LIMIT ' . $limit . ' OFFSET ' . $offset;
*/

/// get extra query parameters from flexible_table behaviour
$where = $table->get_sql_where();
$sort = $table->get_sql_sort();
$table->pagesize($limit, $numrecords);

if (!empty($sort)) {
    $sql .= " ORDER BY $sort";
}

$jobs = $DB->get_records_sql($sql, null, $table->get_page_start(), $table->get_page_size());
$maxpriority = $DB->get_field_select('jobtracker_job', 'MAX(resolutionpriority)', " jobtrackerid = $jobtracker->id GROUP BY jobtrackerid ");

$FULLSTATUSKEYS = jobtracker_get_statuskeys($jobtracker);
$STATUSKEYS = jobtracker_get_statuskeys($jobtracker, $cm);
$STATUSKEYS[0] = get_string('nochange', 'jobtracker');

if (!empty($jobs)) {
    // product data for table
    $followersmenu = array();
    foreach ($jobs as $job) {
        $joburl = new moodle_url('/mod/jobtracker/view.php', array('id' => $cm->id, 'view' => 'view', 'screen' => 'viewanopportunity', 'jobid' => $job->id));
        $jobnumber = '<a href="'.$jobid.'">'.$jobtracker->ticketprefix.$job->id.'</a>';
        $company = '<a href="'.$joburl.'">'.format_string($job->company).'</a>';
        $timecreated = date('Y/m/d h:i', $job->timecreated);

        if (has_capability('mod/jobtracker:manage', $context)) {
            // Managers can assign bugs.
            $status = $renderer->status_select($jobtracker, $job, true);
        } elseif (has_capability('mod/jobtracker:workon', $context)) {
            $status = $renderer->status_select($jobtracker, $job, true);
            // $status = $FULLSTATUSKEYS[0 + $job->status].'<br/>'.html_writer::select($STATUSKEYS, "status{$job->id}", 0, array(), array('onchange' => "document.forms['manageform'].schanged{$job->id}.value = 1;"));
            $managers = get_users_by_capability($context, 'mod/jobtracker:manage', 'u.id,lastname,firstname', 'lastname');
            foreach($managers as $manager) {
                $managersmenu[$manager->id] = fullname($manager);
            }
            $managersmenu[$USER->id] = fullname($USER);
        } else {
            $status = $renderer->status_select($jobtracker, $job, false);
            // $status = $FULLSTATUSKEYS[0 + $job->status]; 
        }

        $hassolution = $job->status == JOBTRACK_CONCLUDED && !empty($job->resolution);
        $solution = ($hassolution) ? '<img src="'.$OUTPUT->pix_url('solution', 'jobtracker').'" height="15" alt="'.get_string('hassolution','jobtracker').'" />' : '';
        $actions = '';
        if (has_capability('mod/jobtracker:manage', $context) || has_capability('mod/jobtracker:workon', $context)) {
            $editurl = new moodle_url('/mod/jobtracker/editajob.php', array('id' => $cm->id, 'jobid' => $job->id, 'return' => 'browse'));
            $actions = '<a href="'.$editurl.'" title="'.get_string('update').'" ><img src="'.$OUTPUT->pix_url('t/edit').'" /></a>';
        }
        if (has_capability('mod/jobtracker:manage', $context)) {
            $deleteurl = new moodle_url('/mod/jobtracker/view.php', array('id' => $cm->id, 'view' => 'view', 'jobid' => $job->id, 'what' => 'delete'));
            $actions .= '&nbsp;<a href="'.$deleteurl.'" title="'.get_string('delete').'" ><img src="'.$OUTPUT->pix_url('t/delete').'" /></a>';
        }
        // Ergo Report I3 2012 => self list displays owned tickets. Already registered
        if (has_capability('mod/jobtracker:viewpriority', $context) && !$resolved) {
            $ticketpriority = ($job->status < JOBTRACK_CONCLUDED) ? $maxpriority - $job->resolutionpriority + 1 : '';
            $dataset = array($ticketpriority, $jobnumber, $company.' '.$solution, $timecreated, $status, 0 + $job->watches, $actions);
        } else {
            $dataset = array($jobnumber, $company.' '.$solution, $timecreated, $status, 0 + $job->watches, $actions);
        }
        $table->add_data($dataset);
    }
    $table->print_html();

    echo '<center>';
    echo '<p><input type="submit" name="go_btn" value="'.get_string('savechanges').'" /></p>';
    echo '</center>';
} else {
    echo '<br/>';
    echo '<br/>';
    echo $OUTPUT->notification(get_string('nojobs', 'jobtracker'), 'box generalbox', 'notice'); 
}

echo '</form>';
