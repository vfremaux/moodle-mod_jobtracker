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
 * @copyright 2010 onwards Valery Fremaux (valery.freamux@club-internet.fr)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_url_activity_task
 */

/**
 * Structure step to restore one jobtracker activity
 */
class restore_jobtracker_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $jobtracker = new restore_path_element('jobtracker', '/activity/jobtracker');
        $paths[] = $jobtracker;
        $elements = new restore_path_element('jobtracker_element', '/activity/jobtracker/elements/element');
        $paths[] = $elements;
        $elementitem = new restore_path_element('jobtracker_elementitem', '/activity/jobtracker/elements/element/elementitems/elementitem');
        $paths[] = $elementitem;
        $usedelement = new restore_path_element('jobtracker_usedelement', '/activity/jobtracker/usedelements/usedelement');
        $paths[] = $usedelement;
        
        if ($userinfo){
            $paths[] = new restore_path_element('jobtracker_job', '/activity/jobtracker/jobs/job');
            $paths[] = new restore_path_element('jobtracker_jobattribute', '/activity/jobtracker/jobs/job/attribs/attrib');
            $paths[] = new restore_path_element('jobtracker_jobcc', '/activity/jobtracker/jobs/job/ccs/cc');
            $paths[] = new restore_path_element('jobtracker_jobcomment', '/activity/jobtracker/jobs/job/comments/comment');
            $paths[] = new restore_path_element('jobtracker_jobownership', '/activity/jobtracker/jobs/job/ownerships/ownership');
            $paths[] = new restore_path_element('jobtracker_state_change', '/activity/jobtracker/jobs/job/statechanges/state');
            $paths[] = new restore_path_element('jobtracker_preferences', '/activity/jobtracker/preferences/preference');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_jobtracker($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the label record
        $newitemid = $DB->insert_record('jobtracker', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function after_execute() {
        global $DB;

        // remap element used to real values
        if ($used = $DB->get_records('jobtracker_elementused', array('jobtrackerid' => $this->get_new_parentid('jobtracker')))){
            foreach($used as $u){
                $u->elementid = $this->get_mappingid('jobtracker_element', $u->elementid);
                $DB->update_record('jobtracker_elementused', $u);
             }
        }        
        
        // Add jobtracker related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_jobtracker', 'intro', null);
    }

    protected function process_jobtracker_element($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('jobtracker_element', $data);
        $this->set_mapping('jobtracker_element', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_jobtracker_elementitem($data) {
        global $DB;
        
        $data = (object)$data;
        $oldid = $data->id;

        $data->elementid = $this->get_mappingid('jobtracker_element', $data->elementid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('jobtracker_elementitem', $data);
        $this->set_mapping('jobtracker_elementitem', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_jobtracker_usedelement($data) {
        global $DB;
        
        $data = (object)$data;
        $oldid = $data->id;

        $data->jobtrackerid = $this->get_new_parentid('jobtracker');
        $data->canbemodifiedby = $this->get_mappingid('user', $data->canbemodifiedby);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('jobtracker_elementused', $data);
        $this->set_mapping('jobtracker_elementused', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_jobtracker_job($data) {
        global $DB;
        
        $data = (object)$data;
        $oldid = $data->id;

        $data->jobtrackerid = $this->get_new_parentid('jobtracker');

        $data->userid = $this->get_mappingid('user', $data->userid);

        // $data->timemodified = $this->apply_date_offset($data->timemodified);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('jobtracker_job', $data);
        $this->set_mapping('jobtracker_job', $oldid, $newitemid, false); // Has no related files

        $this->add_related_files('mod_jobtracker', 'jobcomment', 'jobtracker_job', null, $oldid);
        $this->add_related_files('mod_jobtracker', 'jobdescription', 'jobtracker_job', null, $oldid);
        $this->add_related_files('mod_jobtracker', 'jobresolution', 'jobtracker_job', null, $oldid);
    }

    protected function process_jobtracker_jobattribute($data) {
        global $DB;
        
        $data = (object)$data;
        $oldid = $data->id;

        $data->jobtrackerid = $this->get_mappingid('jobtracker', $data->jobtrackerid);
        $data->jobid = $this->get_new_parentid('job');

        $data->elementid = $this->get_mappingid('jobtracker_element', $data->elementid);
        $data->elementitemid = $this->get_mappingid('jobtracker_elementitem', $data->elementitemid);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('jobtracker_jobattribute', $data);
        // needs no mapping as terminal record
        $this->set_mapping('jobtracker_jobattribute', $oldid, $newitemid, false); // Has no related files

        $this->add_related_files('mod_jobtracker', 'jobattribute', 'jobtracker_jobattribute', null, $oldid);
    }

    protected function process_jobtracker_jobcc($data) {
        global $DB;
        
        $data = (object)$data;

        $oldid = $data->id;

        $data->jobtrackerid = $this->get_mappingid('jobtracker', $data->jobtrackerid);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->jobid = $this->get_new_parentid('job');

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('jobtracker_jobcc', $data);
        // needs no mapping as terminal record
        // $this->set_mapping('jobtracker_jobcc', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_jobtracker_jobcomment($data) {
        global $DB;

        $data = (object)$data;

        $oldid = $data->id;

        $data->jobtrackerid = $this->get_mappingid('jobtracker', $data->jobtrackerid);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->jobid = $this->get_new_parentid('job');

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('jobtracker_jobcomment', $data);
        // needs no mapping as terminal record
        $this->set_mapping('jobtracker_jobcomment', $oldid, $newitemid, false); // Has no related files

        $this->add_related_files('mod_jobtracker', 'jobcomment', 'jobtracker_jobcomment', null, $oldid);
    }

    protected function process_jobtracker_jobownership($data) {
        global $DB;
        
        $data = (object)$data;

        $data->jobtrackerid = $this->get_mappingid('jobtracker', $data->jobtrackerid);
        $data->jobid = $this->get_new_parentid('job');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->bywhomid = $this->get_mappingid('user', $data->bywhomid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('jobtracker_jobownership', $data);
        // needs no mapping as terminal record
        // $this->set_mapping('jobtracker_ownership', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_jobtracker_preferences($data) {
        global $DB;
        
        $data = (object)$data;

        $data->jobtrackerid = $this->get_new_parentid('jobtracker');
        $data->userid = $this->get_mappingid('user', $data->userid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('jobtracker_preferences', $data);
        // needs no mapping as terminal record
        // $this->set_mapping('jobtracker_preferences', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_jobtracker_state_change($data) {
        global $DB;
        
        $data = (object)$data;

        $data->jobtrackerid = $this->get_mappingid('jobtracker', $data->jobtrackerid);
        $data->jobid = $this->get_new_parentid('job');
        $data->userid = $this->get_mappingid('user', $data->userid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('jobtracker_state_change', $data);
        // needs no mapping as terminal record
        // $this->set_mapping('jobtracker_state_change', $oldid, $newitemid, false); // Has no related files
    }

}
