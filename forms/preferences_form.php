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
 * @package mod_jobtracker
 * @category mod
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

require_once $CFG->libdir.'/formslib.php';

class PreferencesForm extends moodleform {

    var $context;

    /**
     * Dynamically defines the form using elements setup in jobtracker instance
     *
     *
     */
    function definition() {
        global $DB, $COURSE, $USER;

        $jobtracker = $this->_customdata['jobtracker'];
        $storedprefs = $this->_customdata['storedprefs'];
        jobtracker_loadpreferences($jobtracker->id, $USER->id);

        $yesno = array('0' => get_string('no'), '1' => get_string('yes'));

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'what', 'saveprefs');
        $mform->setType('what', PARAM_TEXT);

        $mform->addElement('hidden', 'view', 'profile');
        $mform->setType('view', PARAM_TEXT);

        // $mform->addElement('header', 'h0', get_string('notifications', 'jobtracker');

        $mform->addElement('header', 'h1', get_string('notifications', 'jobtracker'));

        if ($jobtracker->enabledstates & JOBTRACK_ENABLED_OPEN) {
            $mform->addElement('select', 'open', get_string('unsetwhenopens', 'jobtracker'), $yesno);
        }

        if ($jobtracker->enabledstates & JOBTRACK_ENABLED_SHORTLIST) {
            $mform->addElement('select', 'shortlist', get_string('unsetwhenshortlisted', 'jobtracker'), $yesno);
        }

        if ($jobtracker->enabledstates & JOBTRACK_ENABLED_WAITINGEVENT) {
            $mform->addElement('select', 'waitingevent', get_string('unsetwhenwaitsevent', 'jobtracker'), $yesno);
        }

        if ($jobtracker->enabledstates & JOBTRACK_ENABLED_TOREFRESH) {
            $mform->addElement('select', 'torefresh', get_string('unsetwhentorefresh', 'jobtracker'), $yesno);
        }

        if ($jobtracker->enabledstates & JOBTRACK_ENABLED_MEETINGSCHEDULED) {
            $mform->addElement('select', 'meetingscheduled', get_string('unsetwhenmeetingscheduled', 'jobtracker'), $yesno);
        }

        if ($jobtracker->enabledstates & JOBTRACK_ENABLED_MEETINGDONE) {
            $mform->addElement('select', 'meetingdone', get_string('unsetwhenmeetingdone', 'jobtracker'), $yesno);
        }

        if ($jobtracker->enabledstates & JOBTRACK_ENABLED_CONCLUDED) {
            $mform->addElement('select', 'concluded', get_string('unsetwhenconcluded', 'jobtracker'), $yesno);
        }

        if ($jobtracker->enabledstates & JOBTRACK_ENABLED_DEAD) {
            $mform->addElement('select', 'dead', get_string('unsetwhendead', 'jobtracker'), $yesno);
        }

        $mform->addElement('select', 'oncomment', get_string('unsetoncomment', 'jobtracker'), $yesno);

        $this->add_action_buttons(false);
    }

    function validation($data, $files = null) {
    }

}