<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace pulseaction_notification\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
 
/**
 * Privacy provider.
 *
 * @package   pulseaction_notification
 * @author    vithushakethiri <vithushakethiri@catalyst-au.net>
 * @copyright 2024 Catalyst IT
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider, \core_privacy\local\request\data_provider {
    /**
     * Returns meta data about this system.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        // We store time spent in a course per linked to a user.
        $collection->add_database_table(
            'pulseaction_notification_sch',
            [
                'userid' => 'privacy:metadata:pulseaction_notification_sch:userid',
                'status' => 'privacy:metadata:pulseaction_notification_sch:status',
                'timecreated' => 'privacy:metadata:pulseaction_notification_sch:timecreated',
            ],
            'privacy:metadata:pulseaction_notification'
        );
        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The list of contexts used in this plugin.
     */

    public static function get_contexts_for_userid(int $userid) : \core_privacy\local\request\contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = "SELECT c.id
            FROM {context} c
            JOIN {pulseaction_notification_sch} pl ON c.instanceid = pl.userid
            JOIN {user} u ON u.id = :userid
            WHERE c.contextlevel = :contextlevel";

        $params = [
            'userid' => $userid,
            'contextlevel' => CONTEXT_USER,
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

        if (!$context instanceof \context_course) {
            return;
        }

        $sql = "SELECT ts.userid
            FROM {pulseaction_notification_sch} ts
            WHERE ts.courseid = :courseid";

        $params = [
            'courseid' => $context->instanceid,
        ];
        $userlist->add_from_sql('userid', $sql, $params);
    
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                // Only support course context.
                continue;
            }

            $data = $DB->get_record('pulseaction_notification_sch', ['userid' => $userid, 'courseid' => $context->instanceid]);
            writer::with_context($context)->export_data(
                [get_string('privacy:metadata:pulseaction_notification', 'pulseaction_notification'), 'pulseaction_notification'],
                $data
            );
        }

        return $contextlist;
    }

    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $DB->delete_records('pulseaction_notification_sch', ['courseid' => $context->instanceid]);
    }

    /**
     * Delete user within a single context.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                // Only support course context.
                continue;
            }

            $DB->delete_records('pulseaction_notification_sch', ['userid' => $userid, 'courseid' => $context->instanceid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();

        // Sanity check that context is at the course context level.
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params = array_merge(['courseid' => $context->instanceid], $userinparams);
        $sql = "courseid = :courseid AND userid {$userinsql}";

        $DB->delete_records_select('pulseaction_notification_sch', $sql, $params);
    }

}
