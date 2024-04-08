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
 * This file contains the class for restore of this pulse action notification plugin
 *
 * @package   pulseaction_notification
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore pulse action subplugin class.
 *
 */
class restore_pulseaction_notification_subplugin extends restore_subplugin {

    /**
     * Returns the paths to be handled by the subplugin.
     * @return array
     */
    protected function define_pulse_autoinstances_subplugin_structure() {

        $paths = [];

        // We used get_recommended_name() so this works.
        $paths[] = new restore_path_element('pulseaction_notification',
            '/activity/pulse_autoinstances/notificationaction/pulseaction_notification'
        );

        $paths[] = new restore_path_element('pulseaction_notification_ins',
            '/activity/pulse_autoinstances/notificationactionins/pulseaction_notification_ins'
        );

        return $paths;
    }

    /**
     * Processes one pulseaction_notification element
     * @param mixed $data
     * @return void
     */
    public function process_pulseaction_notification($data) {
        global $DB;

        $data = (object) $data;
        // Get the new template id.
        $data->templateid = $this->get_mappingid('pulse_autotemplates', $data->templateid);

        // If already notification is created for template then no need to include again.
        if (!$DB->record_exists('pulseaction_notification', ['templateid' => $data->templateid])) {
            $DB->insert_record('pulseaction_notification', $data);
        }
    }

    /**
     * Processes one pulseaction_notification_ins element
     * @param mixed $data
     * @return void
     */
    public function process_pulseaction_notification_ins($data) {
        global $DB;

        $data = (object)$data;

        $data->instanceid = $this->get_new_parentid('pulse_autoinstances');

        if (!$DB->record_exists('pulseaction_notification_ins', ['instanceid' => $data->instanceid])) {
            $DB->insert_record('pulseaction_notification_ins', $data);
        }
    }

    /**
     * Update editors Methods for after the pulse automation instances restore.
     *
     * @return void
     */
    public function launch_after_restore_methods() {
        global $DB;

        $instances = $DB->get_records('backup_ids_temp', [
            'backupid' => $this->get_restoreid(), 'itemname' => 'pulse_autoinstances',
        ]);

        foreach ($instances as $instance) {
            $oldinstances = $DB->get_records('pulseaction_notification_ins', ['instanceid' => $instance->newitemid]);
            foreach ($oldinstances as $ins) {
                $record = (object) ['id' => $ins->id];
                $suppress = $ins->suppress ? json_decode($ins->suppress, true) : [];
                if ($suppress) {
                    array_walk($suppress, function(&$sup) {
                        $sup = $this->get_mappingid('course_module', $sup, $sup);
                    });
                    $record->suppress = json_encode($suppress);
                }

                if (!empty($ins->dynamiccontent)) {
                    $record->dynamiccontent = $this->get_mappingid('course_module', $ins->dynamiccontent, $ins->dynamiccontent);
                }
                $DB->update_record('pulseaction_notification_ins', $record);

                // Add pulse related files.
                $this->add_related_files('mod_pulse', 'pulsenotification_headercontent', 'pulse_autoinstances', null,
                    $instance->itemid);
                $this->add_related_files('mod_pulse', 'pulsenotification_staticcontent', 'pulse_autoinstances', null,
                    $instance->itemid);
                $this->add_related_files('mod_pulse', 'pulsenotification_footercontent', 'pulse_autoinstances', null,
                    $instance->itemid);
                // Add pulse related files.
                $this->add_related_files('mod_pulse', 'pulsenotification_headercontent_instance', 'pulse_autoinstances', null,
                    $instance->itemid);
                $this->add_related_files('mod_pulse', 'pulsenotification_staticcontent_instance', 'pulse_autoinstances', null,
                    $instance->itemid);
                $this->add_related_files('mod_pulse', 'pulsenotification_footercontent_instance', 'pulse_autoinstances', null,
                    $instance->itemid);
            }
        }
    }

    /**
     * Update the files of editors after restore execution.
     *
     * @param array $contents
     * @return void
     */
    public static function decode_contents(&$contents) {

        $contents[] = new restore_decode_content('pulseaction_notification_ins', [
            'headercontent', 'staticcontent', 'footercontent',
        ], 'pulse_autoinstances');

        $contents[] = new restore_decode_content('pulseaction_notification', [
            'headercontent', 'staticcontent', 'footercontent',
        ], 'pulse_autotemplates');
    }

}
