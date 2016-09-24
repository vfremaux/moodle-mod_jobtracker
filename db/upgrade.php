<?php

function xmldb_jobtracker_upgrade($oldversion=0) {
/// This function does anything necessary to upgrade 
/// older versions to match current functionality 

    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2015011901) {

    // Define field fallbacktype to be added to customlabel.
        $table = new xmldb_table('jobtracker_element');
        $field = new xmldb_field('param1');
        $field->set_attributes(XMLDB_TYPE_CHAR, '32', null, null, null, null, 'type');

    // Launch add field parent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('param2');
        $field->set_attributes(XMLDB_TYPE_CHAR, '32', null, null, null, null, 'param1');

    // Launch add field parent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('param3');
        $field->set_attributes(XMLDB_TYPE_CHAR, '32', null, null, null, null, 'param2');

    // Launch add field parent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // jobtracker savepoint reached.
        upgrade_mod_savepoint(true, 2015011901, 'jobtracker');
    }

    if ($oldversion < 2015011902) {

        $table = new xmldb_table('jobtracker_job');
        $field = new xmldb_field('notesformat');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'notes');

    // Launch add field parent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // jobtracker savepoint reached.
        upgrade_mod_savepoint(true, 2015011902, 'jobtracker');
    }

    return true;
}

