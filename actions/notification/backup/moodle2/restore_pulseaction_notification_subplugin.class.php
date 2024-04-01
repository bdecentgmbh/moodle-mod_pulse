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
 * @package assignsubmission_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
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

        $paths = array();


        $path = $this->get_pathfor('/pulseaction_notification'); // Subplugin root path.
        $inspath = $this->get_pathfor('/pulseaction_notification_ins');
        // We used get_recommended_name() so this works.
        $paths[] = new restore_path_element('pulseaction_notification',
            '/activity/pulse_autoinstances/notificationaction/pulseaction_notification'
        );

        $paths[] = new restore_path_element('pulseaction_notification_ins',
            '/activity/pulse_autoinstances/notificationactionins/pulseaction_notification_ins'
        );

        // $paths[] = new restore_path_element($elename, $elepath);

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

       /* $this->add_related_files('assignsubmission_file',
                                 'submission_files',
                                 'submission',
                                 null,
                                 $oldsubmissionid); */
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

        /* $this->add_related_files('assignsubmission_file',
                                 'submission_files',
                                 'submission',
                                 null,
                                 $oldsubmissionid); */
    }
}
