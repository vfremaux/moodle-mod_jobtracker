<?php

/**
 * @package mod-jobtracker
 * @category mod
 * @author Valery Fremaux > 1.8
 * @date 02/12/2007
 *
 * Controller for all "element management" related views
 *
 * @usecase 'createelement'
 * @usecase 'doaddelement'
 * @usecase 'editelement'
 * @usecase 'doupdateelement'
 * @usecase 'deleteelement'
 * @usecase 'submitelementoption'
 * @usecase 'viewelementoption'
 * @usecase 'editelementoption'
 * @usecase 'updateelementoption'
 * @usecase 'moveelementoptionup'
 * @usecase 'moveelementoptiondown'
 * @usecase 'addelement'
 * @usecase 'removeelement'
 * @usecase 'raiseelement'
 * @usecase 'lowerelement'
 * @usecase 'localparent'
 * @usecase 'remoteparent'
 * @usecase 'unbind'
 * @usecase 'setinactive'
 * @usecase 'setactive'
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/jobtracker
}

/************************************* Create element form *****************************/
if ($action == 'createelement') {
    $form = new StdClass;
    $form->type = required_param('type', PARAM_ALPHA);
    // $elementid = optional_param('elementid', null, PARAM_INT);
    $form->action = 'doaddelement';
    include $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/editelement.html';
    return -1;
}
/************************************* add an element *****************************/
elseif ($action == 'doaddelement') {
    $form = new StdClass;
    $form->name = required_param('name', PARAM_ALPHANUM);
    $form->description = required_param('description', PARAM_CLEANHTML);
    $form->type = required_param('type', PARAM_ALPHA);
    $form->shared = optional_param('shared', 0, PARAM_INT);
    $errors = array();
    if (empty($form->name)) {
        $error = new StdClass;
        $error->message = get_string('namecannotbeblank', 'jobtracker');
        $error->on = 'name';
        $errors[] = $error;
    }

    if (!count($errors)) {
        $element = new StdClass;
        $element->name = $form->name;
        $element->description = $form->description;
        $form->type = $element->type = $form->type;
        $element->course = ($form->shared) ? 0 : $COURSE->id;
        if (!$form->elementid = $DB->insert_record('jobtracker_element', $element)) {
            print_error('errorcannotcreateelement', 'jobtracker', $url);
        }

        $elementobj = jobtrackerelement::find_instance_by_id($jobtracker, $form->elementid);
        if ($elementobj->hasoptions()) {
            // Bounces to the option editor
            $form->name = '';
            $form->description = '';

            // prepare use case bounce to further code (later in controller).
            $bounce_elementid = $form->elementid;
            $action = 'viewelementoptions';
        }
    } else {
        $form->name = '';
        $form->description = '';
        include $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/editelement.html';
    }
}
/************************************* Edit an element form *****************************/
elseif ($action == 'editelement') {
    $form = new StdClass;
    $form->elementid = required_param('elementid', PARAM_INT);
    if ($form->elementid != null) {
        $element = jobtrackerelement::find_instance_by_id($jobtracker, $form->elementid);
        $form->type = $element->type;
        $form->name = $element->name;
        $form->description = addslashes($element->description);
        $form->format = $element->format;
        $form->shared = ($element->course == 0) ;
        $form->action = 'doupdateelement';
        include $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/editelement.html';
    } else {
        print_error ('errorinvalidelementid', 'jobtracker', $url);
    }
    return -1;
}
/************************************* Update an element *****************************/
if ($action == 'doupdateelement') {
    $form = new StdClass;
    $form->elementid = required_param('elementid', PARAM_INT);
    $form->name = required_param('name', PARAM_ALPHANUM);
    $form->description = required_param('description', PARAM_CLEANHTML);
    $form->format = optional_param('format', '', PARAM_INT);
    $form->type = required_param('type', PARAM_ALPHA);
    $form->shared = optional_param('shared', 0, PARAM_INT);

    if (empty($form->elementid)) {
        print_error('errorelementdoesnotexist', 'jobtracker', $url);
    }

    $errors = array();
    if (empty($form->name)) {
        $error = new StdClass;
        $error->message = get_string('namecannotbeblank', 'jobtracker');
        $error->on = 'name';
        $errors[] = $error;
    }

    if (!count($errors)) {
        $element = new StdClass;
        $element->id = $form->elementid;
        $element->name = $form->name;
        $element->type = $form->type;
        $element->description = $form->description;
        $element->format = $form->format;
        $element->course = ($form->shared) ? 0 : $COURSE->id ;
        if (!$DB->update_record('jobtracker_element', $element)) {
            print_error('errorcannotupdateelement', 'jobtracker', $url);
        }
    } else {
        $form->action = 'doupdateelement';
        include $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/editelement.html';
    }
}
/************************************ delete an element from available **********************/
if ($action == 'deleteelement') {
    $elementid = required_param('elementid', PARAM_INT);
    if (!jobtracker_iselementused($jobtracker->id, $elementid)) {
        if (!$DB->delete_records ('jobtracker_element', array('id' =>  $elementid))) {
            print_error('errorcannotdeleteelement', 'jobtracker', $url);
        }
        $DB->delete_records('jobtracker_elementitem', array('elementid' => $elementid));
    } else {
        // should not even be proposed by the GUI;
        print_error('errorcannotdeleteelement', 'jobtracker', $url);
    }
}    
/************************************* add an element option *****************************/
if ($action == 'submitelementoption') {
    $form = new StdClass;
    $form->elementid = required_param('elementid', PARAM_INT);
    $form->name = required_param('name', PARAM_ALPHANUM);
    $form->description = required_param('description', PARAM_CLEANHTML);
    $form->type = required_param('type', PARAM_ALPHA);
    $element = $DB->get_record('jobtracker_element', array('id' => $form->elementid));
    // check validity
    $errors = array();
    if ($DB->count_records('jobtracker_elementitem', array('elementid' => $form->elementid, 'name' => $form->name))) {
        $error = new StdClass;
        $error->message = get_string('optionisused', 'jobtracker', $url);
        $error->on = 'name';
        $errors[] = $error;
    }

    if ($form->name == '') {
        unset($error);
        $error = new StdClass;
        $error->message = get_string('optionnamecannotbeblank', 'jobtracker');
        $error->on = 'name';
        $errors[] = $error;
    }

    if ($form->description == '') {
        unset($error);
        $error = new StdClass;
        $error->message = get_string('descriptionisempty', 'jobtracker');
        $error->on = 'description';
        $errors[] = $error;
    }
    if (!count($errors)) {
        $option = new StdClass;
        $option->name = strtolower($form->name);
        $option->description = $form->description;
        $option->elementid = $form->elementid;
        $countoptions = 0 + $DB->count_records('jobtracker_elementitem', array('elementid' => $form->elementid));
        $option->sortorder = $countoptions + 1;
        if (!$DB->insert_record('jobtracker_elementitem', $option)) {
            print_error('errorcannotcreateelementoption', 'jobtracker', $url);
        }
        $form->name = '';
        $form->description = '';
    } else {
        // print errors;
        $errorstr = '';
        foreach ($errors as $anError) {
            $errorstrs[] = $anError->message;
        }
        echo $OUTPUT->box(implode('<br/>', $errorstrs), 'center', '70%', '', 5, 'errorbox');
    }
    echo $OUTPUT->heading(get_string('editoptions', 'jobtracker'));
    $element = jobtrackerelement::find_instance_by_id($jobtracker, $form->elementid);
    $element->optionlistview($cm);
    $caption = get_string('addanoption', 'jobtracker');
    echo $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'jobtracker', false));
    include $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/editoptionform.html';
    return -1;
}
/************************************* edit an element option *****************************/
if ($action == 'viewelementoptions') {
    $form = new StdClass();
    $form->elementid = optional_param('elementid', @$bounce_elementid, PARAM_INT);
    if ($form->elementid) {
        $element = jobtrackerelement::find_instance_by_id($jobtracker, $form->elementid);
        $form->type = $element->type;
        echo $OUTPUT->heading(get_string('editoptions', 'jobtracker'));
        echo '<center>';
        $element = jobtrackerelement::find_instance_by_id($jobtracker, $form->elementid);
        $element->optionlistview($cm);
        echo $OUTPUT->heading(get_string('addanoption', 'jobtracker'));
        include $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/editoptionform.html';
        echo '</center>';
    } else {
        print_error('errorcannotviewelementoption', 'jobtracker', $url);
    }
    return -1;
}
/************************************* delete an element option *****************************/
if ($action == 'deleteelementoption') {
    $form = new StdClass;
    $form->elementid = optional_param('elementid', null, PARAM_INT);
    $form->optionid = required_param('optionid', PARAM_INT);
    $element = jobtrackerelement::getelement($jobtracker, $form->elementid);
    $deletedoption = $element->getoption($form->optionid);
    $form->type = $element->type;

    if ($DB->get_records('jobtracker_issueattribute', array('elementitemid' => $form->optionid))) {
        print_error('errorcannotdeleteoption', 'jobtracker');
    }
    if (!$DB->delete_records('jobtracker_elementitem', array('id' => $form->optionid))) {
        print_error('errorcannotdeleteoption', 'jobtracker');
    }

    // renumber higher records;
    $sql = "
        UPDATE
            {jobtracker_elementitem}
        SET
            sortorder = sortorder - 1
        WHERE
            elementid = ? AND
            sortorder > ?
    ";
    $DB->execute($sql, array($form->elementid, $deletedoption->sortorder));
    echo $OUTPUT->heading(get_string('editoptions', 'jobtracker'));
    $element = jobtrackerelement::find_instance_by_id($jobtracker, $form->elementid);
    $element->optionlistview($cm);
    $caption = get_string('addanoption', 'jobtracker');
    echo $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'jobtracker', false));
    include $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/editoptionform.html';
    return -1;
}
/************************************* edit an element option *****************************/
if ($action == 'editelementoption') {
    $form = new StdClass;
    $form->elementid = required_param('elementid', PARAM_INT);
    $form->optionid = required_param('optionid', PARAM_INT);
    $element = jobtrackerelement::find_instance_by_id($jobtracker, $form->elementid);
    $option = $element->getoption($form->optionid);
    $form->type = $element->type;
    $form->name = $option->name;
    $form->description = $option->description;
    include $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/updateoptionform.html';
    return -1;
}
/************************************* edit an element option *****************************/
if ($action == 'updateelementoption') {
    $form = new Stdclass();
    $form->elementid = required_param('elementid', PARAM_INT);
    $form->optionid = required_param('optionid', PARAM_INT);
    $form->name = required_param('name', PARAM_ALPHANUM);
    $form->description = required_param('description', PARAM_CLEANHTML);
    $form->format = optional_param('format', 0, PARAM_INT);

    $element = jobtrackerelement::find_instance_by_id($jobtracker, $form->elementid);
    $form->type = $element->type;
    // check validity
    $errors = array();
    if ($DB->count_records_select('jobtracker_elementitem', "elementid = $form->elementid AND name = '$form->name' AND id != $form->optionid ")) {
        $error = new StdClass;
        $error->message = get_string('optionisused', 'jobtracker');
        $error->on = 'name';
        $errors[] = $error;
    }

    if ($form->name == '') {
        unset($error);
        $error = new StdClass;
        $error->message = get_string('optionnamecannotbeblank', 'jobtracker');
        $error->on = 'name';
        $errors[] = $error;
    }

    if ($form->description == '') {
        unset($error);
        $error = new StdClass;
        $error->message = get_string('descriptionisempty', 'jobtracker');
        $error->on = 'description';
        $errors[] = $error;
    }

    if (!count($errors)) {
        $update = new StdClass;
        $update->id = $form->optionid;
        $update->name = $form->name;
        $update->description = $form->description;
        $update->format = $form->format;
        if ($DB->update_record('jobtracker_elementitem', $update)) {
            echo $OUTPUT->heading(get_string('editoptions', 'jobtracker'));
            $element = jobtrackerelement::find_instance_by_id($jobtracker, $form->elementid);
            $element->optionlistview($cm);
            echo $OUTPUT->heading(get_string('addanoption', 'jobtracker'));
            include $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/editoptionform.html';
        } else {
            print_error('errorcannotupdateoptionbecauseused', 'jobtracker', $url);
        }
    } else {
        /// print errors
        $errorstr = '';
        foreach($errors as $anError) {
            $errorstrs[] = $anError->message;
        }
        echo $OUTPUT->box(implode("<br/>", $errorstrs), 'center', '70%', '', 5, 'errorbox');
        include $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/updateoptionform.html';
    }
    return -1;
}
/********************************** move an option up in list ***************************/
if ($action == 'moveelementoptionup') {
    $form = new StdClass;
    $form->elementid = required_param('elementid', PARAM_INT);
    $form->optionid = required_param('optionid', PARAM_INT);

    $option = $DB->get_record('jobtracker_elementitem', array('elementid' => $form->elementid, 'id' => $form->optionid));
    $element = jobtrackerelement::find_instance_by_id($jobtracker, $form->elementid);
    $form->type = $element->type;
    $option->id = $form->optionid;
    $sortorder = $DB->get_field('jobtracker_elementitem', 'sortorder', array('elementid' => $form->elementid, 'id' => $form->optionid));
    if ($sortorder > 1) {
        $option->sortorder = $sortorder - 1;
        $previousoption = new StdClass();
        $previousoption->id = $DB->get_field('jobtracker_elementitem', 'id', array('elementid' => $form->elementid, 'sortorder' => $sortorder - 1));
        $previousoption->sortorder = $sortorder;
        // swap options in database
        if (!$DB->update_record('jobtracker_elementitem', $option)) {
            print_error('errordbupdate', 'jobtracker', $url);
        }
        if (!$DB->update_record('jobtracker_elementitem', $previousoption)) {
            print_error('errordbupdate', 'jobtracker', $url);
        }
    }    
    echo $OUTPUT->heading(get_string('editoptions', 'jobtracker'));
    $element = jobtrackerelement::find_instance_by_id($jobtracker, $form->elementid);
    $element->optionlistview($cm);
    $caption = get_string('addanoption', 'jobtracker');
    echo $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'jobtracker', false));
    include $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/editoptionform.html';
    return -1;
}
/********************************** move an option down in list ***************************/
if ($action == 'moveelementoptiondown') {
    $form = new StdClass;
    $form->elementid = required_param('elementid', PARAM_INT);
    $form->optionid = required_param('optionid', PARAM_INT);

    $option = $DB->get_record('jobtracker_elementitem', array('elementid' => $form->elementid, 'id' => $form->optionid));
    $element = jobtrackerelement::find_instance_by_id($jobtracker, $form->elementid);
    $form->type = $element->type;
    $option->id = $form->optionid;
    $sortorder = $DB->get_field('jobtracker_elementitem', 'sortorder', array('elementid' => $form->elementid, 'id' => $form->optionid));
    if ($sortorder < $element->maxorder) {
        $option->sortorder = $sortorder + 1;
        $nextoption = new StdClass;
        $nextoption->id = $DB->get_field('jobtracker_elementitem', 'id', array('elementid' => $form->elementid, 'sortorder' => $sortorder + 1));
        $nextoption->sortorder = $sortorder;
        // swap options in database
        if (!$DB->update_record('jobtracker_elementitem', addslashes_recursive($option))) {
            print_error('errordbupdate', 'jobtracker', $url);
        }
        if (!$DB->update_record('jobtracker_elementitem', addslashes_recursive($nextoption))) {
            print_error('errordbupdate', 'jobtracker', $url);
        }
    }
    echo $OUTPUT->heading(get_string('editoptions', 'jobtracker'));
    $element = jobtrackerelement::find_instance_by_id($jobtracker, $form->elementid);
    $element->optionlistview($cm);
    $caption = get_string('addanoption', 'jobtracker');
    echo $OUTPUT->heading($caption . $OUTPUT->help_icon('options', 'jobtracker', false));
    include $CFG->dirroot.'/mod/jobtracker/classes/jobtrackercategorytype/editoptionform.html';
    return -1;
}
/********************************** add an element to be used ***************************/
if ($action == 'addelement') {
    $elementid = required_param('elementid', PARAM_INT);

    if (!jobtracker_iselementused($jobtracker->id, $elementid)) {
        /// Add element to element used table;
        $used = new StdClass;
        $used->elementid = $elementid;
        $used->jobtrackerid = $jobtracker->id;
        $used->canbemodifiedby = $USER->id;
        /// get last sort order
        $sortorder = 0 + $DB->get_field_select('jobtracker_elementused', 'MAX(sortorder)', "jobtrackerid = {$jobtracker->id} GROUP BY jobtrackerid");
        $used->sortorder = $sortorder + 1;
        if (!$DB->insert_record ('jobtracker_elementused', $used)) {
            print_error('errorcannotaddelementtouse', 'jobtracker', $url.'&amp;view=admin');
        }
    } else {
        // Feedback message that element is already in uses
        print_error('erroralreadyinuse', 'jobtracker', $url.'&amp;view=admin');
    }
}
/****************************** remove an element from usable list **********************/
if ($action == 'removeelement') {
    $usedid = required_param('usedid', PARAM_INT);
    if (!$DB->delete_records ('jobtracker_elementused', array('elementid' => $usedid, 'jobtrackerid' => $jobtracker->id))){    
        print_error('errorcannotdeleteelement', 'jobtracker', $url);
    }
}
/****************************** raise element pos in usable list **********************/
if ($action == 'raiseelement') {
    $usedid = required_param('elementid', PARAM_INT);
    $used = $DB->get_record('jobtracker_elementused', array('elementid' => $usedid, 'jobtrackerid' => $jobtracker->id));
    $previous = $DB->get_record('jobtracker_elementused', array('sortorder' => $used->sortorder - 1, 'jobtrackerid' => $jobtracker->id));
    $used->sortorder--;
    $previous->sortorder++;
    $DB->update_record('jobtracker_elementused', $used);
    $DB->update_record('jobtracker_elementused', $previous);    
}
/****************************** lower element pos in usable list **********************/
if ($action == 'lowerelement') {
    $usedid = required_param('elementid', PARAM_INT);
    $used = $DB->get_record('jobtracker_elementused', array('elementid' => $usedid, 'jobtrackerid' => $jobtracker->id));
    $next = $DB->get_record('jobtracker_elementused', array('sortorder' => $used->sortorder + 1, 'jobtrackerid' => $jobtracker->id));
    $used->sortorder++;
    $next->sortorder--;
    $DB->update_record('jobtracker_elementused', $used);    
    $DB->update_record('jobtracker_elementused', $next);    
}    
/*************************** Update parent jobtracker binding *******************************/    
if ($action == 'localparent') {
    $parent = optional_param('localjobtracker', null, PARAM_INT);

    if (!$DB->set_field('jobtracker', 'parent', $parent, array('id' => $jobtracker->id))) {
        print_error('errorcannotsetparent', 'jobtracker', $url);
    }
    $jobtracker->parent = $parent;
}
/*************************** Update remote parent jobtracker binding *******************************/    
if ($action == 'remoteparent') {
    $step = optional_param('step', 0, PARAM_INT);
    switch ($step) {
        case 1: { // we choose the host
            $parenthost = optional_param('remotehost', null, PARAM_RAW);
        }
        break;
        case 2: { // we choose the jobtracker
            $remoteparent = optional_param('remotejobtracker', null, PARAM_RAW);

            if (!$DB->set_field('jobtracker', 'parent', $remoteparent, array('id' => $jobtracker->id))){    
                print_error('errorcannotsetparent', 'jobtracker');
            }
        $jobtracker->parent = $remoteparent;
        $step = 0;
        break;
        }
    }
}
/*************************** unbinds any cascade  *******************************/
if ($action == 'unbind') {
    if (!$DB->set_field('jobtracker', 'parent', '', array('id' => $jobtracker->id))) { 
        print_error('errorcannotunbindparent', 'jobtracker', $url);
    }
    $jobtracker->parent = '';
}
/****************************** set a used element inactive for form **********************/
if ($action == 'setinactive') {
    $usedid = required_param('usedid', PARAM_INT);
    if (!$DB->set_field_select('jobtracker_elementused', 'active', 0, " elementid = ? && jobtrackerid = ? ", array($usedid, $jobtracker->id))) {
        print_error('errorcannothideelement', 'jobtracker', $url);
    }
}    
/****************************** set a used element active for form **********************/
if ($action == 'setactive') {
    $usedid = required_param('usedid', PARAM_INT);
    if (!$DB->set_field_select('jobtracker_elementused', 'active', 1, " elementid = ? && jobtrackerid = ? ", array($usedid, $jobtracker->id))) {
        print_error('errorcannotshowelement', 'jobtracker', $url);
    }
}
