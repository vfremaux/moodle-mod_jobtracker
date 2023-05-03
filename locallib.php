<?php

/**
 * @package mod-jobtracker
 * @category mod
 * @author Valery Fremaux
 * @date 02/12/2007
 *
 * Library of internal functions and constants for module jobtracker
 */
require_once($CFG->dirroot.'/lib/uploadlib.php');
require_once($CFG->dirroot.'/mod/jobtracker/mailtemplatelib.php');

// Statusses.

define('JOBTRACK_POSTED', 0);
define('JOBTRACK_OPEN', 1);
define('JOBTRACK_SHORTLIST', 2);
define('JOBTRACK_WAITINGEVENT', 3);
define('JOBTRACK_TOREFRESH', 4);
define('JOBTRACK_MEETINGSCHEDULED', 5);
define('JOBTRACK_MEETINGDONE', 6);
define('JOBTRACK_CONCLUDED', 7);
define('JOBTRACK_DEAD', 8);

global $STATUSLABELS;
$STATUSLABELS = array(
    JOBTRACK_POSTED => 'posted',
    JOBTRACK_OPEN => 'open',
    JOBTRACK_SHORTLIST => 'shortlist',
    JOBTRACK_WAITINGEVENT => 'waitingevents',
    JOBTRACK_TOREFRESH => 'torefresh',
    JOBTRACK_MEETINGSCHEDULED => 'meetingscheduled',
    JOBTRACK_MEETINGDONE => 'meetingdone',
    JOBTRACK_CONCLUDED => 'concluded',
    JOBTRACK_DEAD => 'dead');

// Statusses.

define('JOBTRACK_ENABLED_POSTED', 1);
define('JOBTRACK_ENABLED_OPEN', 2);
define('JOBTRACK_ENABLED_SHORTLIST', 4);
define('JOBTRACK_ENABLED_WAITINGEVENT', 8);
define('JOBTRACK_ENABLED_TOREFRESH', 16);
define('JOBTRACK_ENABLED_MEETINGSCHEDULED', 32);
define('JOBTRACK_ENABLED_MEETINGDONE', 64);
define('JOBTRACK_ENABLED_CONCLUDED', 128);
define('JOBTRACK_ENABLED_DEAD', 256);

// major roles against status keys
function jobtracker_get_role_definition(&$jobtracker, $role) {
    if ($role == 'report') {
        // a reporter (teacher) can add)
        return JOBTRACK_ENABLED_POSTED;
    } elseif ($role == 'develop') {
        // an opportunityowner (student) can do anything unless post)
        return JOBTRACK_ENABLED_OPEN | JOBTRACK_ENABLED_SHORTLIST | JOBTRACK_ENABLED_WAITINGEVENT | JOBTRACK_ENABLED_TOREFRESH | JOBTRACK_ENABLED_METTINGSCHEDULED | MEETING_DONE | JOBTRACK_ENABLED_CONCLUDED | JOBTRACK_ENABLED_DEAD;
    } elseif ($role == 'manage') {
        return JOBTRACK_ENABLED_POSTED | JOBTRACK_ENABLED_OPEN | JOBTRACK_ENABLED_SHORTLIST | JOBTRACK_ENABLED_WAITINGEVENT | JOBTRACK_ENABLED_TOREFRESH | JOBTRACK_ENABLED_METTINGSCHEDULED | MEETING_DONE | JOBTRACK_ENABLED_CONCLUDED | JOBTRACK_ENABLED_DEAD;
    }
    return 0;
}

// States && eventmasks.
define('JOBTRACK_EVENT_POSTED', 1);
define('JOBTRACK_EVENT_OPEN', 2);
define('JOBTRACK_EVENT_SHORTLIST', 4);
define('JOBTRACK_EVENT_WAITINGEVENT', 8);
define('JOBTRACK_EVENT_TOREFRESH', 16);
define('JOBTRACK_EVENT_MEETINGSCHEDULED', 32);
define('JOBTRACK_EVENT_MEETINGDONE', 64);
define('JOBTRACK_EVENT_CONCLUDED', 128);
define('JOBTRACK_EVENT_DEAD', 256);
define('JOBTRACK_ON_COMMENT', 1024);

define('JOBTRACK_ALL_EVENTS', 2047);

global $STATUSCODES;
global $STATUSKEYS;
$STATUSCODES = array(
    JOBTRACK_POSTED => 'posted',
    JOBTRACK_OPEN => 'open',
    JOBTRACK_SHORTLIST => 'shortlist',
    JOBTRACK_WAITINGEVENT => 'waitingevent',
    JOBTRACK_TOREFRESH => 'torefresh',
    JOBTRACK_MEETINGSCHEDULED => 'meetingscheduled',
    JOBTRACK_MEETINGDONE => 'meetingdone',
    JOBTRACK_CONCLUDED => 'concluded',
    JOBTRACK_DEAD => 'dead',
);

/**
 * loads all elements in memory
 * @uses $CFG
 * @uses $COURSE
 * @param reference $jobtracker the jobtracker object
 * @param reference $elementsobj
 */
