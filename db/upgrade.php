<?php

function xmldb_pulse_upgrade($oldversion) {
    global $DB, $CFG;

    // if ($oldversion <= 2021052201) {
        $dbman = $DB->get_manager();
        // Qtype essay options.
        $table = new xmldb_table('pulse');
        $field = new xmldb_field('pulse_subject', XMLDB_TYPE_TEXT, 'medium', null,
            null, null, null, 'introformat');
        // Conditionally launch add field maxbytes.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

    // }
    return true;
}