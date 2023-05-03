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
 * this library provides a set of function used by other modules.
 * those functions will avoid otehr components to deal with internal implementation
 * of the current component.
 */
require_once($CFG->dirroot.'/mod/jobtracker/locallib.php');

/**
 * Get all pending tracked jobs in all trackers for a user.
 * see local/isfmobile
 */
function jobtracker_get_pendings($userid) {
    global $DB;

    $pendings = $DB->get_records('jobtracker_job', array('userid' => $userid, 'status' => JOBTRACK_TOREFRESH));

    return $pendings;
}

/**
 * Get all dependant trainees in all jobtracker instances to feed a trainer mobile view
 * see local/isfmobile
 */
function jobtracker_get_my_trainees($filter, $mobileonly = true) {
    global $DB;

    $mentees = array();
    $coursescanned = array();

    $mycourses = enrol_get_my_courses();
    if (!empty($mycourses)) {
        $mycourseids = implode("','", array_keys($mycourses));
        $alltrackers = $DB->get_records_select('jobtracker', " course IN ('.$mycourseids.') ");
        if ($alltrackers) {
            foreach ($alltrackers as $t) {

                // Do NOT reaggregate mentees of already full scanned course / performance.
                if (in_array($t->course, $coursescanned)) {
                    continue;
                }

                $cm = get_coursemodule_from_instance('jobtracker', $t->id);

                // this will only keep mobile enabled trackers
                if ($mobileonly && !preg_match('/MOB$/', $cm->idnumber)) {
                    continue;
                }

                $context = context_module::instance($cm->id);
                if (has_capability('mod/jobtracker:follow', $context)) {
                    // I am follower here. Now let's get mentees
                    $groupmode = groups_get_activity_groupmode($cm);
                    if ($groupmode == NOGROUPS) {
                        $coursementees = get_users_by_capability($context, 'mod/jobtracker:workon', 'u.id, u.username, '.get_all_user_name_fields(true, 'u').', u.phone1, u.phone2');
                        if ($coursementees) {
                            $coursescanned[] = $t->course;
                            foreach ($coursementees  as $cmid => $cmtee) {
                                $fullname = fullname($cmtee);
                                if (preg_match("/$filter/", $fullname) || empty($filter)) {
                                    $mentees[$cmid] = $cmtee;
                                }
                            }
                        }
                    } else {
                        $group = groups_get_activity_group($cm);
                        $potentialmentees = groups_get_members($group->id);
                        foreach ($potentialmentees as $pmid => $pm) {
                            // Check is a real mentee in activity.
                            if (has_capability('mod/jobtracker:follow', $context)) {
                                $fullname = fullname($pm);
                                if (preg_match("/$filter/", $fullname) || empty($filter)) {
                                    $mentees[$pmid] = $pm;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    return $mentees;
}