function jobtracker_loadelements(&$jobtracker, &$elementsobj) {
    global $COURSE, $CFG, $DB;

    // First get shared elements.
    $elements = $DB->get_records('jobtracker_element', array('course' => 0));
    if (!$elements) $elements = array();

    // Get course scope elements.
    $courseelements = $DB->get_records('jobtracker_element', array('course' => $COURSE->id));
    if ($courseelements){
        $elements = array_merge($elements, $courseelements);
    }

    // Make a set of element objet with records.
    if (!empty($elements)) {
        foreach ($elements as $element) {
            // This get the options by the constructor.
            include_once($CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/'.$element->type.'/'.$element->type.'.class.php');
            $constructorfunction = "{$element->type}element";
            $elementsobj[$element->id] = new $constructorfunction($jobtracker, $element->id);
            $elementsobj[$element->id]->name = $element->name;
            $elementsobj[$element->id]->description = $element->description;
            $elementsobj[$element->id]->type = $element->type;
            $elementsobj[$element->id]->course = $element->course;
        }
    }
}

/**
 * get all available types which are plugins in classes/jobtrackercategorytype
 * @uses $CFG
 * @return an array of known element types
 */
function jobtracker_getelementtypes() {
    global $CFG;

    $typedir = $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype';
    $DIR = opendir($typedir);
    while ($entry = readdir($DIR)) {
        if (strpos($entry, '.') === 0) {
            continue;
        }
        if ($entry == 'CVS') {
            continue;
        }
        if (!is_dir("$typedir/$entry")) {
            continue;
        }
        $types[] = $entry;
    }
    return $types;
}

/**
 * tells if at least one used element is a file element
 * @param int $jobtrackerid the current jobtracker
 */
function jobtracker_requiresfile($jobtrackerid) {
    global $DB;

    $sql = "
        SELECT 
            COUNT(*)
        FROM 
            {jobtracker_element} e,
            {jobtracker_elementused} eu
        WHERE 
            eu.elementid = e.id AND 
            eu.jobtrackerid = {$jobtrackerid} AND
            e.type = 'file'
    ";
    $count = $DB->count_records_sql($sql);
    return $count;
}

/**
 * loads elements as objects array in a reference
 * @param int $jobtrackerid the current jobtracker
 * @param reference $used a reference to an array of used elements
 */
function jobtracker_loadelementsused(&$jobtracker, &$used) {
    global $CFG, $DB;

    $cm = get_coursemodule_from_instance('jobtracker', $jobtracker->id);
    $context = context_module::instance($cm->id);

    $usedelements = $DB->get_records('jobtracker_elementused', array('jobtrackerid' => $jobtracker->id), 'sortorder', 'id,elementid,sortorder');
    $used = array();
    $sortorder = 1;
    if (!empty($usedelements)) {
        foreach ($usedelements as $ueid => $ue) {
            // normalize sortorder indexes
            if ($ue->sortorder != $sortorder) {
                $ue->sortorder = $sortorder;
                $DB->update_record('jobtracker_elementused', $ue);
            }
            $used[$ue->elementid] = jobtrackerelement::find_instance_by_usedid($jobtracker, $ueid);
            $used[$ue->elementid]->setcontext($context);
            $sortorder++;
        }
    }
}

/**
 * quite the same as above, but not loading objects, and
 * mapping hash keys by "name"
 * @param int $jobtrackerid
 *
 */
function jobtracker_getelementsused_by_name(&$jobtracker) {
    global $DB;

    $sql = "
        SELECT 
            e.name,
            e.description,
            e.type,
            eu.id AS usedid,
            eu.sortorder, 
            eu.jobtrackerid, 
            eu.canbemodifiedby, 
            eu.active
        FROM 
            {jobtracker_element} e,
            {jobtracker_elementused} eu
        WHERE 
            eu.elementid = e.id AND 
            eu.jobtrackerid = {$jobtracker->id}
        ORDER BY 
            eu.sortorder ASC
    ";
    if (!$usedelements = $DB->get_records_sql($sql)) {
        return array();
    }
    return $usedelements;
}

/**
 * checks if an element is used somewhere in the jobtracker. It must be in used list
 * @param int $jobtrackerid the current jobtracker
 * @param int $elementid the element
 * @return boolean
 */
function jobtracker_iselementused($jobtrackerid, $elementid) {
    global $DB;

    $unusedelements = $DB->count_records_select('jobtracker_elementused', 'elementid = ' . $elementid . ' AND jobtrackerid = ' . $jobtrackerid);  
    return $unusedelements;
}

/**
 * print additional user defined elements in several contexts
 * @param int $jobtrackerid the current jobtracker
 * @param array $fields the array of fields to be printed
 */
function jobtracker_printelements(&$jobtracker, $fields = null, $dest = false) {
    jobtracker_loadelementsused($jobtracker, $used);

    if (!empty($used)) {
        if (!empty($fields)) {
            foreach ($used as $element) {
                if (isset($fields[$element->id])) {
                    foreach ($fields[$element->id] as $value) {
                        $element->value = $value;
                    }
                }
            }
        }
        foreach ($used as $element) {

            if (!$element->active) {
                continue;
            }

            echo '<tr>';
            echo '<td align="right" valign="top">';
            echo '<b>' . format_string($element->description) . ':</b>';
            echo '</td>';
            echo '<td align="left" colspan="3">';
            if ($dest == 'search'){
                if ($element->type == 'file') {
                    continue;
                }
                $element->viewsearch();
            } elseif ($dest == 'query') {
                if ($element->type == 'file') {
                    continue;
                }
                $element->viewquery();
            } else {
                $element->view(true);
            }
            echo '</td>';
            echo '</tr>';
        }
    }
}

/**
 * get how many jobs in this jobtracker
 * @uses $CFG
 * @param int $jobtrackerid
 * @param int $status if status is positive or null, filters by status
 * @param int $userid if defined, limits to one user
 */
function jobtracker_getnumjobsreported($jobtrackerid, $status='*', $userid = '*', $groupid = '*') {
    global $CFG, $DB;

    $statusClause = ($status !== '*') ? " AND j.status = $status " : '';
    $ownerClause = ($userid != '*') ? " AND j.userid = $userid " : '';
    $groupjoin = ($groupid != '*') ? " JOIN {groups_members} gm ON gm.userid = j.userid " : '';
    $groupClause = ($groupid != '*') ? " AND gm.groupid = $userid " : '';

    $sql = "
        SELECT
            COUNT(DISTINCT(j.id))
        FROM
            {jobtracker_job} j
            $groupjoin
        WHERE
            j.jobtrackerid = {$jobtrackerid}
            $statusClause
            $ownerClause
            $groupClause
    ";
    return $DB->count_records_sql($sql);
}

// User related.

/**
 * get available managers/jobtracker administrators
 * @param object $context
 */
function jobtracker_getadministrators($context) {
    return get_users_by_capability($context, 'mod/jobtracker:manage', 'u.id,'.get_all_user_name_fields(true, 'u').',picture,email', 'lastname', '', '', '', '', false);
}

/**
 * get available followers (teachers)
 * @param object $context
 */
function jobtracker_getavailablefollowers($context) {
    return get_users_by_capability($context, 'mod/jobtracker:follow', 'u.id,'.get_all_user_name_fields(true, 'u').',picture,email', 'lastname', '', '', '', '', false);
}

/**
 * get available followers (teachers)
 * @param object $context
 */
function jobtracker_getfollowers($jobtracker) {
    global $DB;

    $cm = get_coursemodule_from_instance('jobtracker', $jobtracker->id);
    $context = context_module::instance($cm->id);

    return get_users_by_capability($context, 'mod/jobtracker:follow', 'u.id,'.get_all_user_name_fields(true, 'u'));
}

/**
 * get actual reporters from records
 * @uses $CFG
 * @param int $jobtrackerid
 */
function jobtracker_getreporters($jobtrackerid) {
    global $CFG, $DB;

    $sql = "
        SELECT
            DISTINCT(userid) AS id,
            u.firstname,
            u.lastname,
            u.imagealt
        FROM
            {jobtracker_job} j,
            {user} u
        WHERE
            j.userid = u.id AND
            j.jobtrackerid = ?
    ";
    return $DB->get_records_sql($sql, array($jobtrackerid));
}

/**
 * submits an job in the current jobtracker
 * @uses $CFG
 * @param int $jobtrackerid the current jobtracker
 */
function jobtracker_submitanopportunity(&$jobtracker, &$data) {
    global $CFG, $DB, $USER;

    $job = new StdClass();
    $job->timecreated = time();
    $job->company = $data->company;
    $job->contact = $data->contact;
    $job->contactphone = $data->contactphone;
    $job->contactmail = ''.$data->contactmail;
    $job->position = $data->position;
    $job->notes = $data->notes['text'];
    $job->jobtrackerid = $jobtracker->id;
    $job->status = $data->status;
    $job->userid = $data->userid;
    $job->notesformat = FORMAT_HTML;

    // Fetch max actual priority.
    $maxpriority = $DB->get_field_select('jobtracker_job', 'MAX(resolutionpriority)', " jobtrackerid = ? GROUP BY jobtrackerid ", array($jobtracker->id));
    $job->resolutionpriority = $maxpriority + 1;

    if ($job->id = $DB->insert_record('jobtracker_job', $job)) {
        $data->jobid = $job->id;
        jobtracker_recordelements($job, $data);

        // If not CCed, the assignee should be.
        jobtracker_register_cc($jobtracker, $job, $job->userid);
        return $job;
    } else {
        print_error('errorrecordjob', 'jobtracker');
    }
}

/**
 * fetches all jobs a user is assigned to follow
 * @uses $USER
 * @param int $jobtrackerid the current jobtracker
 * @param int $userid an eventual userid
 */
function jobtracker_getownedjobstofollow($jobtrackerid, $userid = null) {
    global $USER, $DB;

    if (empty($userid)) {
        $userid = $USER->id;
    }
    return $DB->get_records_select('jobtracker_job', " jobtrackerid = ? AND followedby = ? ", array($jobtrackerid, $userid));
}

/**
 * stores in database the element values
 * @uses $CFG
 * @param object $job
 * @param object $data full form return
 */
function jobtracker_recordelements(&$job, &$data) {
    global $CFG, $COURSE, $DB , $PAGE;

    $jobtracker = $DB->get_record('jobtracker', array('id' => $job->jobtrackerid));
    $usedelements = $DB->get_records('jobtracker_elementused', array('jobtrackerid' => $job->jobtrackerid), 'id', 'id,elementid');
    foreach ($usedelements as $ueid => $ue) {
        $ueinstance = jobtrackerelement::find_instance_by_usedid($jobtracker, $ueid);
        $ueinstance->setcontext($PAGE->context);
        $ueinstance->formprocess($data);
    }
}

/**
 * clears element recordings for a job
 * @see view.controller.php / updatejob
 * @param int $jobid the job
 * @param int $withfiles if true, the attached files will be deleted too (full deletion)
 */
function jobtracker_clearelements($jobid, $withfiles = false) {
    global $CFG, $COURSE, $DB;

    if (!$job = $DB->get_record('jobtracker_job', array('id' => "$jobid"))) {
        return;
    }

    $attributeids = $DB->get_records('jobtracker_jobattribute', array('jobid' => $jobid), 'id', 'id,id');

    if (!$DB->delete_records('jobtracker_jobattribute', array('jobid' => $jobid))){
        print_error('errorcannotlearelementsforjob', 'jobtracker', $jobid);
    }

    // Delete job attribute fields.
    if ($withfiles && !empty($attributeids)) {
        $fs = get_file_storage();
        foreach($attributeids as $attid => $void) {
            $fs->delete_area_files($context->id, 'mod_jobtracker', 'jobattribute', $attid);
        }
    }
}

/**
 * adds an error css marker in case of matching error
 * @param array $errors the current error set
 * @param string $errorkey 
 */
if (!function_exists('print_error_class')) {
    function print_error_class($errors, $errorkeylist) {
        if ($errors) {
            foreach ($errors as $anError) {
                if ($anError->on == '') continue;
                if (preg_match("/\\b{$anError->on}\\b/" ,$errorkeylist)) {
                    echo " class=\"formerror\" ";
                    return;
                }
            }
        }
    }
}

/**
 * registers a user as cced for an job in a jobtracker
 * @param reference $jobtracker the current jobtracker
 * @param reference $job the job to watch
 * @param int $userid the cced user's ID
 */
function jobtracker_register_cc(&$jobtracker, &$job, $userid) {
    global $DB;

    if ($userid && !$DB->get_record('jobtracker_jobcc', array('jobtrackerid' => $jobtracker->id, 'jobid' => $job->id, 'userid' => $userid))) {
        // Add new the assignee as new CC !!
        // We do not discard the old one as he may be still concerned.
        $eventmask = 127;
        if ($userprefs = $DB->get_record('jobtracker_preferences', array('jobtrackerid' => $jobtracker->id, 'userid' => $userid, 'name' => 'eventmask'))) {
            $eventmask = $userprefs->value;
        }
        $cc = new StdClass;
        $cc->jobtrackerid = $jobtracker->id;
        $cc->jobid = $job->id;
        $cc->userid = $userid;
        $cc->events = $eventmask;
        $DB->insert_record('jobtracker_jobcc', $cc);
    }
}

/**
 * a local version of the print user command that fits  better to the jobtracker situation
 * @uses $COURSE
 * @uses $CFG
 * @param object $user the user record
 */
function jobtracker_print_user($user) {
    global $COURSE, $CFG, $OUTPUT;

    $str = '';

    if ($user) {
        $str .= $OUTPUT->user_picture ($user, array('courseid' => $COURSE->id, 'size' => 25));
        if ($CFG->messaging) {
            $userurl = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $COURSE->id));
            $str .= '&nbsp;<a href="'.$userurl.'">'.fullname($user).'</a> ';
            $jshandler = 'this.target=\'message\'; return openpopup(\'/message/discussion.php?id='.$user->id.'\', \'message\', \'menubar=0,location=0,scrollbars,status,resizable,width=400,height=500\', 0);';
            $str .= '<a href="'.$userurl.'" onclick="'.$jshandler.'" >'.$OUTPUT->pix_icon('t/message', '', 'core').'</a>';
        } elseif (!$user->emailstop && $user->maildisplay) {
            $userurl = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $COURSE->id));
            $str .= '&nbsp;<a href="'.$userurl.'">'.fullname($user).'</a> ';
            $str .= '<a href="mailto:'.$user->email.'">'.$OUTPUT->pix_icon('t/mail', '', 'core').'</a>';
        } else {
            $str .= '&nbsp;'.fullname($user);
        }
    }
    return $str;
}

