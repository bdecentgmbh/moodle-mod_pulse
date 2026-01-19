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

namespace pulseaddon_availability\privacy;

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\metadata\provider as core_privacy_provider;
use core_privacy\local\request\plugin\provider as core_plugin_provider;

/**
 * Privacy implementation for availability submodule.
 *
 * @package   pulseaddon_availability
 * @copyright 2026 Tomo Tsuyuki <tomotsuyuki@catalyst-au.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements core_plugin_provider, core_privacy_provider, core_userlist_provider {
    /**
     * List of used data fields summary meta key.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {

        // Availability table fields meta summary.
        $metadata = [
            'userid' => 'privacy:metadata:pulseaddon_availability:userid',
            'status' => 'privacy:metadata:pulseaddon_availability:status',
            'availabletime' => 'privacy:metadata:pulseaddon_availability:approvaltime',
        ];
        $collection->add_database_table('pulseaddon_availability', $metadata, 'privacy:metadata:pulseaddon_availability');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param  int         $userid    The user to search.
     * @return contextlist $contextlist The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = "SELECT c.id
                FROM {context} c
                INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                INNER JOIN {pulse} p ON p.id = cm.instance
                LEFT JOIN {pulseaddon_availability} pa ON pa.pulseid = p.id
                WHERE pa.userid = :userid";
        $params = [
            'modname' => 'pulse',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $params = [
            'instanceid' => $context->instanceid,
            'modulename' => 'pulse',
        ];

        $sql = "SELECT d.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {pulse} f ON f.id = cm.instance
                  JOIN {pulseaddon_availability} d ON d.pulseid = f.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
        $pulse = $DB->get_record('pulse', ['id' => $cm->instance]);

        [$userinsql, $userinparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params = array_merge(['pulseid' => $pulse->id], $userinparams);
        $sql = "pulseid = :pulseid AND userid {$userinsql}";
        $DB->delete_records_select('pulseaddon_availability', $sql, $params);
    }

    /**
     * Delete user completion data for multiple context.
     *
     * @param approved_contextlist $contextlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
            $DB->delete_records('pulseaddon_availability', ['pulseid' => $instanceid, 'userid' => $userid]);
        }
    }

    /**
     * Delete all completion data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('pulse', $context->instanceid);
        if (!$cm) {
            return;
        }
        $DB->delete_records('pulseaddon_availability', ['pulseid' => $cm->instance]);
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        // Context user.
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist as $context) {
            $sql = "SELECT pa.id  availabilityid, cm.id cmid, c.id contextid,
                           p.id AS pid, p.course AS pcourse,
                           pa.userid AS userid, pa.status AS pastatus, pa.availabletime AS paavailabletime
                      FROM {context} c
                      JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                      JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                      JOIN {pulse} p ON p.id = cm.instance
                      JOIN {pulseaddon_availability} pa ON pa.pulseid = p.id AND pa.userid = :userid
                     WHERE c.id = :contextid
                  ORDER BY cm.id, pa.id ASC";

            $params = [
                'modname' => 'pulse',
                'contextlevel' => CONTEXT_MODULE,
                'userid' => $userid,
                'contextid' => $context->id,
            ];
            $rows = $DB->get_records_sql($sql, $params);
            $list = [];
            foreach ($rows as $row) {
                $list[] = [
                    'userid' => $row->userid,
                    'status' => $row->pastatus,
                    'availabletime' => $row->paavailabletime,
                ];
            }
            writer::with_context($context)->export_data(
                [get_string('privacy:metadata:pulseaddon_availability', 'pulseaddon_availability')],
                (object) $list
            );
        }
    }
}
