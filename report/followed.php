<?php

if ($groupmode == NOGROUPS) {
    $followed = get_users_by_capability($context, 'mod/jobtracker:workon', 'u.id, u.username, u.firstname, u.lastname');
} else {
    $returnurl = new moodle_url('/mod/jobtracker/view.php', array('id' => $cm->id, 'view' => $view, 'screen' => $screen));
    groups_print_activity_menu($cm, $returnurl);
    $groupid = groups_get_activity_group($cm, true);
    $members = groups_get_members($groupid, 'u.id, u.username, u.firstname, u.lastname');
    $followed = array();
    foreach($members as $m) {
        if (has_capability('mod/jobtracker:workon', $context)) {
            $followed[$m->id] = $m;
        }
    }
}

if (empty($followed)) {
    echo $OUTPUT->notification(get_string('nofollowed', 'jobtracker'));
    echo $OUTPUT->footer();
    die;
}

echo $renderer->mentees_tracks($jobtracker, $followed);