/**
 * prints comments for the given job
 * @uses $CFG
 * @param int $jobid
 */
function jobtracker_printcomments($jobid) {
    global $CFG, $DB;

    $comments = $DB->get_records('jobtracker_jobcomment', array('jobid' => $jobid), 'datecreated');
    if ($comments) {
        foreach ($comments as $comment) {
            $user = $DB->get_record('user', array('id' => $comment->userid));
            echo '<tr>';
            echo '<td valign="top" class="commenter" width="30%">';
            jobtracker_print_user($user);
            echo '<br/>';
            echo '<span class="timelabel">'.userdate($comment->datecreated).'</span>';
            echo '</td>';
            echo '<td colspan="3" valign="top" align="left" class="comment">';
            echo $comment->comment;
            echo '</td>';
            echo '</tr>';
        }
    }
}

/**
 * get list of possible parents. Note that none can be in the subdependancies.
 * @uses $CFG
 * @param int $jobtrackerid
 * @param int $jobid
 */
function jobtracker_getpotentialdependancies($jobtrackerid, $jobid) {
    global $CFG, $DB;

    $subtreelist = jobtracker_get_subtree_list($jobtrackerid, $jobid);
    $subtreeClause = (!empty($subtreelist)) ? "AND i.id NOT IN ({$subtreelist}) " : '' ;

    $sql = "
       SELECT
          j.id,
          id.parentid,
          id.childid as isparent,
          company
       FROM
          {jobtracker_job} j
       LEFT JOIN
          {jobtracker_jobdependancy} id
       ON
          j.id = id.parentid
       WHERE
          j.jobtrackerid = {$jobtrackerid} AND
          ((id.childid IS NULL) OR (id.childid = $jobid)) AND
          ((id.parentid != $jobid) OR (id.parentid IS NULL)) AND
          j.id != $jobid 
          $subtreeClause
       GROUP BY 
          j.id, 
          id.parentid, 
          id.childid, 
          company
    ";
    return $DB->get_records_sql($sql);
}

/**
 * get the full list of dependencies in a tree // revamped from techproject/treelib.php
 * @param table the table-tree
 * @param id the node from where to start of
 * @return a comma separated list of nodes
 */
function jobtracker_get_subtree_list($jobtrackerid, $id) {
    global $DB;

    $res = $DB->get_records_menu('jobtracker_jobdependancy', array('parentid' => $id), '', 'id,childid');
    $ids = array();
    if (is_array($res)) {
        foreach (array_values($res) as $aSub) {
            $ids[] = $aSub;
            $subs = jobtracker_get_subtree_list($jobtrackerid, $aSub);
            if (!empty($subs)) $ids[] = $subs;
        }
    }
    return(implode(',', $ids));
}

/**
 * prints all childs of an job treeshaped
 * @uses $CFG
 * @uses $STATUSCODES
 * @uses $STATUS KEYS
 * @param object $jobtracker 
 * @param int $jobid 
 * @param boolean $return if true, returns the HTML, prints it to output elsewhere
 * @param int $indent the indent value
 * @return the HTML
 */
function jobtracker_printchilds(&$jobtracker, $jobid, $return = false, $indent = '') {
    global $CFG, $STATUSCODES, $STATUSKEYS, $DB;

    $str = '';
    $sql = "
       SELECT
          childid,
          company,
          status
       FROM
          {jobtracker_jobdependancy} id,
          {jobtracker_job} j
       WHERE
          j.id = id.childid AND
          id.parentid = {$jobid} AND
          j.jobtrackerid = {$jobtracker->id}
    ";
    $res = $DB->get_records_sql($sql);
    if ($res) {
        foreach ($res as $aSub) {
            $suburl = new moodle_url('/mod/jobtracker/view.php', array('a' => $jobtracker->id, 'view' => 'view', 'screen' => 'viewanopportunity', 'jobid' => $aSub->childid));
            $str .= '<span style="position : relative; left : '.$indent.'px"><a href="'.$suburl.'">'.$jobtracker->ticketprefix.$aSub->childid.' - '.format_string($aSub->company)."</a>";
            $str .= "&nbsp;<span class=\"status_".$STATUSCODES[$aSub->status]."\">".$STATUSKEYS[$aSub->status]."</span></span><br/>\n";
            $indent = $indent + 20;
            $str .= jobtracker_printchilds($jobtracker, $aSub->childid, true, $indent);
            $indent = $indent - 20;
        }
    }
    if ($return) {
        return $str;
    }
    echo $str;
}

/**
 * return watch list for a user
 * @uses $CFG
 * @param int jobtrackerid the current jobtracker
 * @param int userid the user
 */
function jobtracker_getwatches($jobtrackerid, $userid) {
    global $CFG, $DB;

    $sql = "
        SELECT
            w.*,
            j.company
        FROM
            {jobtracker_jobcc} w,
            {jobtracker_job} j
        WHERE
            w.jobid = j.id AND
            j.jobtrackerid = ? AND
            w.userid = ?
    ";
    $watches = $DB->get_records_sql($sql, array($jobtrackerid, $userid));
    if ($watches) {
        foreach ($watches as $awatch) {
            $people = $DB->count_records('jobtracker_jobcc', array('jobid' => $awatch->jobid));
            $watches[$awatch->id]->people = $people;
        }
    }
    return $watches;
}

/**
 * sends required notifications by the watchers when first submit
 * @uses $COURSE
 * @param object $job
 * @param object $cm
 * @param object $jobtracker
 */
