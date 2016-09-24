<?php

/**
 * This view allows checking deck states
 * 
 * @package mod_jobtracker
 * @category mod
 * @author Valery Fremaux
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * overrides moodleform for test setup
 */
class mod_jobtracker_mod_form extends moodleform_mod {

    function definition() {
        global $CFG, $COURSE, $DB;

        $mform    =& $this->_form;
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        $mform->setType('name', PARAM_CLEANHTML);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor(true, get_string('intro', 'jobtracker'));

        // $mform->addRule('summary', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'ticketprefix', get_string('ticketprefix', 'jobtracker'), array('size' => 5));
        $mform->setType('ticketprefix', PARAM_TEXT);
        $mform->setAdvanced('ticketprefix');

        $stateprofileopts = array(
            JOBTRACK_ENABLED_OPEN => get_string('open', 'jobtracker'),
            JOBTRACK_ENABLED_SHORTLIST => get_string('shortlist', 'jobtracker'),
            JOBTRACK_ENABLED_WAITINGEVENT => get_string('waitingevent', 'jobtracker'),
            JOBTRACK_ENABLED_TOREFRESH => get_string('torefresh', 'jobtracker'),
            JOBTRACK_ENABLED_MEETINGSCHEDULED => get_string('meetingscheduled', 'jobtracker'),
            JOBTRACK_ENABLED_MEETINGDONE => get_string('meetingdone', 'jobtracker'),
            JOBTRACK_ENABLED_CONCLUDED => get_string('concluded', 'jobtracker'),
            JOBTRACK_ENABLED_DEAD => get_string('dead', 'jobtracker'),
        );
        $select = &$mform->addElement('select', 'stateprofile', get_string('stateprofile', 'jobtracker'), $stateprofileopts);
        $mform->setType('stateprofile', PARAM_INT);
        $select->setMultiple(true);
        $mform->setAdvanced('stateprofile');

        $mform->addElement('checkbox', 'allownotifications', get_string('notifications', 'jobtracker'));
        $mform->addHelpButton('allownotifications', 'notifications', 'jobtracker');

        if (isset($this->_cm->id) && $assignableusers = get_users_by_capability(context_module::instance($this->_cm->id), 'mod/jobtracker:resolve', 'u.id,'.get_all_user_name_fields(true, 'u'), 'lastname,firstname')){
            $useropts[0] = get_string('none');
            foreach($assignableusers as $assignable){
                 $useropts[$assignable->id] = fullname($assignable);
            }
            $mform->addElement('select', 'defaultassignee', get_string('defaultassignee', 'jobtracker'), $useropts);
            $mform->addHelpButton('defaultassignee', 'defaultassignee', 'jobtracker');
            $mform->disabledIf('defaultassignee', 'supportmode', 'eq', 'taskspread');
            $mform->setAdvanced('defaultassignee');
        } else {
            $mform->addElement('hidden', 'defaultassignee', 0);
        }
        $mform->setType('defaultassignee', PARAM_INT);

        $options['idnumber'] = true;
        $options['groups'] = false;
        $options['groupings'] = false;
        $options['gradecat'] = false;
        $this->standard_coursemodule_elements($options);
        $this->add_action_buttons();
    }

     function set_data($defaults) {

          if (!property_exists($defaults, 'enabledstates')){
               $defaults->stateprofile = array();

               $defaults->stateprofile[] = JOBTRACK_ENABLED_OPEN; // state when opened by the assigned
               $defaults->stateprofile[] = JOBTRACK_ENABLED_SHORTLIST; // state when asigned tells he starts processing
               $defaults->stateprofile[] = JOBTRACK_ENABLED_WAITINGEVENT; // state when ticket is blocked by an external cause
               $defaults->stateprofile[] = JOBTRACK_ENABLED_TOREFRESH; // state when issue has an identified solution provided by assignee
               $defaults->stateprofile[] = JOBTRACK_ENABLED_MEETINGSCHEDULED; // state when issue is no more relevant by external cause
               $defaults->stateprofile[] = JOBTRACK_ENABLED_MEETINGDONE; // state when assignee submits issue to requirer and needs acknowledge
               $defaults->stateprofile[] = JOBTRACK_ENABLED_CONCLUDED; // state when solution is realy published in production (not testing)
               $defaults->stateprofile[] = JOBTRACK_ENABLED_DEAD; // state when solution is realy published in production (not testing)
          } else {
               $defaults->stateprofile = array();
               if ($defaults->enabledstates & JOBTRACK_ENABLED_OPEN) $defaults->stateprofile[] = JOBTRACK_ENABLED_OPEN;
               if ($defaults->enabledstates & JOBTRACK_ENABLED_SHORTLIST) $defaults->stateprofile[] = JOBTRACK_ENABLED_SHORTLIST;
               if ($defaults->enabledstates & JOBTRACK_ENABLED_WAITINGEVENT) $defaults->stateprofile[] = JOBTRACK_ENABLED_WAITINGEVENT;
               if ($defaults->enabledstates & JOBTRACK_ENABLED_TOREFRESH) $defaults->stateprofile[] = JOBTRACK_ENABLED_TOREFRESH;
               if ($defaults->enabledstates & JOBTRACK_ENABLED_MEETINGSCHEDULED) $defaults->stateprofile[] = JOBTRACK_ENABLED_MEETINGSCHEDULED;
               if ($defaults->enabledstates & JOBTRACK_ENABLED_MEETINGDONE) $defaults->stateprofile[] = JOBTRACK_ENABLED_MEETINGDONE;
               if ($defaults->enabledstates & JOBTRACK_ENABLED_CONCLUDED) $defaults->stateprofile[] = JOBTRACK_ENABLED_CONCLUDED;
               if ($defaults->enabledstates & JOBTRACK_ENABLED_DEAD) $defaults->stateprofile[] = JOBTRACK_ENABLED_DEAD;
          }

          parent::set_data($defaults);

     }

     function definition_after_data(){
       $mform    =& $this->_form;
     }

     function validation($data, $files = null) {
         $errors = array();
         return $errors;
     }

    function add_completion_rules() {
        $mform =& $this->_form;

        $mform->addElement('checkbox', 'completionhasconcluded', get_string('hasconcluded', 'jobtracker'), get_string('completionhasconcluded', 'jobtracker'));

        return array('completionhasconcluded');
    }

    function completion_rule_enabled($data) {
        return(!empty($data['completionhasconcluded']));
    }
}
