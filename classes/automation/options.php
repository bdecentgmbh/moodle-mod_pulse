<?php

namespace mod_pulse;

use context_course;
use moodle_url;

class options {

    protected $tablename;

    public function __construct($tablename) {
        // $this->format = course_get_format($course);
        // $this->course = $this->format->get_course();
        $this->tablename = $tablename;
    }

    /**
     * Find the given string is JSON format or not.
     *
     * @param string $string
     * @return bool
     */
    public static function is_json($string) {
        return (is_null(json_decode($string))) ? false : true;
    }

    /**
     * Get custom additional field value for the module.
     *
     * @param int $cmid Course module id.
     * @param string $name Module additional field name.
     * @return null|string Returns value of given module field.
     */
    public static function get_option(int $cmid, $name) {
        global $DB;
        if ($data = $DB->get_field('format_levels_options', 'value',
            ['cmid' => $cmid, 'name' => $name])) {
            return $data;
        }
        return null;
    }

    /**
     * Get designer additional fields values for the given module.
     *
     * @param int $cmid course module id.
     * @return stdclass $options List of additional field values
     */
    public static function get_options($cmid) {
        global $DB;
        $options = new \stdclass;
        if ($records = $DB->get_records('format_levels_options', ['cmid' => $cmid])) {
            foreach ($records as $key => $field) {
                $options->{$field->name} = self::is_json($field->value)
                    ? json_decode($field->value, true) : $field->value;
            }
        }
        return $options;
    }

    /**
     * Insert the additional module fields data to the table.
     *
     * @param int $cmid Course module id.
     * @param int $courseid Course id.
     * @param string $name Field name.
     * @param mixed $value value of the field.
     * @return void
     */
    public static function insert_option(int $cmid, int $courseid, $name, $value) {
        global $DB;

        $record = new \stdClass;
        $record->cmid = $cmid;
        $record->courseid = $courseid;
        $record->name = $name;
        $record->value = $value ?: '';
        $record->timemodified = time();
        if ($exitrecord = $DB->get_record('format_levels_options', [
            'cmid' => $cmid, 'courseid' => $courseid, 'name' => $name])) {
            $record->id = $exitrecord->id;
            $record->timecreated = $exitrecord->timecreated;
            $DB->update_record('format_levels_options', $record);
        } else {
            $record->timecreated = time();
            $DB->insert_record('format_levels_options', $record);
        }
    }

}