function jobtracker_notify_submission($job, &$cm, $jobtracker = null) {
    global $COURSE, $SITE, $CFG, $USER, $DB;

    if (empty($jobtracker)) {
        // Database access optimization in case we have a jobtracker from somewhere else.
        $jobtracker = $DB->get_record('jobtracker', array('id' => $job->jobtrackerid));
    }
    $context = context_module::instance($cm->id);
    // Todo : restrict to same group
    $managers = get_users_by_capability($context, 'mod/jobtracker:follow', 'u.id,'.get_all_user_name_fields(true, 'u').',lang,email,emailstop,mailformat,mnethostid', 'lastname,firstname');

    $by = $DB->get_record('user', array('id' => $job->userid));
    if (!empty($followers)) {
        $vars = array('COURSE_SHORT' => $COURSE->shortname, 
                      'COURSENAME' => format_string($COURSE->fullname), 
                      'JOBTRACKERNAME' => format_string($jobtracker->name), 
                      'JOB' => $jobtracker->ticketprefix.$job->id, 
                      'COMPANY' => format_string($job->company), 
                      'CONTACT' => format_string($job->contact),
                      'CONTACTPHONE' => format_string($job->contactphone),
                      'CONTACTMAIL' => format_string($job->contactmail),
                      'POSITION' => format_string($job->position),
                      'BY' => fullname($by),
                      'JOBURL' => $CFG->wwwroot."/mod/jobtracker/view.php?a={$jobtracker->id}&amp;view=view&amp;screen=viewanopportunity&amp;jobid={$job->id}",
                      'CCURL' => $CFG->wwwroot."/mod/jobtracker/view.php?a={$jobtracker->id}&amp;view=profile&amp;screen=mywatches&amp;jobid={$job->id}&amp;what=register"
                      );
        include_once($CFG->dirroot."/mod/jobtracker/mailtemplatelib.php");
        foreach ($managers as $manager) {
            $notification = jobtracker_compile_mail_template('submission', $vars, 'jobtracker', $manager->lang);
            $notification_html = jobtracker_compile_mail_template('submission_html', $vars, 'jobtracker', $manager->lang);
            if ($CFG->debugsmtp) {
                echo "Sending Submission Mail Notification to " . fullname($manager) . '<br/>'.$notification_html;
            }
            email_to_user($manager, $USER, get_string('submission', 'jobtracker', $SITE->shortname.':'.format_string($jobtracker->name)), $notification, $notification_html);
        }
    }
}

/**
 * sends required notifications by the watchers when first submit
 * @uses $COURSE
 * @param object $job
 * @param object $cm
 * @param object $jobtracker
 */
function jobtracker_notify_proposal($job, &$cm, $jobtracker = null) {
    global $COURSE, $SITE, $CFG, $USER, $DB;

    if (empty($jobtracker)) {
        // Database access optimization in case we have a jobtracker from somewhere else.
        $jobtracker = $DB->get_record('jobtracker', array('id' => $job->jobtrackerid));
    }
    $context = context_module::instance($cm->id);
    $followers = get_users_by_capability($context, 'mod/jobtracker:follow', 'u.id,'.get_all_user_name_fields(true, 'u').',lang,email,emailstop,mailformat,mnethostid', 'lastname,firstname');

    $by = $DB->get_record('user', array('id' => $job->userid));
    $vars = array(
        'COURSE_SHORT' => $COURSE->shortname, 
        'COURSENAME' => format_string($COURSE->fullname), 
        'JOBTRACKERNAME' => format_string($jobtracker->name), 
        'JOB' => $jobtracker->ticketprefix.$job->id, 
        'COMPANY' => format_string($job->company), 
        'CONTACT' => format_string($job->contact),
        'CONTACTPHONE' => format_string($job->contactphone),
        'CONTACTMAIL' => format_string($job->contactmail),
        'POSITION' => format_string($job->position),
        'BY' => fullname($by),
        'JOBURL' => $CFG->wwwroot."/mod/jobtracker/view.php?a={$jobtracker->id}&amp;view=view&amp;screen=viewanopportunity&amp;jobid={$job->id}",
        'CCURL' => $CFG->wwwroot."/mod/jobtracker/view.php?a={$jobtracker->id}&amp;view=profile&amp;screen=mywatches&amp;jobid={$job->id}&amp;what=register"
    );

    $targetuser = $DB->get_record('user', array('id' => $job->userid));
    include_once($CFG->dirroot.'/mod/jobtracker/mailtemplatelib.php');
    $notification = jobtracker_compile_mail_template('submission', $vars, 'jobtracker', $targetuser->lang);
    $notification_html = jobtracker_compile_mail_template('submission_html', $vars, 'jobtracker', $targetuser->lang);
    if ($CFG->debugsmtp) {
        echo "Sending Submission Mail Notification to ".fullname($targetuser).'<br/>'.$notification_html;
    }
    email_to_user($targetuser, $USER, get_string('proposal', 'jobtracker', $SITE->shortname.':'.format_string($jobtracker->name)), $notification, $notification_html);
}

/**
 * sends required notifications by the watchers when state changes
 * @uses $COURSE
 * @param int $jobid
 * @param object $jobtracker
 */
function jobtracker_notifyccs_changestate($jobid, $jobtracker = null) {
    global $COURSE, $SITE, $CFG, $USER, $DB;

    $job = $DB->get_record('jobtracker_job', array('id' => $jobid));
    if (empty($jobtracker)) {
        // Database access optimization in case we have a jobtracker from somewhere else.
        $jobtracker = $DB->get_record('jobtracker', array('id' => $job->jobtrackerid));
    }
    $jobccs = $DB->get_records('jobtracker_jobcc', array('jobid' => $jobid));

    if (!empty($jobccs)) { 
        $vars = array('COURSE_SHORT' => $COURSE->shortname, 
                      'COURSENAME' => format_string($COURSE->fullname), 
                      'JOBTRACKERNAME' => format_string($jobtracker->name), 
                      'JOB' => $jobtracker->ticketprefix.$jobid, 
                      'COMPANY' => format_string($job->company), 
                      'BY' => fullname($USER),
                      'JOBURL' => $CFG->wwwroot."/mod/jobtracker/view.php?a={$jobtracker->id}&amp;view=view&amp;screen=viewanopportunity&amp;jobid={$jobid}");
        include_once($CFG->dirroot.'/mod/jobtracker/mailtemplatelib.php');

        foreach ($jobccs as $cc) {
            unset($notification);
            unset($notification_html);
            $ccuser = $DB->get_record('user', array('id' => $cc->userid));
            $vars['UNCCURL'] = $CFG->wwwroot."/mod/jobtracker/view.php?a={$jobtracker->id}&amp;view=profile&amp;screen=mywatches&amp;ccid={$cc->userid}&amp;what=unregister";
            $vars['ALLUNCCURL'] = $CFG->wwwroot."/mod/jobtracker/view.php?a={$jobtracker->id}&amp;view=profile&amp;screen=mywatches&amp;userid={$cc->userid}&amp;what=unregisterall";
            switch ($job->status) {
                case JOBTRACK_OPEN:
                    if ($cc->events & JOBTRACK_EVENT_OPEN) {
                        $vars['EVENT'] = get_string('open', 'jobtracker');
                        $notification = jobtracker_compile_mail_template('statechanged', $vars, 'jobtracker', $ccuser->lang);
                        $notification_html = jobtracker_compile_mail_template('statechanged_html', $vars, 'jobtracker', $ccuser->lang);
                    }
                break;
                case JOBTRACK_SHORTLIST:
                    if($cc->events & JOBTRACK_EVENT_SHORTLIST) {
                        $vars['EVENT'] = get_string('shortlist', 'jobtracker');
                        $notification = jobtracker_compile_mail_template('statechanged', $vars, 'jobtracker', $ccuser->lang);
                        $notification_html = jobtracker_compile_mail_template('statechanged_html', $vars, 'jobtracker', $ccuser->lang);
                    }
                break;
                case JOBTRACK_WAITINGEVENT:
                    if ($cc->events & JOBTRACK_EVENT_WAITINGEVENT) {
                        $vars['EVENT'] = get_string('waitingevent', 'jobtracker');
                        $notification = jobtracker_compile_mail_template('statechanged', $vars, 'jobtracker', $ccuser->lang);
                        $notification_html = jobtracker_compile_mail_template('statechanged_html', $vars, 'jobtracker', $ccuser->lang);
                    }
                break;
                case JOBTRACK_TOREFRESH :
                    if ($cc->events & JOBTRACK_EVENT_TOREFRESH) {
                        $vars['EVENT'] = get_string('torefresh', 'jobtracker');
                        $notification = jobtracker_compile_mail_template('statechanged', $vars, 'jobtracker', $ccuser->lang);
                        $notification_html = jobtracker_compile_mail_template('statechanged_html', $vars, 'jobtracker', $ccuser->lang);
                    }
                break;
                case JOBTRACK_MEETINGSCHEDULED:
                    if ($cc->events & JOBTRACK_EVENT_MEETINGSCHEDULED) {
                        $vars['EVENT'] = get_string('meetingscheduled', 'jobtracker');
                        $notification = jobtracker_compile_mail_template('statechanged', $vars, 'jobtracker', $ccuser->lang);
                        $notification_html = jobtracker_compile_mail_template('statechanged_html', $vars, 'jobtracker', $ccuser->lang);
                    }
                break;
                case JOBTRACK_MEETINGDONE:
                    if ($cc->events & JOBTRACK_EVENT_MEETINGDONE) {
                        $vars['EVENT'] = get_string('meetingdone', 'jobtracker');
                        $notification = jobtracker_compile_mail_template('statechanged', $vars, 'jobtracker', $ccuser->lang);
                        $notification_html = jobtracker_compile_mail_template('statechanged_html', $vars, 'jobtracker', $ccuser->lang);
                    }
                break;
                case JOBTRACK_CONCLUDED:
                    if ($cc->events & JOBTRACK_EVENT_CONCLUDED) {
                        $vars['EVENT'] = get_string('concluded', 'jobtracker');
                        $notification = jobtracker_compile_mail_template('statechanged', $vars, 'jobtracker', $ccuser->lang);
                        $notification_html = jobtracker_compile_mail_template('statechanged_html', $vars, 'jobtracker', $ccuser->lang);
                    }
                break;
                case JOBTRACK_DEAD: 
                    if ($cc->events & JOBTRACK_EVENT_DEAD) {
                        $vars['EVENT'] = get_string('dead', 'jobtracker');
                        $notification = jobtracker_compile_mail_template('statechanged', $vars, 'jobtracker', $ccuser->lang);
                        $notification_html = jobtracker_compile_mail_template('statechanged_html', $vars, 'jobtracker', $ccuser->lang);
                    }
                break;
                default:
            }
            if (!empty($notification)) {
                if ($CFG->debugsmtp) {
                    echo "Sending State Change Mail Notification to " . fullname($ccuser) . '<br/>'.$notification_html;
                }
                email_to_user($ccuser, $USER, get_string('jobtrackereventchanged', 'jobtracker', $SITE->shortname.':'.format_string($jobtracker->name)), $notification, $notification_html);
            }
        }
    }
}

