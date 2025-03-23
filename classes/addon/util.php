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

namespace mod_pulse\addon;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/enrollib.php');

use context;

/**
 * Class util
 *
 * @package    mod_pulse
 * @copyright  2024 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class util {

    /**
     * Counts list of users enrolled into course (as per above function)
     *
     * @param context $context
     * @param string $withcapability
     * @param int|array $groupids The groupids, 0 or [] means all groups and USERSWITHOUTGROUP no group
     * @param bool $onlyactive consider only active enrolments in enabled plugins and time restrictions
     * @param array $additionalconditions
     * @param array $additionalparams
     * @return int number of users enrolled into course
     */
    public static function count_enrolled_users_sql(context $context, $withcapability = '', $groupids = 0,
        $onlyactive = false, $additionalconditions = [], $additionalparams = []) {

        $capjoin = get_enrolled_with_capabilities_join(
                $context, '', $withcapability, $groupids, $onlyactive);

        $sql = "SELECT COUNT(DISTINCT u.id)
                FROM {user} u
                $capjoin->joins
                WHERE $capjoin->wheres AND u.deleted = 0 AND u.suspended = 0";

        if (!empty($additionalconditions)) {
            $sql .= ' AND ' . implode(' AND ', $additionalconditions);
        }

        return [$sql, $capjoin->params + $additionalparams];
    }

    /**
     * Returns list of users enrolled into course.
     *
     * @param context $context
     * @param string $withcapability
     * @param int|array $groupids The groupids, 0 or [] means all groups and USERSWITHOUTGROUP no group
     * @param string $userfields requested user record fields
     * @param string $orderby
     * @param int $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     * @param bool $onlyactive consider only active enrolments in enabled plugins and time restrictions
     * @param array $additionalconditions additional conditions to be added to the SQL query
     * @param array $additionalparams additional parameters to be added to the SQL query
     * @param array $joins additional joins to be added to the SQL query
     * @param array $joinparams additional parameters to be added to the SQL query for the joins
     *
     * @return array of user records
     */
    public static function get_enrolled_users_sql(context $context, $withcapability = '', $groupids = 0, $userfields = 'u.*',
        $orderby = null, $limitfrom = 0, $limitnum = 0, $onlyactive = false, $additionalconditions = [],
        $additionalparams = [], $joins = [], $joinparams = []) {

        global $DB;

        $additionaljoins = implode(' ', $joins);

        list($esql, $params) = get_enrolled_sql($context, $withcapability, $groupids, $onlyactive);
        $sql = "SELECT $userfields
                FROM {user} u
                JOIN ($esql) je ON je.id = u.id
                $additionaljoins
                WHERE u.deleted = 0 AND u.suspended = 0";

        if (!empty($additionalconditions)) {
            $sql .= " AND " . implode(" AND ", $additionalconditions);
        }

        if ($orderby) {
            $sql = "$sql ORDER BY $orderby";
        } else {
            list($sort, $sortparams) = users_order_by_sql('u');
            $sql = "$sql ORDER BY $sort";
            $params = array_merge($params, $sortparams);
        }

        $params = array_merge($params, $additionalparams, $joinparams);

        return  $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    }
}
