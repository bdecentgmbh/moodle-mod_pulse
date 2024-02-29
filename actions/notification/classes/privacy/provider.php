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
use core_privacy\local\request\contextlist;
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
            JOIN {course} co ON c.instanceid = co.id
            JOIN {user} u ON u.id = :userid
            WHERE c.contextlevel = :contextlevel";

        $params = [
        'userid' => $userid,
        'contextlevel' => CONTEXT_USER,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }
}