/**
 * sends required notifications by the watchers when first submit
 * @uses $COURSE
 * @param int $jobid
 * @param object $jobtracker
 */
function jobtracker_notifyccs_comment($jobid, $comment, $jobtracker = null) {
    global $COURSE, $SITE, $CFG, $USER, $DB;

    $job = $DB->get_record('jobtracker_job', array('id' => $jobid));
    if (empty($jobtracker)) {
        // Database access optimization in case we have a jobtracker from somewhere else.
        $jobtracker = $DB->get_record('jobtracker', array('id' => $job->jobtrackerid));
    }

    $jobccs = $DB->get_records('jobtracker_jobcc', array('jobid' => $job->id));
    if (!empty($jobccs)) {
        $vars = array('COURSE_SHORT' => $COURSE->shortname, 
                      'COURSENAME' => format_string($COURSE->fullname), 
                      'JOBTRACKERNAME' => format_string($jobtracker->name), 
                      'JOB' => $jobtracker->ticketprefix.$job->id, 
                      'POSITION' => $job->position, 
                      'COMMENT' => format_string($comment), 
                      'JOBURL' => $CFG->wwwroot."/mod/jobtracker/view.php?a={$jobtracker->id}&amp;view=view&amp;screen=viewanopportunity&amp;jobid={$job->id}",
                      );
        include_once($CFG->dirroot.'/mod/jobtracker/mailtemplatelib.php');
        foreach ($jobccs as $cc) {
            $ccuser = $DB->get_record('user', array('id' => $cc->userid));
            if ($cc->events & JOBTRACK_ON_COMMENT) {
                $vars['CONTRIBUTOR'] = fullname($USER);
                $vars['UNCCURL'] = $CFG->wwwroot."/mod/jobtracker/view.php?a={$jobtracker->id}&amp;view=profile&amp;screen=mywatches&amp;ccid={$cc->userid}&amp;what=unregister";
                $vars['ALLUNCCURL'] = $CFG->wwwroot."/mod/jobtracker/view.php?a={$jobtracker->id}&amp;view=profile&amp;screen=mywatches&amp;userid={$cc->userid}&amp;what=unregisterall";
                $notification = jobtracker_compile_mail_template('addcomment', $vars, 'jobtracker', $ccuser->lang);
                $notification_html = jobtracker_compile_mail_template('addcomment_html', $vars, 'jobtracker', $ccuser->lang);
                if ($CFG->debugsmtp) {
                    echo "Sending Comment Notification to " . fullname($ccuser) . '<br/>'.$notification_html;
                }
                email_to_user($ccuser, $USER, get_string('submission', 'jobtracker', $SITE->shortname.':'.format_string($jobtracker->name)), $notification, $notification_html);
            }
        }
    }
}

/**
 * loads the jobtracker users preferences in the $USER global.
 * @uses $USER
 * @param int $jobtrackerid the current jobtracker
 * @param int $userid the user the preferences belong to
 */
function jobtracker_loadpreferences($jobtrackerid, $userid = 0) {
    global $USER, $DB;

    if ($userid == 0) {
        $userid = $USER->id;
    }
    $preferences = $DB->get_records_select('jobtracker_preferences', "jobtrackerid = ? AND userid = ? ", array($jobtrackerid, $userid));
    if ($preferences) {
        foreach ($preferences as $preference) {
            $USER->jobtrackerprefs = new Stdclass();
            $USER->jobtrackerprefs->{$preference->name} = $preference->value;
        }
    }
}

