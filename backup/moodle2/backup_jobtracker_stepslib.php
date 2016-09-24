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
 * @package    mod
 * @subpackage jobtracker
 * @copyright  2010 onwards Valery Fremaux {valery.fremaux@club-internet.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_vodclic_activity_task
 */

/**
 * Define the complete label structure for backup, with file and id annotations
 */
class backup_jobtracker_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $jobtracker = new backup_nested_element('jobtracker', array('id'), array(
            'name', 'intro', 'introformat', 'allownotifications', 'ticketprefix', 
            'timemodified', 'refreshdelay', 'enablestates'));

        $elements = new backup_nested_element('elements');

        $element = new backup_nested_element('element', array('id'), array(
            'name', 'description', 'type'));

        $elementitems = new backup_nested_element('elementitems');

        $item = new backup_nested_element('elementitem', array('id'), array(
            'elementid', 'name', 'description', 'sortorder', 'active'));

        $usedelements = new backup_nested_element('usedelements');

        $used = new backup_nested_element('used', array('id'), array(
            'jobtrackerid', 'elementid', 'sortorder', 'canbemodifiedby', 'active'));

        $jobs = new backup_nested_element('jobs');

        $job = new backup_nested_element('job', array('id'), array(
            'jobtrackerid', 'company', 'contact', 'contactphone', 'contactmail', 'position', 'notes', 'notesformat', 'timecreated', 'lastmodified', 'userid', 'status', 'resolution', 'resolutionformat', 'resolutionpriority'));

        $attribs = new backup_nested_element('jobattributes');

        $attrib = new backup_nested_element('jobattribute', array('id'), array(
            'jobtrackerid', 'jobid', 'elementid', 'elementitemid', 'timemodified'));

        $ccs = new backup_nested_element('ccs');

        $cc = new backup_nested_element('cc', array('id'), array(
            'jobtrackerid', 'userid', 'jobid', 'events'));

        $comments = new backup_nested_element('comments');

        $comment = new backup_nested_element('comment', array('id'), array(
            'jobtrackerid', 'userid', 'jobid', 'comment', 'commentformat', 'datecreated'));

        $preferences = new backup_nested_element('preferences');

        $preference = new backup_nested_element('preference', array('id'), array(
            'jobtrackerid', 'userid', 'name', 'value'));

        $statechanges = new backup_nested_element('statechanges');

        $state = new backup_nested_element('change', array('id'), array(
            'jobtrackerid', 'jobid', 'userid', 'timechange', 'statusfrom', 'statusto'));

        // Build the tree
        // (love this)
        $jobtracker->add_child($elements);
        $elements->add_child($element);
        $element->add_child($elementitems);
        $elementitems->add_child($item);

        $jobtracker->add_child($usedelements);
        $usedelements->add_child($used);

        $jobtracker->add_child($jobs);
        $jobs->add_child($job);

        $job->add_child($attribs);
        $attribs->add_child($attrib);
        $job->add_child($ccs);
        $ccs->add_child($cc);
        $job->add_child($comments);
        $comments->add_child($comment);
        $job->add_child($ownerships);
        $ownerships->add_child($ownership);
        $job->add_child($statechanges);
        $statechanges->add_child($state);

        $jobtracker->add_child($preferences);
        $preferences->add_child($preference);

        // Define sources
        $jobtracker->set_source_table('jobtracker', array('id' => backup::VAR_ACTIVITYID));
        $element->set_source_table('jobtracker_element', array('course' => backup::VAR_COURSEID));
        $item->set_source_table('jobtracker_elementitem', array('elementid' => backup::VAR_PARENTID));
        $used->set_source_table('jobtracker_elementused', array('jobtrackerid' => backup::VAR_ACTIVITYID));

        if ($userinfo) {
            $issue->set_source_table('jobtracker_job', array('jobtrackerid' => backup::VAR_PARENTID));
            $attrib->set_source_table('jobtracker_jobattribute', array('jobtrackerid' => backup::VAR_ACTIVITYID, 'jobid' => backup::VAR_PARENTID));
            $cc->set_source_table('jobtracker_jobcc', array('jobtrackerid' => backup::VAR_ACTIVITYID, 'jobid' => backup::VAR_PARENTID));
            $comment->set_source_table('jobtracker_jobcomment', array('jobtrackerid' => backup::VAR_ACTIVITYID, 'jobid' => backup::VAR_PARENTID));
            $ownership->set_source_table('jobtracker_jobownership', array('jobtrackerid' => backup::VAR_ACTIVITYID, 'jobid' => backup::VAR_PARENTID));
            $state->set_source_table('jobtracker_state_change', array('jobtrackerid' => backup::VAR_ACTIVITYID, 'jobid' => backup::VAR_PARENTID));
            $preference->set_source_table('jobtracker_preferences', array('jobtrackerid' => backup::VAR_ACTIVITYID));
        }

        // Define id annotations
        // (none)
        $job->annotate_ids('user', 'userid');
        $job->annotate_ids('user', 'followedby');
        $job->annotate_ids('user', 'bywhomid');
        $cc->annotate_ids('user', 'userid');
        $comment->annotate_ids('user', 'userid');
        $ownership->annotate_ids('user', 'userid');
        $ownership->annotate_ids('user', 'bywhomid');
        $preference->annotate_ids('user', 'userid');
        $state->annotate_ids('user', 'userid');

        // Define file annotations
        $jobtracker->annotate_files('mod_jobtracker', 'intro', null); // This file area hasn't itemid
        $comment->annotate_files('mod_jobtracker', 'jobcomment', 'id');
        $job->annotate_files('mod_jobtracker', 'jobnotes', 'id'); 
        $job->annotate_files('mod_jobtracker', 'jobresolution', 'id'); 
        $attrib->annotate_files('mod_jobtracker', 'jobattribute', 'id');

        // Return the root element (jobtracker), wrapped into standard activity structure
        return $this->prepare_activity_structure($jobtracker);
    }
}
