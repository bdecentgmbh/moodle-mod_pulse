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
 * This file contains the class for backup of this pulse action notification plugin.
 *
 * @package   pulseaction_notification
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Provides the information to backup of the pulse action notification.
 */
class backup_pulseaction_notification_subplugin extends backup_subplugin {

    /**
     * Returns the subplugin information to attach to submission element
     * @return backup_subplugin_element
     */
    protected function define_pulse_autoinstances_subplugin_structure() {

        // Create XML elements.
        $subplugin = $this->get_subplugin_element();

        // NOtification action template.
        $action = new \backup_nested_element('notificationaction');
        $actionfields = new \backup_nested_element('pulseaction_notification', ['id'], [
            "templateid", "sender", "senderemail", "notifyinterval", "week", "month", "time", "notifydelay", "delayduration",
            "notifylimit", "recipients", "cc", "bcc", "subject", "headercontent", "headercontentformat", "staticcontent",
            "staticcontentformat", "dynamiccontent", "contentlength", "contenttype", "footercontent", "footercontentformat",
            "timemodified",
        ]);

        $actionins = new \backup_nested_element('notificationactionins');
        $actioninsfields = new \backup_nested_element('pulseaction_notification_ins', ['id'], [
            "instanceid", "sender", "senderemail", "notifyinterval", "week", "month", "time", "notifydelay", "delayduration",
            "suppress", "suppressoperator", "notifylimit", "recipients", "cc", "bcc", "subject", "headercontent", "staticcontent",
            "dynamiccontent", "contentlength", "contenttype", "chapterid", "footercontent", "timemodified",
        ]);

        $subplugin->add_child($action);
        $action->add_child($actionfields);

        $subplugin->add_child($actionins);
        $actionins->add_child($actioninsfields);

        // Notification template data source query.
        $actionfields->set_source_sql('
            SELECT pn.*
            FROM {pulseaction_notification} pn
            JOIN {pulse_autotemplates} at ON at.id = pn.templateid
            WHERE at.id IN (
                SELECT templateid
                FROM {pulse_autoinstances}
                WHERE courseid = :courseid
            )
        ', ['courseid' => backup::VAR_COURSEID]);

        $actioninsfields->set_source_table('pulseaction_notification_ins', ['instanceid' => \backup::VAR_PARENTID]);

        // Define file annotations.
        $subplugin->annotate_files('mod_pulse', 'pulsenotification_headercontent', null);
        $subplugin->annotate_files('mod_pulse', 'pulsenotification_staticcontent', null);
        $subplugin->annotate_files('mod_pulse', 'pulsenotification_footercontent', null);
        $subplugin->annotate_files('mod_pulse', 'pulsenotification_headercontent_instance', null);
        $subplugin->annotate_files('mod_pulse', 'pulsenotification_staticcontent_instance', null);
        $subplugin->annotate_files('mod_pulse', 'pulsenotification_footercontent_instance', null);

        return $subplugin;
    }

}