/**
* reorder correctly the priority sequence and discard from the stack
* all concluded and dead entries
* @uses $CFG
* @param $reference $jobtracker
*/
function jobtracker_update_priority_stack(&$jobtracker) {
    global $CFG, $DB;

    // Discards concluded or dead.
    $sql = "
       UPDATE
           {jobtracker_job}
       SET
           resolutionpriority = 0
       WHERE
           jobtrackerid = $jobtracker->id AND
           status IN (".JOBTRACK_CONCLUDED.','.JOBTRACK_DEAD.')';
    $DB->execute($sql);

    // Fetch prioritarized by order.
    $jobs = $DB->get_records_select('jobtracker_job', "jobtrackerid = ? AND resolutionpriority != 0 ", array($jobtracker->id), 'resolutionpriority', 'id, resolutionpriority');
    $i = 1;
    if (!empty($jobs)) {
        foreach ($jobs as $job) {
            $job->resolutionpriority = $i;
            $DB->update_record('jobtracker_job', $job);
            $i++;
        }
    }
}

function jobtracker_get_stats(&$jobtracker, $from = null, $to = null) {
    global $CFG, $DB;

    $sql = "
        SELECT
            status,
            count(*) as value
        FROM
            {jobtracker_job}
        WHERE
            jobtrackerid = {$jobtracker->id}
        GROUP BY
            status
    ";
    if ($results = $DB->get_records_sql($sql)) {
        foreach ($results as $r) {
            $stats[$r->status] = $r->value;
        }
    } else {
        $stats[JOBTRACK_POSTED] = 0;
        $stats[JOBTRACK_OPEN] = 0;
        $stats[JOBTRACK_SHORTLIST] = 0;
        $stats[JOBTRACK_WAITINGEVENT] = 0;
        $stats[JOBTRACK_TOREFRESH] = 0;
        $stats[JOBTRACK_MEETINGSCHEDULED] = 0;
        $stats[JOBTRACK_MEETINGDONE] = 0;
        $stats[JOBTRACK_CONCLUDED] = 0;
        $stats[JOBTRACK_DEAD] = 0;
    }

    return $stats;
}

/**
 * compile stats relative to emission date
 *
 */
function jobtracker_get_stats_by_month(&$jobtracker, $from = null, $to = null) {
    global $CFG, $DB;
    $sql = "
        SELECT
            CONCAT(YEAR(FROM_UNIXTIME(timecreated)), '-', DATE_FORMAT(FROM_UNIXTIME(timecreated), '%m'), '-', status) as resultid,
            CONCAT(YEAR(FROM_UNIXTIME(timecreated)), '-', DATE_FORMAT(FROM_UNIXTIME(timecreated), '%m')) as period,
            status,
            count(*) as value
        FROM
            {jobtracker_job}
        WHERE
            jobtrackerid = {$jobtracker->id}
        GROUP BY
            status, CONCAT(YEAR(FROM_UNIXTIME(timecreated)), '-', DATE_FORMAT(FROM_UNIXTIME(timecreated), '%m'))
        ORDER BY period
    ";
    if ($results = $DB->get_records_sql($sql)) {
        foreach ($results as $r) {
            $stats[$r->period][$r->status] = $r->value;
            $stats[$r->period]['sum'] = @$stats[$r->period]['sum'] + $r->value;
            $stats['sum'] = @$stats['sum'] + $r->value;
        }
    } else {
        $stats = array();
    }

    return $stats;
}

/**
 * backtracks all jobs and summarizes monthly on status
 *
 */
function jobtracker_backtrack_stats_by_month(&$jobtracker) {
    global $CFG, $DB;

    $sql = "
        SELECT
            id,
            CONCAT(YEAR(FROM_UNIXTIME(timecreated)), '-', DATE_FORMAT(FROM_UNIXTIME(timecreated), '%m')) as period,
            status
        FROM
            {jobtracker_job}
        WHERE
            jobtrackerid = {$jobtracker->id}
        ORDER BY period
    ";
    if ($jobs = $DB->get_records_sql($sql)) {

        // Dispatch job generating events and follow change tracks.
        foreach ($jobs as $is) {
            $tracks[$is->period][$is->id] = $is->status;
            $sql = "
                SELECT
                    id,
                    jobid,
                    timechange,
                    CONCAT(YEAR(FROM_UNIXTIME(timechange)), '-', DATE_FORMAT(FROM_UNIXTIME(timechange), '%m')) as period,
                    statusto
                FROM
                    {jobtracker_state_change}
                WHERE
                    jobid = {$is->id}
                ORDER BY 
                    timechange
            ";
            if ($changes = $DB->get_records_sql($sql)) {
                foreach ($changes as $c) {
                    $tracks[$c->period][$c->jobid] = $c->statusto;
                }
            }
            $joblist[$is->id] = -1;
        }

        ksort($tracks);

        $availdates = array_keys($tracks);
        $lowest = $availdates[0];
        $highest = $availdates[count($availdates) - 1];
        $low = new StdClass();
        list($low->year, $low->month) = explode('-', $lowest);
        $dateiter = new jobtracker_date_iterator($low->year, $low->month);

        // Scan table and snapshot job states.
        $current = $dateiter->current();
        while (strcmp($current, $highest) <= 0) {
            if (array_key_exists($current, $tracks)) {
                foreach ($tracks[$current] as $trackedid => $trackedstate){
                    $joblist[$trackedid] = $trackedstate;
                }
            }
            $monthtracks[$current] = $joblist;
            $dateiter->next();
            $current = $dateiter->current();
        }

        // Revert and summarize states.
        foreach ($monthtracks as $current => $monthtrack) {
            foreach ($monthtrack as $jobid => $state) {
                if ($state == -1) {
                    continue;
                }
                $stats[$current][$state] = @$stats[$current][$state] + 1;
                $stats[$current]['sum'] = @$stats[$current]['sum'] + 1;
                if ($state != JOBTRACK_CONCLUDED && $state != JOBTRACK_DEAD) {
                    $stats[$current]['sumunres'] = @$stats[$current]['sumunres'] + 1;
                }
            }
        }

        return $stats;
    }
    return array();
}

/**
 * Compiles global stats on users
 *
 */
function jobtracker_get_stats_by_user(&$jobtracker, $userclass, $from = null, $to = null) {
    global $CFG, $DB;

    $groups = false;

    $systemcontext = context_system::instance();

    // Reduce to groups the user can see.
    if (!has_capability('moodle/site:accessallgroups', $systemcontext)) {
        if ($mygroups = jobtracker_get_user_groups()) {
            $groups = implode(',', array_keys($mygorups));
        } else {
            return array();
        }
    }

    $groupClause = ($groups) ? " gm.groupid IN ({$groups}) " : '';
    $groupJoin = ($groups) ? " JOIN {groups_members} gm ON j.userid = gm.userid " : '';

    $sql = "
        SELECT
            CONCAT(u.id, '-', j.status) as resultdid,
            u.id,".
            get_all_user_name_fields(true, 'u').",
            count(*) as value,
            j.status
        FROM
            {jobtracker_job} j
        LEFT JOIN
            {user} u
        ON
            j.{$userclass} = u.id
        WHERE
            jobtrackerid = ?
        GROUP BY
            CONCAT(u.id, '-', j.status)
        ORDER BY
            u.lastname, u.firstname
    ";
    if ($results = $DB->get_records_sql($sql, array($jobtracker->id))) {
        foreach ($results as $r) {
            $stats[$r->id] = new StdClass();
            $stats[$r->id]->name = fullname($r);
            $stats[$r->id]->status[$r->status] = $r->value;
            $stats[$r->id]->sum = @$stats[$r->id]->sum + $r->value;
        }
    } else {
        $stats = array();
    }
    return $stats;
}

/**
 * Compiles global stats on users
 *
 */
function jobtracker_get_stats_by_assignee(&$jobtracker, $from = null, $to = null) {
    global $CFG, $DB;

    $coursecontext = context_course::instance($jobtracker->course);

    $assignees = get_users_by_capability($coursecontext, 'mod/jobtracker:follow', 'u.id,'.get_all_user_name_fields(true, 'u'), 'u.lastname, u.firstname');

    if (empty($assignees)) return array();

    $stats = array();

    foreach($assignees as $assignee) {

        if ($assigneegroups = jobtracker_get_user_groups($assignee->id)) {
            $groups = implode(',',array_keys($assigneegroups));
        } else {
            return array();
        }

        $groupClause = ($groups) ? " AND gm.groupid IN ({$groups}) " : '';
        $groupJoin = ($groups) ? " JOIN {groups_members} gm ON j.userid = gm.userid " : '';
    
        $sql = "
            SELECT
                j.status,
                count(*) as value
            FROM
                {jobtracker_job} j
            $groupJoin
            WHERE
                jobtrackerid = ?
                $groupClause
            GROUP BY
                CONCAT(j.status)
        ";
        if ($results = $DB->get_records_sql($sql, array($jobtracker->id))) {
            foreach ($results as $r) {
                $stats[$assignee->id] = new StdClass();
                $stats[$assignee->id]->name = fullname($assignee);
                $stats[$assignee->id]->status[$r->status] = $r->value;
                $stats[$assignee->id]->sum = @$stats[$r->status]->sum + $r->value;
            }
        }
    }

    return $stats;
}

/**
 * Gets array of all groups for user.
 *
 * @since Moodle 2.5
 * @category group
 * @return array Returns an array of the group objects.
 */
function jobtracker_get_user_groups($userid = 0) {
    global $DB, $USER;

    if ($userid == 0) {
        $userid = $USER->id;
    }

    return $DB->get_records_sql("SELECT g.*
                                   FROM {groups_members} gm
                                   JOIN {groups} g
                                    ON g.id = gm.groupid
                                  WHERE gm.userid = ?
                                   ORDER BY name ASC", array($userid));
}
 
/**
 * provides a practical date iterator for progress display
 *
 */
class jobtracker_date_iterator {
    var $inityear;
    var $initmonth;
    var $year;
    var $month;
    
    function __construct($year, $month) {
        $this->year = $year;
        $this->month = $month;
        $this->inityear = $year;
        $this->initmonth = $month;
    }    

    function reset() {
        $this->year = $this->inityear;
        $this->month = $this->initmonth;
    }
    
    function next() {
        $this->month++;
        if ($this->month > 12){
            $this->month = 1;
            $this->year++;
        }
    }

    function current() {
        return $this->year.'-'.sprintf('%02d', $this->month);
    }

    function getyear() {
        return $this->year;
    }

    function getmonth() {
        return $this->month;
    }
    
    function getiterations($highest) {
        $year = $this->year;
        $month = $this->month;
        $current = $year.'-'.sprintf('%02d', $month);
        $i = 0;
        while (strcmp($current, $highest) <= 0) {
            $i++;
            $month++;
            if ($month > 12) {
                $month = 1;
                $year++;
            }            
            $current = $year.'-'.sprintf('%02d', $month);
        }
        return $i;
    }
}

/**
 * Initializes a full featured moodle text editor outside a moodle form context.
 * This allow making custom forms with free HMTL layout.
 */
function jobtracker_print_direct_editor($attributes, $values, $options) {
    global $CFG, $PAGE;

    require_once($CFG->dirroot.'/repository/lib.php');

    $ctx = $options['context'];

    $id           = $attributes['id'];
    $elname       = $attributes['name'];

    $subdirs      = @$options['subdirs'];
    $maxbytes     = @$options['maxbytes'];
    $areamaxbytes = @$options['areamaxbytes'];
    $maxfiles     = @$options['maxfiles'];
    $changeformat = @$options['changeformat']; // TO DO: implement as ajax calls

    $text         = $values['text'];
    $format       = $values['format'];
    $draftitemid  = $values['itemid'];

    // security - never ever allow guest/not logged in user to upload anything
    if (isguestuser() or !isloggedin()) {
        $maxfiles = 0;
    }

    // $str = $this->_getTabs();
    $str = '';
    $str .= '<div>';

    $editor = editors_get_preferred_editor($format);
    $strformats = format_text_menu();
    $formats =  $editor->get_supported_formats();
    foreach ($formats as $fid) {
        $formats[$fid] = $strformats[$fid];
    }

    // get filepicker info
    //
    $fpoptions = array();
    if ($maxfiles != 0 ) {
        if (empty($draftitemid)) {
            // No existing area info provided - let's use fresh new draft area.
            require_once("$CFG->libdir/filelib.php");
            $draftitemid = file_get_unused_draft_itemid();
            echo " Generating fresh filearea $draftitemid "; 
        }

        $args = new stdClass();
        // Need these three to filter repositories list.
        $args->accepted_types = array('web_image');
        $args->return_types = @$options['return_types'];
        $args->context = $ctx;
        $args->env = 'filepicker';
        // Advimage plugin.
        $image_options = initialise_filepicker($args);
        $image_options->context = $ctx;
        $image_options->client_id = uniqid();
        $image_options->maxbytes = @$options['maxbytes'];
        $image_options->areamaxbytes = @$options['areamaxbytes'];
        $image_options->env = 'editor';
        $image_options->itemid = $draftitemid;

        // Moodlemedia plugin.
        $args->accepted_types = array('video', 'audio');
        $media_options = initialise_filepicker($args);
        $media_options->context = $ctx;
        $media_options->client_id = uniqid();
        $media_options->maxbytes  = @$options['maxbytes'];
        $media_options->areamaxbytes  = @$options['areamaxbytes'];
        $media_options->env = 'editor';
        $media_options->itemid = $draftitemid;

        // Advlink plugin.
        $args->accepted_types = '*';
        $link_options = initialise_filepicker($args);
        $link_options->context = $ctx;
        $link_options->client_id = uniqid();
        $link_options->maxbytes  = @$options['maxbytes'];
        $link_options->areamaxbytes  = @$options['areamaxbytes'];
        $link_options->env = 'editor';
        $link_options->itemid = $draftitemid;

        $fpoptions['image'] = $image_options;
        $fpoptions['media'] = $media_options;
        $fpoptions['link'] = $link_options;
    }

    // If editor is required and tinymce, then set required_tinymce option to initalize tinymce validation.
    if (($editor instanceof tinymce_texteditor)  && !empty($attributes['onchange'])) {
        $options['required'] = true;
    }

    // Print text area - TODO: add on-the-fly switching, size configuration, etc.
    $editor->use_editor($id, $options, $fpoptions);

    $rows = empty($attributes['rows']) ? 15 : $attributes['rows'];
    $cols = empty($attributes['cols']) ? 80 : $attributes['cols'];

    //Apply editor validation if required field
    $editorrules = '';
    if (!empty($attributes['onblur']) && !empty($attributes['onchange'])) {
        $editorrules = ' onblur="'.htmlspecialchars($attributes['onblur']).'" onchange="'.htmlspecialchars($attributes['onchange']).'"';
    }
    $str .= '<div><textarea id="'.$id.'" name="'.$elname.'[text]" rows="'.$rows.'" cols="'.$cols.'"'.$editorrules.'>';
    $str .= s($text);
    $str .= '</textarea></div>';

    $str .= '<div>';
    if (count($formats) > 1) {
        $str .= html_writer::label(get_string('format'), 'menu'. $elname. 'format', false, array('class' => 'accesshide'));
        $str .= html_writer::select($formats, $elname.'[format]', $format, false, array('id' => 'menu'. $elname. 'format'));
    } else {
        $keys = array_keys($formats);
        $str .= html_writer::empty_tag('input',
                array('name' => $elname.'[format]', 'type' => 'hidden', 'value' => array_pop($keys)));
    }
    $str .= '</div>';

    // during moodle installation, user area doesn't exist
    // so we need to disable filepicker here.
    if (!during_initial_install() && empty($CFG->adminsetuppending)) {
        // 0 means no files, -1 unlimited
        if ($maxfiles != 0 ) {
            $str .= '<input type="hidden" name="'.$elname.'[itemid]" value="'.$draftitemid.'" />';

            // Used by non js editor only.
            $editorurl = new moodle_url("$CFG->wwwroot/repository/draftfiles_manager.php", array(
                'action' => 'browse',
                'env' => 'editor',
                'itemid' => $draftitemid,
                'subdirs' => $subdirs,
                'maxbytes' => $maxbytes,
                'areamaxbytes' => $areamaxbytes,
                'maxfiles' => $maxfiles,
                'ctx_id' => $ctx->id,
                'course' => $PAGE->course->id,
                'sesskey' => sesskey(),
                ));
            $str .= '<noscript>';
            $str .= "<div><object type='text/html' data='$editorurl' height='160' width='600' style='border:1px solid #000'></object></div>";
            $str .= '</noscript>';
        }
    }

    $str .= '</div>';

    return $str;
}

function jobtracker_get_statuscodes() {
    static $STATUSCODES;

    if (!isset($STATUSCODES)) {
        $STATUSCODES = array('posted', 'open', 'shortlist', 'waitingevent', 'torefresh', 'meetingscheduled', 'meetingdone', 'concluded', 'dead');
    }

    return $STATUSCODES;
}

/**
 * get all active keys for ticket states? As this may be required for all tickets in a print list, we cache it
 * @param object $jobtracker the jobtracker instance
 * @param object $cm the course module. If given, only role accessible keys will be output
 */
function jobtracker_get_statuskeys($jobtracker, $cm = null) {
    static $AVAILABLESTATUSKEYS;
    static $FULLSTATUSKEYS;
    static $STATUSKEYS;

    if (!isset($AVAILABLESTATUSKEYS)) {
        $AVAILABLESTATUSKEYS = array(
            JOBTRACK_POSTED => get_string('posted', 'jobtracker'), 
            JOBTRACK_OPEN => get_string('open', 'jobtracker'), 
            JOBTRACK_SHORTLIST => get_string('shortlist', 'jobtracker'), 
            JOBTRACK_WAITINGEVENT => get_string('waitingevent', 'jobtracker'), 
            JOBTRACK_TOREFRESH => get_string('torefresh', 'jobtracker'), 
            JOBTRACK_MEETINGSCHEDULED => get_string('meetingscheduled', 'jobtracker'), 
            JOBTRACK_MEETINGDONE => get_string('meetingdone', 'jobtracker'), 
            JOBTRACK_CONCLUDED => get_string('concluded', 'jobtracker'), 
            JOBTRACK_DEAD => get_string('dead', 'jobtracker'),
        );
    }

    if (!$jobtracker) {
        return $AVAILABLESTATUSKEYS;
    }

    if (!isset($FULLSTATUSKEYS)) {
        $FULLSTATUSKEYS = $AVAILABLESTATUSKEYS;

        if (!($jobtracker->enabledstates & JOBTRACK_ENABLED_OPEN)) {
            unset($FULLSTATUSKEYS[OPEN]);
        }
        if (!($jobtracker->enabledstates & JOBTRACK_ENABLED_SHORTLIST)) {
            unset($FULLSTATUSKEYS[JOBTRACK_SHORTLIST]);
        }
        if (!($jobtracker->enabledstates & JOBTRACK_ENABLED_WAITINGEVENT)) {
            unset($FULLSTATUSKEYS[JOBTRACK_WAITINGEVENT]);
        }
        if (!($jobtracker->enabledstates & JOBTRACK_ENABLED_TOREFRESH)) {
            unset($FULLSTATUSKEYS[JOBTRACK_TOREFRESH]);
        }
        if (!($jobtracker->enabledstates & JOBTRACK_ENABLED_MEETINGSCHEDULED)) {
            unset($FULLSTATUSKEYS[JOBTRACK_MEETINGSCHEDULED]);
        }
        if (!($jobtracker->enabledstates & JOBTRACK_ENABLED_MEETINGDONE)) {
            unset($FULLSTATUSKEYS[JOBTRACK_MEETINGDONE]);
        }
        if (!($jobtracker->enabledstates & JOBTRACK_ENABLED_CONCLUDED)) {
            unset($FULLSTATUSKEYS[JOBTRACK_CONCLUDED]);
        }
        if (!($jobtracker->enabledstates & JOBTRACK_ENABLED_DEAD)) {
            unset($FULLSTATUSKEYS[JOBTRACK_DEAD]);
        }
    }

    if (!empty($jobtracker->strictworkflow) && $cm) {
        if (!isset($STATUSKEYS)) {
            $context = context_module::instance($cm->id);

            $STATUSKEYS = array();

            if (has_capability('mod/jobtracker:report', $context)) {
                $roledef = jobtracker_get_role_definition($jobtracker, 'report');
                foreach ($FULLSTATUSKEYS as $key => $label) {
                    $eventkey = pow(2,$key);
                    if ($eventkey & $roledef) {
                        $STATUSKEYS[$key] = $label;
                    }
                }
            }
            if (has_capability('mod/jobtracker:develop', $context)) {
                $roledef = jobtracker_get_role_definition($jobtracker, 'develop');
                foreach ($FULLSTATUSKEYS as $key => $label) {
                    $eventkey = pow(2,$key);
                    if ($eventkey & $roledef) {
                        $STATUSKEYS[$key] = $label;
                    }
                }
            }
            if (has_capability('mod/jobtracker:resolve', $context)) {
                $roledef = jobtracker_get_role_definition($jobtracker, 'resolve');
                foreach ($FULLSTATUSKEYS as $key => $label) {
                    $eventkey = pow(2,$key);
                    if ($eventkey & $roledef) {
                        $STATUSKEYS[$key] = $label;
                    }
                }
            }
            if (has_capability('mod/jobtracker:manage', $context)) {
                $roledef = jobtracker_get_role_definition($jobtracker, 'manage');
                foreach ($FULLSTATUSKEYS as $key => $label) {
                    $eventkey = pow(2,$key);
                    if ($eventkey & $roledef) {
                        $STATUSKEYS[$key] = $label;
                    }
                }
            }
        } else {
            // echo "using cache";
        }
        return $STATUSKEYS;
    }
    
    return $FULLSTATUSKEYS;
}

// Allows array reduction for state profiles.
function jobtracker_ror($v, $w) {
    $v |= $w;
    return $v;
}

/**
 *
 *
 */
function jobtracker_resolve_view(&$jobtracker, &$cm) {
    global $SESSION;
    
    $context = context_module::instance($cm->id);
    
    $view = optional_param('view', @$SESSION->jobtracker_current_view, PARAM_ALPHA);

    if (empty($view)) {
        $defaultview = 'view';
        $view = $defaultview;
    }
    
    $SESSION->jobtracker_current_view = $view;
    return $view;
}

/**
 *
 *
 */
function jobtracker_resolve_screen(&$jobtracker, &$cm) {
    global $SESSION;

    $context = context_module::instance($cm->id);

    $screen = optional_param('screen', @$SESSION->jobtracker_current_screen, PARAM_ALPHA);
    if (empty($screen)) {
        if (has_capability('mod/jobtracker:follow', $context)) {
            $defaultscreen = 'mywork';
        } elseif (has_capability('mod/jobtracker:report', $context)) {
            $defaultscreen = 'myjobs';
        } else {
            $defaultscreen = 'browse'; // report
        }
        $screen = $defaultscreen;
    }

    $SESSION->jobtracker_current_screen = $screen;
    return $screen;
}

/**
 * Conditions for people having access to ticket full edition
 *
 */
function jobtracker_can_edit(&$jobtracker, &$context, &$job) {
    global $USER;

    if (has_capability('mod/jobtracker:manage', $context)) {
        return true;
    }
    
    if ($job->userid == $USER->id) {
        // if this record is mine
        return true;
    }

    if ($job->followedby == $USER->id && has_capability('mod/jobtracker:follow', $context)) {
        // if i track this record
        return true;
    }

    return false;
}

/**
 * Conditions for people authorized to work on : ticket editor (but non owner)
 * this is used for opening tickets when viweing 
 * @see views/viewanjob.php
 */
function jobtracker_can_workon(&$jobtracker, &$context, $job = null) {
    global $USER;

    if (has_capability('mod/jobtracker:report', $context)) {
        return true;
    }

    if ($job) {
        if ($job->followedby == $USER->id && has_capability('mod/jobtracker:follow', $context)) {
            return true;
        }
    } else {
        if (has_capability('mod/jobtracker:workon', $context)) {
            return true;
        }
    }

    return false;
}

function jobtracker_check_jquery() {
    global $PAGE, $OUTPUT, $JQUERYVERSION;

    $current = '1.8.2';

    if (empty($JQUERYVERSION)) {
        $JQUERYVERSION = '1.8.2';
        $PAGE->requires->js('/mod/jobtracker/js/jquery-'.$current.'.min.js', true);
    } else {
        if ($JQUERYVERSION < $current) {
            debugging('the previously loaded version of jquery is lower than required. This may cause jobs to jobtracker reports. Programmers might consider upgrading JQuery version in the component that preloads JQuery library.', DEBUG_DEVELOPER, array('notrace'));
        }
    }
}

function jobtracker_update_status($jobid, $status) {
    global $DB, $USER;
    
    $job = $DB->get_record('jobtracker_job', array('id' => $jobid));
    $jobtracker = $DB->get_record('jobtracker', array('id' => $job->jobtrackerid));

    $oldstatus = $job->status;
    $job->status = $status;

    $DB->update_record('jobtracker_job', $job);

    // Check status changing and send notifications.
    if ($oldstatus != $job->status) {
        if ($jobtracker->allownotifications) {
            jobtracker_notifyccs_changestate($job->id, $jobtracker);
        }
        // log state change
        $stc = new StdClass;
        $stc->userid = $USER->id;
        $stc->jobid = $job->id;
        $stc->jobtrackerid = $jobtracker->id;
        $stc->timechange = time();
        $stc->statusfrom = $oldstatus;
        $stc->statusto = $job->status;
        $DB->insert_record('jobtracker_state_change', $stc);
    }
}

/**
 * Centralizes all rules that makes a user visible for followers
 * @param int $userid
 * @return boolean
 */
function jobtracker_can_see_user($userid) {
    global $USER;
    
    $result = true;
    
    return $status;
}

/**
 * builds job list and count queries
 */
function jobtracker_get_listsql($jobtracker, $resolved) {
    global $USER;

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
            i.status,
            i.resolutionpriority,
            COUNT(ic.jobid) AS watches
        FROM 
            {jobtracker_job} i
        LEFT JOIN
            {jobtracker_jobcc} ic 
        ON
            ic.jobid = i.id
        WHERE 
            i.userid = {$USER->id} AND
            i.jobtrackerid = {$jobtracker->id}
            $resolvedclause
        GROUP BY 
            i.id,
            i.company,
            i.timecreated,
            i.status,
            i.resolutionpriority
    ";
    
    $countsql = "
        SELECT 
            COUNT(*)
        FROM 
            {jobtracker_job} i
        WHERE 
            i.userid = {$USER->id} AND 
            i.jobtrackerid = {$jobtracker->id}
            $resolvedclause
    ";

    return array($sql, $countsql);
}

function jobtracker_get_element(&$jobtracker, $id = null, $type) {
    global $CFG;

    $classname = $type.'element';
    $classfilename = $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/'.$type.'/'.$type.'.class.php';

    require_once($classfilename);
    $element = new $classname($jobtracker, $id);
    return $element;
}


