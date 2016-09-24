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
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @date 02/11/2014
 * @version Moodle 2.0
 *
 * Library of functions and constants for module jobtracker
 */

require_once($CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/jobtrackerelement.class.php');
require_once($CFG->dirroot.'/mod/jobtracker/locallib.php');

/**
 * List of features supported in jobtracker module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function jobtracker_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_OTHER;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES:    return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 * @param object $jobtracker
 */
function jobtracker_add_instance($jobtracker, $mform) {
    global $DB;

    $jobtracker->timemodified = time();
    if (empty($jobtracker->allownotifications)) $jobtracker->allownotifications = 0;
    if (empty($jobtracker->introformat)) $jobtracker->introformat = FORMAT_MOODLE;
    if (empty($jobtracker->enablecomments)) $jobtracker->enablecomments = 0;

    $jobtracker->id = $DB->insert_record('jobtracker', $jobtracker);

    if (empty($jobtracker->ticketprefix)) {
        $jobtracker->ticketprefix = 'JOB'.$jobtracker->id.'_';
        $DB->set_field('jobtracker', 'ticketprefix', $jobtracker->ticketprefix, array('id' => $jobtracker->id));
    }

    return $jobtracker->id;
}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new data.
 */
function jobtracker_update_instance($jobtracker, $mform) {
    global $DB;

    $jobtracker->timemodified = time();
    $jobtracker->id = $jobtracker->instance;
 
    if (empty($jobtracker->ticketprefix)) {
        $jobtracker->ticketprefix = 'JOB'.$jobtracker->id.'_';
    }

    return $DB->update_record('jobtracker', $jobtracker);
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any data that depends on it.
 */
function jobtracker_delete_instance($id) {
    global $DB;

    if (!$jobtracker = $DB->get_record('jobtracker', array('id' => "$id"))) {
        return false;
    }

    if (!$cm = get_coursemodule_from_instance('jobtracker', $jobtracker->id)) {
        return false;
    }

    $context = context_module::instance($cm->id);

    $result = true;

    /// Delete any dependent records here 
    $DB->delete_records('jobtracker_job', array('jobtrackerid' => "$jobtracker->id"));
    $DB->delete_records('jobtracker_elementused', array('jobtrackerid' => "$jobtracker->id"));
    $DB->delete_records('jobtracker_query', array('jobtrackerid' => "$jobtracker->id"));
    $DB->delete_records('jobtracker_jobattribute', array('jobtrackerid' => "$jobtracker->id"));
    $DB->delete_records('jobtracker_jobcc', array('jobtrackerid' => "$jobtracker->id"));
    $DB->delete_records('jobtracker_jobcomment', array('jobtrackerid' => "$jobtracker->id"));

    // Delete all files attached to this context.
    $fs = get_file_storage();
    $fs->delete_area_files($context->id);

    return $result;
}

/**
 * Return a small object with summary information about what a 
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 */
function jobtracker_user_outline($course, $user, $mod, $jobtracker) {
    return null;
}

/**
* Print a detailed representation of what a  user has done with 
* a given particular instance of this module, for user activity reports.
*/
function jobtracker_user_complete($course, $user, $mod, $jobtracker) {
    return null;
}

/**
* Given a course and a time, this module should find recent activity 
* that has occurred in jobtracker activities and print it out. 
* Return true if there was output, or false is there was none.
*/
function jobtracker_print_recent_activity($course, $isteacher, $timestart) {
    global $DB, $CFG;
    
    $sql = "
        SELECT
            ti.id,
            ti.jobtrackerid,
            ti.company,
            ti.userid,
            ti.timecreated,
            t.name,
            t.ticketprefix
         FROM
            {jobtracker} t,
            {jobtracker_job} ti
         WHERE
            t.id = ti.jobtrackerid AND
            t.course = $course->id AND
            ti.timecreated > $timestart
    ";
    $newstuff = $DB->get_records_sql($sql);
    if ($newstuff) {
        foreach ($newstuff as $ajob) {
            echo "<span style=\"font-size:0.8em\">"; 
            echo get_string('modulename', 'jobtracker').': '.format_string($ajob->name).':<br/>';
            $params = array('a' => $ajob->jobtrackerid, 'view' => 'view', 'screen' => 'viewanopportunity', 'jobid' => $ajob->id);
            $joburl = new moodle_url('/mod/jobtracker/view.php', $params);
            echo '<a href="'.$joburl.'">'.shorten_text(format_string($ajob->company), 20).'</a><br/>';
            echo '&nbsp&nbsp&nbsp<span class="jobtrackersmalldate">'.userdate($ajob->timecreated).'</span><br/>';
            echo "</span><br/>";
        }
        return true;
    }

    return false;  //  True if anything was printed, otherwise false 
}

/**
 * Print an overview of all jobtrackers
 * for the courses.
 *
 * @param mixed $courses The list of courses to print the overview for
 * @param array $htmlarray The array of html to return
 */
function jobtracker_print_overview($courses, &$htmlarray) {
    global $USER, $CFG, $DB;

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    if (!$jobtrackers = get_all_instances_in_courses('jobtracker', $courses)) {
        return;
    }

    $strjobtracker = get_string('modulename', 'jobtracker');

    foreach ($jobtrackers as $jobtracker) {

        $jobtrackerurl = new moodle_url('/mod/jobtracker/view.php', array('id' => $jobtracker->coursemodule));

        $str = '<div class="jobtracker overview">';
        $str .= '<div class="name">'.$strjobtracker. ': ';
        $jtname = format_string($jobtracker->name);
        $str .= '<a '.($jobtracker->visible ? '':' class="dimmed"').' title="'.$strjobtracker.'" href="'.$jobtrackerurl.'">'.$jtname.'</a>';
        $str .= '</div>';

        // count how many active opportunities
        $sql = "
            SELECT DISTINCT
                j.id, j.id
            FROM
                {jobtracker_job} j
            WHERE
                j.jobtrackerid = ? AND
                userid = ? AND
                (status = ".JOBTRACK_TOREFRESH." OR
                status = ".JOBTRACK_SHORTLIST.")
        ";
        $yours = $DB->get_records_sql($sql, array($jobtracker->id, $USER->id));

        if ($yours) {
            $link = new moodle_url('/mod/jobtracker/view.php', array('id' => $jobtracker->coursemodule, 'view' => 'view', 'screen' => 'mywork'));
            $str .= '<div class="details"><a href="'.$link.'">'.get_string('jobstowatch', 'jobtracker', count($yours)).'</a></div>';
        }

        $str .= '</div>';

        if (empty($htmlarray[$jobtracker->course]['jobtracker'])) {
            $htmlarray[$jobtracker->course]['jobtracker'] = $str;
        } else {
            $htmlarray[$jobtracker->course]['jobtracker'] .= $str;
        }
    }
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such 
 * as sending out mail, toggling flags etc ... 
 */
function jobtracker_cron () {
    global $CFG, $DB;

    // triggers all waiting event to refresh state after a delay defined in instance
    $sql = "
        UPDATE 
            {jobtracker_job} j
        SET
            status = '".JOBTRACK_TOREFRESH."'
        WHERE
            status = '".JOBTRACK_WAITINGEVENT."' AND
            lastmodified > (
                SELECT 
                    refreshdelay 
                FROM
                    {jobtracker} jt
                WHERE
                    jt.id = j.jobtrackerid) 
    ";

    $DB->execute($sql);

    return true;
}

/** 
 * Must return an array of grades for a given instance of this module, 
 * indexed by user.  It also returns a maximum allowed grade.
 *
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 */
function jobtracker_grades($jobtrackerid) {
   return null;
}

/**
 *
 **/
function jobtracker_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of jobtracker. Must include every user involved
 * in the instance, independent of his role (student, teacher, admin...)
 * See other modules as example.
 */
function jobtracker_get_participants($jobtrackerid) {
    global $DB;

    $ccs = $DB->get_records('jobtracker_jobcc', array('jobtrackerid' => $jobtrackerid), '', 'id,id');
    if(!$ccs) $ccs = array();
    $reporters = $DB->get_records('jobtracker_job', array('jobtrackerid' => $jobtrackerid), '', 'userid,userid');
    if(!$reporters) $reporters = array();
    $commenters = $DB->get_records('jobtracker_jobcomment', array('jobtrackerid' => $jobtrackerid), '', 'userid,userid');
    if(!$commenters) $commenters = array();
    $participants = array_merge(array_keys($ccs), array_keys($reporters), array_keys($commenters));
    $participantlist = implode(',', array_unique($participants));

    if (!empty($participantlist)) {
        return $DB->get_records_list('user', array('id' => $participantlist));   
    }
    return array();
}

/*
 * This function returns if a scale is being used by one jobtracker
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 */
function jobtracker_scale_used ($jobtrackerid, $scaleid) {
    $return = false;

    //$rec = get_record("jobtracker","id","$jobtrackerid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

function jobtracker_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    $fileareas = array('jobresolution', 'jobattribute', 'jobcomment');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    $itemid = (int)array_shift($args);

    if (!$jobtracker = $DB->get_record('jobtracker', array('id' => $cm->instance))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_jobtracker/$filearea/$itemid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // Finally send the file.
    send_stored_file($file, 0, 0, false); // download MUST be forced - security!
}

function jobtracker_preset_states(&$jobtracker) {

    if (is_array(@$jobtracker->stateprofile)) {
        $jobtracker->enabledstates = array_reduce($jobtracker->stateprofile, 'jobtracker_ror', 0);
    }
}

function jobtracker_preset_params(&$jobtracker) {
    global $DB;

    $jobtracker->thanksmessage = get_string('messageregister', 'jobtracker');
}

/**
 * Obtains the automatic completion state for this module based on any conditions
 * in mplayer settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function jobtracker_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;

    // If completion option is enabled, evaluate it and return true/false.
    if ($mplayerinstance->completionhasconcluded) {
        if ($finished = $DB->count_records('jobtracker_job', array('userid' => $userid, 'jobtrackerid' => $cm->instance, 'status' => JOBTRACKER_CONCLUDED))) {
            if ($type == COMPLETION_AND) {
                $result = $result && $finished;
            } else {
                $result = $result || $finished;
            }
        }
    } else {
        // Completion option is not enabled so just return $type.
        return $type;
    }
}
