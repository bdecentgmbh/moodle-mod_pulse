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

/**
 * Override user groups in modinfo. Group availablity condition doesn't check the passed user groups.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Course modinfo wrapper.
 */
class pulse_course_modinfo extends course_modinfo {

    /**
     * Userid to check the group availability.
     *
     * @var int
     */
    public $groupuserid;

    /**
     * Standard properties to fetch.
     *
     * @var array
     */
    private static $standardproperties = array(
        'courseid' => 'get_course_id',
        'userid' => 'get_user_id',
        'sections' => 'get_sections',
        'cms' => 'get_cms',
        'instances' => 'get_instances',
        'groups' => 'get_groups_all',
        'groupuserid' => 'get_group_userid', // User id to get groups.
    );

    /**
     * Get current group user id.
     *
     * @return void
     */
    public function get_group_userid() {
        return $this->groupuserid;
    }

    /**
     * Set group userid.
     *
     * @param  mixed $userid
     * @return void
     */
    public function changeuserid($userid) {
        $this->groupuserid = $userid;
    }

    /**
     * Returns groups that the updated user belongs to on the course. Note: If not already
     * available, this may make a database query.
     * @param int $groupingid Grouping ID or 0 (default) for all groups
     * @return int[] Array of int (group id) => int (same group id again); empty array if none
     */
    public function get_groups($groupingid = 0) {

        $allgroups = groups_get_user_groups($this->get_course_id(), $this->groupuserid);
        if (!isset($allgroups[$groupingid])) {
            return array();
        }
        return $allgroups[$groupingid];
    }

}
