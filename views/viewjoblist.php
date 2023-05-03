<?php

/**
 * @package mod-jobtracker
 * @category mod
 * @author Clifford Thamm, Valery Fremaux > 1.8
 * @date 02/12/2007
 *
 * Print Bug List
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/jobtracker
}

require_once($CFG->libdir.'/tablelib.php');

$FULLSTATUSKEYS = jobtracker_get_statuskeys($jobtracker);
$STATUSKEYS = jobtracker_get_statuskeys($jobtracker, $cm);
$STATUSKEYS[0] = get_string('nochange', 'jobtracker');

$limit = 20;
$page = optional_param('page', 1, PARAM_INT);

if ($page <= 0) {
    $page = 1;
}

// Check we display only resolved tickets or working.
if ($resolved) {
    $resolvedclause = " AND
       (status = ".JOBTRACK_CONCLUDED." OR
       status = ".JOBTRACK_DEAD.")
    ";
} else {
    $resolvedclause = " AND
       status <> ".JOBTRACK_CONCLUDED." AND
       status <> ".JOBTRACK_DEAD."
    ";
}

$sql = "
    SELECT
        i.id,
        i.company,
        i.timecreated,
        i.userid,
        i.status,
        i.resolutionpriority,
        u.firstname firstname,
        u.lastname lastname,
        COUNT(ic.jobid) watches
    FROM
        {user} u,
        {jobtracker_job} i
    LEFT JOIN
        {jobtracker_jobcc} ic 
    ON
        ic.jobid = i.id
    WHERE
        i.userid = u.id AND
        i.jobtrackerid = {$jobtracker->id}
        $resolvedclause
    GROUP BY
        i.id,
        i.timecreated,
        i.userid,
        i.status,
        i.resolutionpriority,
        u.firstname,
        u.lastname
";

$sqlcount = "
    SELECT
        COUNT(*)
    FROM
        {jobtracker_job} i, 
        {user} u
    WHERE
        i.userid = u.id AND 
        i.jobtrackerid = ?
        $resolvedclause
";
$numrecords = $DB->count_records_sql($sqlcount, array($jobtracker->id));

// Display list of opportunities.

$renderer = $PAGE->get_renderer('jobtracker');

echo $renderer->addjob_button($cm->id);

echo '<form name="manageform" action="view.php" method="post">';
echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
echo '<input type="hidden" name="what" value="updatelist" />';
echo '<input type="hidden" name="view" value="view" />';
echo '<input type="hidden" name="screen" value="browse" />';

// Define table object.

$prioritystr = get_string('priority', 'jobtracker');
$jobnumberstr = get_string('jobnumber', 'jobtracker');
$companystr = get_string('company', 'jobtracker');
$timecreatedstr = get_string('timecreated', 'jobtracker');
$useridstr = get_string('userid', 'jobtracker');
$statusstr = get_string('status', 'jobtracker');
$watchesstr = get_string('watches', 'jobtracker');
$actionstr = '';

if ($resolved) {
    $tablecolumns = array('id', 'company', 'timecreated', 'userid', 'status', 'watches', 'action');
    $tableheaders = array("<b>$jobnumberstr</b>", "<b>$companystr</b>", "<b>$timecreatedstr</b>", "<b>$useridstr</b>", "<b>$statusstr</b>", "<b>$watchesstr</b>", "<b>$actionstr</b>");
} else {
    $tablecolumns = array('resolutionpriority', 'id', 'company', 'timecreated', 'userid', 'status', 'watches', 'action');
    $tableheaders = array("<b>$prioritystr</b>", "<b>$jobnumberstr</b>", "<b>$companystr</b>", "<b>$timecreatedstr</b>", "<b>$useridstr</b>", "<b>$statusstr</b>", "<b>$watchesstr</b>", "<b>$actionstr</b>");
}

$table = new flexible_table('mod-jobtracker-joblist');
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);

$table->define_baseurl(new moodle_url('/mod/jobtracker/view.php', array('id' => $cm->id, 'view' => $view, 'screen' => $screen)));

$table->sortable(true, 'resolutionpriority', SORT_ASC); //sorted by priority by default
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
$table->column_class('userid', 'list-userid');
$table->column_class('watches', 'list-watches');
$table->column_class('status', 'list-status');
$table->column_class('action', 'list-action');

$table->setup();

// Get extra query parameters from flexible_table behaviour.

$where = $table->get_sql_where();
$sort = $table->get_sql_sort();
$table->pagesize($limit, $numrecords);

if (!empty($sort)) {
    $sql .= " ORDER BY $sort";
} else {
    $sql .= " ORDER BY resolutionpriority ASC";
}

$jobs = $DB->get_records_sql($sql, null, $table->get_page_start(), $table->get_page_size());

$maxpriority = $DB->get_field_select('jobtracker_job', 'MAX(resolutionpriority)', " jobtrackerid = $jobtracker->id GROUP BY jobtrackerid ");

if (!empty($jobs)) {
    // Product data for table.
    foreach ($jobs as $job) {
        $viewjoburl = new moodle_url('view.php', array('id' => $cm->id, 'view' => 'view', 'screen' => 'viewanopportunity', 'jobid' => $job->id));
        $jobnumber = '<a href="'.$viewjoburl.'">'.$jobtracker->ticketprefix.$job->id.'</a>';
        $company = '<a href="'.$viewjoburl.'">'.format_string($job->company).'</a>';
        $timecreated = date('Y/m/d h:i', $job->timecreated);
        $user = $DB->get_record('user', array('id' => $job->userid));
        $userid = fullname($user);
        if (has_capability('mod/jobtracker:manage', $context)) {
            // Managers can assign bugs.
            $status = $FULLSTATUSKEYS[0 + $job->status].'<br/>'.html_writer::select($STATUSKEYS, "status{$job->id}", 0, array('' => 'choose'), array('onchange' => "document.forms['manageform'].schanged{$job->id}.value = 1;")). "<input type=\"hidden\" name=\"schanged{$job->id}\" value=\"0\" />";
        } elseif (has_capability('mod/jobtracker:resolve', $context)) {
            // Resolvers can give an opportunity to managers.
            $status = $FULLSTATUSKEYS[0 + $job->status].'<br/>'.html_writer::select($STATUSKEYS, "status{$job->id}", 0, array('' => 'choose'), array('onchange' => "document.forms['manageform'].schanged{$job->id}.value = 1;")) . "<input type=\"hidden\" name=\"schanged{$job->id}\" value=\"0\" />";
        } else {
            $status = $FULLSTATUSKEYS[0 + $job->status]; 
        }
        $status = '<div class="status-'.$STATUSCODES[$job->status].'" style="width: 110%; height: 105%; text-align:center">'.$status.'</div>';
        $hassolution = $job->status == JOBTRACK_CONCLUDED && !empty($job->resolution);
        $solution = ($hassolution) ? $OUTPUT->pix_icon('solution', get_string('hassolution','jobtracker'), 'jobtracker') : '';
        $actions = '';
        if (has_capability('mod/jobtracker:manage', $context) || has_capability('mod/jobtracker:resolve', $context)) {
            $editurl = new moodle_url('/mod/jobtracker/editajob.php', array('id' => $cm->id, 'jobid' => $job->id, 'return' => 'browse'));
            $actions = '<a href="'.$editurl.'">'.$OUTPUT->pix_icon('t/edit', get_string('update')).'</a>';
        }
        if (has_capability('mod/jobtracker:manage', $context)) {
            $deleteurl = new moodle_url('/mod/jobtracker/view.php', array('id' => $cm->id, 'jobid' => $job->id, 'what' => 'delete'));
            $actions .= '&nbsp;<a href="'.$deleteurl.'">'.$OUTPUT->pix_icon('t/delete', get_string('delete')).'</a>';
        }
        if (!$DB->get_record('jobtracker_jobcc', array('userid' => $USER->id, 'jobid' => $job->id))) {
            $registerurl = new moodle_url('/mod/tracker/view.php', array('id' => $cm->id, 'view' => 'profile', 'screen' => $screen, 'jobid' => $job->id, 'what' => 'register'));
            $actions .= '&nbsp;<a href="'.$registerurl.'">'.$OUTPUT->pix_icon('register', get_string('register', 'jobtracker'), 'mod_jobtracker').'</a>';
        }
        if ($resolved) {
            $dataset = array($jobnumber, $company.' '.$solution, $timecreated, $userid, $status, 0 + $job->watches, $actions);
        } else {
            $dataset = array($maxpriority - $job->resolutionpriority + 1, $jobnumber, $company.' '.$solution, $timecreated, $userid, $status, 0 + $job->watches, $actions);
        }
        $table->add_data($dataset);
    }
    $table->print_html();
    echo '<br/>';
    if (jobtracker_can_workon($jobtracker, $context)) {
        echo '<center>';
        echo '<p><input type="submit" name="go_btn" value="'.get_string('savechanges').'" /></p>';
        echo '</center>';
    }
} else {
    if (!$resolved) {
        echo '<br/>';
        echo '<br/>';
        echo $OUTPUT->notification(get_string('nojobsreported', 'jobtracker'), 'box generalbox', 'notice'); 
    } else {
        echo '<br/>';
        echo '<br/>';
        echo $OUTPUT->notification(get_string('nojobsconcluded', 'jobtracker'), 'box generalbox', 'notice'); 
    }
}

echo '</form>';