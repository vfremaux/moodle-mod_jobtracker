<?php 

/**
 * @package mod-jobtracker
 * @category mod
 * @author Clifford Tham, Valery Fremaux > 1.8
 * @date 02/12/2007
 *
 * From for showing element list
 */

if (!defined('MOODLE_INTERNAL')) {
    //  It must be included from view.php in mod/jobtracker;
    die('Direct access to this script is forbidden.');
}

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // course ID

echo $OUTPUT->box_start('generalbox', 'description');

$types = jobtracker_getelementtypes();
$elementtypeurls = array();

foreach ($types as $type) {
    $typecreateurl = new moodle_url('/mod/jobtracker/editelement.php', array('id' => $id, 'type' => $type));
    $elementtypeurls[''.$typecreateurl] = get_string($type, 'jobtracker');
}

print_string('createnewelement', 'jobtracker');
echo $OUTPUT->url_select($elementtypeurls, null, array('' => 'choosedots'));

echo $OUTPUT->box_end();
echo $OUTPUT->box_start('generalbox', 'description');

jobtracker_loadelements($jobtracker, $elements);

echo $OUTPUT->heading(get_string('elements', 'jobtracker'));

$localstr = get_string('local', 'jobtracker');
$namestr = get_string('name');
$typestr = get_string('type', 'jobtracker');
$cmdstr = get_string('action', 'jobtracker');

unset($table);
$table = new html_table();
$table->head = array("<b>$cmdstr</b>", "<b>$namestr</b>", "<b>$localstr</b>", "<b>$typestr</b>");
$table->width = '100%';
$table->size = array(100, 250, 50, 50);
$table->align = array('left', 'center', 'center', 'center'); 

if (!empty($elements)) {
    // Clean list from used elements.
    foreach ($elements as $id => $element) {
        if (in_array($element->id, array_keys($used))) {
            unset($elements[$id]);
        }
    }

    // Make list.
    foreach ($elements as $element) {

        $name = format_string($element->description);
        $name .= '<br />';
        $name .= '<span style="font-size:70%">';
        $name .= $element->name;
        $name .= '</span>';
        if ($element->hasoptions() && empty($element->options)) {
            $name .= ' <span class="error">('.get_string('nooptions', 'jobtracker').')</span>';
        }
        $params = array('id' => $cm->id, 'view' => 'admin', 'what' => 'addelement', 'elementid' => $element->id);
        $addurl = new moodle_url('/mod/jobtracker/view.php', $params);
        $pix = $OUTPUT->pix_icon('t/moveleft', get_string('addtothejobtracker', 'jobtracker'), 'core');
        $actions = '&nbsp;<a href="'.$addurl.'">'.$pix.'</a>';

        $params = array('id' => $id, 'elementid' => $element->id);
        $editoptionsurl = new moodle_url('/mod/jobtracker/editelementoptions.php', $params);
        $pix = $OUTPUT->pix_icon('editoptions', get_string('editoptions', 'jobtracker'), 'mod_jobtracker');
        $actions .= '&nbsp;<a href="'.$editoptionsurl.'">'.$pix.'</a>';

        $editurl = new moodle_url('/mod/jobtracker/editelement.php', array('id' => $id, 'elementid' => $element->id));
        $pix = $OUTPUT->pix_icon('t/edit', get_string('editproperties', 'jobtracker'), 'core');
        $actions .= '&nbsp;<a href="'.$editurl.'">'.$pix.'</a>';

        $params = array('id' => $id, 'elementid' => $element->id, 'what' => 'deleteelement');
        $deleteurl = new moodle_url('/mod/jobtracker/view.php', $params);
        $actions .= '&nbsp;<a href="'.$deleteurl.'"><img src="'.$OUTPUT->pix_icon('t/delete', get_string('delete'), 'core') .'</a>';

        $local = '';
        if ($element->course == $COURSE->id) {
            $local = $OUTPUT->pix_icon('i/course', '', 'core');
        }
        $type = $OUTPUT->pix_icon("types/{$element->type}", '', 'mod_jobtracker');
        $table->data[] = array($actions, $name, $local, $type);
    }
    echo html_writer::table($table);
} else {
    echo '<center>';
    print_string('noelements', 'jobtracker');
    echo '<br /></center>';
}
echo $OUTPUT->box_end();
