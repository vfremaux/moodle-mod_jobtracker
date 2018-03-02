<?php 

/**
* @package mod-jobtracker
* @category mod
* @author Clifford Tham, Valery Fremaux > 1.8
* @date 02/12/2007
*
* From for showing used element list
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    //  It must be included from view.php in mod/jobtracker
}

$OUTPUT->box_start('center', '100%', '', '', 'generalbox', 'description');
$OUTPUT->box_end(); 
$OUTPUT->box_start('center', '100%', '', '', 'generalbox', 'description');

jobtracker_loadelementsused($jobtracker, $used);

echo $OUTPUT->heading(get_string('elementsused', 'jobtracker'));

$orderstr = get_string('order', 'jobtracker');
$namestr = get_string('name');
$typestr = get_string('type', 'jobtracker');
$cmdstr = get_string('action', 'jobtracker');

$table = new html_table();
$table->head = array("<b>$orderstr</b>", "<b>$namestr</b>", "<b>$typestr</b>", "<b>$cmdstr</b>");
$table->width = '100%';
$table->size = array(20, 250, 50, 100);
$table->align = array('left', 'center', 'center', 'center'); 

if (!empty($used)) {
    foreach ($used as $element) {
        $icontype = $OUTPUT->pix_icon('/types/{$element->type}', '', 'mod_jobtracker');
        if ($element->sortorder > 1) {
            $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'raiseelement', 'elementid' => $element->id);
            $viewurl = new moodle_url('/mod/jobtracker/view.php', $params);
            $actions = '&nbsp;<a href="'.$viewurl.'">'.$OUTPUT->pix_icon('/t/up', '', 'core').'</a>';
        } else {
            $actions = '&nbsp;'.$OUTPUT->pix_icon('up_shadow', '', 'mod_jobtracker');
        }
        if ($element->sortorder < count($used)) {
            $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'lowerelement', 'elementid' => $element->id);
            $viewurl = new moodle_url('/mod/jobtracker/view.php', $params);
            $actions .= '&nbsp;<a href="'.$viewurl.'">'.$OUTPUT->pix_icon('/t/down', '', 'core').'</a>';
        } else {
            $actions .= '&nbsp;'.$OUTPUT->pix_icon('down_shadow', '', 'mod_jobtracker');
        }
        $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'editelement', 'elementid' => $element->id, 'used' => 1);
        $viewurl = new moodle_url('/mod/jobtracker/view.php', $params);
        $actions .= '&nbsp;<a href="'.$viewurl.'">'.$OUTPUT->pix_icon('/t/edit', get_string('update'), 'core').'</a>';

        $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'viewelementoptions', 'elementid' => $element->id);
        $viewurl = new moodle_url('/mod/jobtracker/view.php', $params);
        $actions .= '&nbsp;<a href="'.$viewurl.'">'.$OUTPUT->pix_icon('editoptions', get_string('editoptions', 'mod_jobtracker'), 'mod_jobtracker').'</a>';

        $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'removeelement', 'usedid' => $element->id);
        $viewurl = new moodle_url('/mod/jobtracker/view.php', $params);
        $actions .= '&nbsp;<a href="'.$viewurl.'">'.$OUTPUT->pix_icon('/t/right', '', 'core').'</a>';

        if ($element->active){
            $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'setinactive', 'usedid' => $element->id);
            $viewurl = new moodle_url('/mod/jobtracker/view.php', $params);
            $actions .= '&nbsp;<a href="'.$viewurl.'">'.$OUTPUT->pix_icon('/t/hide', get_string('show'), 'core').'</a>';
        } else {
            $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'setactive', 'usedid' => $element->id);
            $viewurl = new moodle_url('/mod/jobtracker/view.php', $params);
            $actions .= '&nbsp;<a href="'.$viewurl.'">'.$OUTPUT->pix_icon('/t/show', get_string('hide'), 'core').'</a>';
        }
        $table->data[] = array($element->sortorder, format_string($element->description), $icontype, $actions);
    }
    echo html_writer::table($table);
} else {
    echo '<center>';
    print_string('noelements', 'jobtracker');
    echo '<br/></center>';
}

$OUTPUT->box_end();
