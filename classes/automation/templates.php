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
 * Notification pulse action - Manage automation templates.
 *
 * @package   mod_pulse
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_pulse\automation;

use context_system;
use context_module;
use core_tag_tag;

/**
 * Manage automation templates.
 */
class templates {

    /**
     * Repersents the template visibility is shown.
     * @var int
     */
    const VISIBILITY_SHOW = 1;

    /**
     * Repersents the template visibility is hidden.
     * @var int
     */
    const VISIBILITY_HIDDEN = 0;

    /**
     * Repersents the template status is enabled.
     * @var int
     */
    const STATUS_ENABLE = 1;

    /**
     * Repersents the template status is disabled.
     * @var int
     */
    const STATUS_DISABLE = 0;

    /**
     * ID of the automation template.
     *
     * @var int
     */
    protected $templateid; // Template id.

    /**
     * The record of the templates
     *
     * @var stdclass
     */
    protected $template;

    /**
     * Constructor for initializing a template.
     *
     * @param int $templateid The ID of the template.
     */
    protected function __construct($templateid) {
        $this->templateid = $templateid;
        $this->template = $this->get_template_record();
    }

    /**
     * Get the template associated with this instance.
     *
     * @return stdClass The template object.
     */
    public function get_template() {
        return $this->template;
    }

    /**
     * Create an instance of the template.
     *
     * @param int $templateid The ID of the template.
     * @return self The instance of the template.
     */
    public static function create($templateid) {
        // TODO: template exist checks goes here.
        return new self($templateid);
    }

    /**
     * Find the template is available to add in the course.
     *
     * @param int $categoryid
     * @return bool
     */
    public function is_available_forcourse(int $categoryid): bool {
        // Fetch template record.
        $formdata = $this->template;
        // Check the template is visible.
        if (!$formdata->visible) {
            return false;
        }
        // Verify template has category restriction, if contains check the course category is included in the categories list.
        $categories = (array) $formdata->categories;
        if ($categories && !in_array($categoryid, $categories)) {
            return false;
        }
        // Template is visible.
        return true;
    }

    /**
     * Retrieve the template record from the database.
     *
     * @return stdClass The template record.
     */
    protected function get_template_record() {

        $data = $this->get_formdata(); // Fetch the template data from DB.

        // Convert the json values to array.
        $data->tenants = json_decode($data->tenants);
        $data->categories = json_decode($data->categories);
        $data->triggerconditions = json_decode($data->triggerconditions);

        // Get the tags for the template.
        $tagoptions = self::get_tag_options();
        $data->tags = core_tag_tag::get_item_tags_array($tagoptions['component'], $tagoptions['itemtype'], $data->id);

        $data->templateid = $data->id;
        // Include all available actions data for this current template.
        $this->include_actions_data($data);

        return $data;
    }

    /**
     * Get form data for a template.
     *
     * @return stdClass The template data.
     */
    public function get_formdata() {
        global $DB;

        if ($autotemplate = $DB->get_record('pulse_autotemplates', ['id' => $this->templateid], '*', MUST_EXIST)) {
            return $autotemplate;
        }
    }

    /**
     * Get data for a specific instance.
     *
     * @param stdClass $instance The instance object.
     * @return stdClass The instance data.
     */
    public function get_data_forinstance(&$instance) {
        global $DB;

        $autotemplateins = $DB->get_record('pulse_autotemplates_ins', ['instanceid' => $instance->id], '*', MUST_EXIST);
        $autotemplate = $this->get_formdata();

        $instance->overridecount  = 0; // Count the overrides for this instance.
        $overridedata = array_filter((array) $autotemplateins);
        foreach ($overridedata as $key => $value) {
            if (!in_array($key, ['id', 'timemodified', 'instanceid', 'insreference'])) {
                $instance->override[$key] = 1;
                $instance->overridecount += 1;
            }
        }

        // Merge the override data intop of template data.
        $data = (object) \mod_pulse\automation\helper::merge_instance_overrides($autotemplateins, $autotemplate);

        // Convert the json values to array.
        $data->status = $instance->status; // Use the instance status as final instance status.
        $data->tenants = json_decode($data->tenants);
        $data->categories = json_decode($data->categories);
        $data->triggerconditions = json_decode($data->triggerconditions);
        $data->tags = json_decode($data->tags);

        $data->templateid = $autotemplate->id;
        unset($data->id); // Remove the template id.

        $instance = (object) array_merge((array) $instance, (array) $data);

        return $data;
    }

    /**
     * Get raw data for templates. NOT RECOMMENDED
     *
     * @return stdClass The raw template data.
     */
    public function get_templates_rawdata() {
        global $DB;

        $actions = \mod_pulse\plugininfo\pulseaction::get_list();

        $select = ['te.id as templateid'];
        $join = [];
        $i = 0;
        foreach ($actions as $action) {
            $i++;
            $tablename = $action->get_tablename();
            if (!$tablename) {
                continue;
            }
            $sht = 'ac'.$i;
            $join[] = " JOIN $tablename AS $sht ON $sht.templateid=te.id ";
            $select[] = "$sht.id as $sht, $sht.*";
        }
        $select[] = 'te.*';
        $select = implode(', ', $select);
        $join = implode(' ', $join);

        $sql = "SELECT $select FROM {pulse_autotemplates} te";
        $sql .= $join;
        $sql .= " WHERE te.id=:templateid";

        return $DB->get_record_sql($sql, ['templateid' => $this->templateid]);
    }

    /**
     * List of instanced created using this template
     *
     * @return void
     */
    public function get_instances() {
        global $DB;

        $instances = $DB->get_records('pulse_autoinstances', ['templateid' => $this->templateid]);
        $overrides = [];
        $overinstances = [];
        if (!empty($instances)) {
            foreach ($instances as $instanceid => $instance) {
                $insobj = new \mod_pulse\automation\instances($instance->id);
                $formdata = (object) $insobj->get_instance_formdata();
                foreach ($formdata->override as $key => $value) {
                    if (isset($overrides[$key])) {
                        $overrides[$key] += 1;
                        $overinstances[$key][] = ['id' => $instance->id, 'name' => format_string($formdata->title)];
                    } else {
                        $overrides[$key] = 1;
                        $overinstances[$key] = [['id' => $instance->id, 'name' => format_string($formdata->title)]];
                    }
                }
            }
        }

        return [$overrides, $overinstances];
    }

    /**
     * Retrieves instances associated with this template.
     *
     * @return array An array of template instances or an empty array if none are found.
     */
    public function get_instances_record() {
        global $DB;

        if ($instances = $DB->get_records('pulse_autoinstances', ['templateid' => $this->templateid]) ) {
            return $instances;
        }

        return [];
    }

    /**
     * Include actions data.
     *
     * @param stdclass $data
     * @return void
     */
    public function include_actions_data(&$data) {

        // Fetch the list of enabled action plugins.
        $actionplugins = \mod_pulse\plugininfo\pulseaction::get_list();
        foreach ($actionplugins as $name => $plugin) {
            // Include all the actions data for this template.
            $plugin->include_data_fortemplate($data);
        }
    }

    /**
     * Delete all the available actions linked with this template.
     *
     * Find the lis of actions and get linked template instance based template id and delete those actions.
     *
     * @param int $templateid
     * @return void
     */
    public function delete_template_actions($templateid) {
        global $DB;
        // Fetch the list of enabled action plugins.
        $actionplugins = \mod_pulse\plugininfo\pulseaction::get_list();
        foreach ($actionplugins as $name => $plugin) {
            $plugin->delete_template_action($templateid);
        }
    }

    /**
     * Updates the "visible" field of the current menu and deletes it from the cache.
     *
     * @param bool $visible The new value for the "visible" field.
     * @return bool True if the update was successful, false otherwise.
     */
    public function update_visible(bool $visible) {

        return $this->update_field('visible', $visible, ['id' => $this->templateid]);
    }

    /**
     * Updates the "visible" field of the current menu and deletes it from the cache.
     *
     * @param bool $status The new value for the "status" field.
     * @param bool $instance
     * @return bool True if the update was successful, false otherwise.
     */
    public function update_status(bool $status, bool $instance=false) {
        global $DB;

        if ($instance) {
            foreach ($this->get_instances_record() as $instanceid => $instance) {
                instances::create($instanceid)->update_status($status);
            }
        }

        return $this->update_field('status', $status, ['id' => $this->templateid]);
    }

    /**
     * Updates a field of the current menu with the given key and value.
     *
     * @param string $key The key of the field to update.
     * @param mixed $value The new value of the field.
     * @return bool|int Returns true on success, or false on failure. it also deletes the current menu from cache.
     */
    public function update_field($key, $value) {
        global $DB;

        $result = $DB->set_field('pulse_autotemplates', $key, $value, ['id' => $this->templateid]);

        return $result;
    }

    /**
     * Delete the current menu and all its associated items from the database.
     *
     * @return bool True if the deletion is successful, false otherwise.
     */
    public function delete_template() {
        global $DB;
        if ($DB->delete_records('pulse_autotemplates', ['id' => $this->templateid])) {
            // Delete all its actions.
            $this->delete_template_actions($this->templateid);

            return true;
        }
        return false;
    }

    /**
     * Get options for tagging templates.
     *
     * @return array An associative array with 'itemtype' and 'component'.
     */
    public static function get_tag_options() {
        return [
            'itemtype' => 'pulse_autotemplates',
            'component' => 'mod_pulse'
        ];
    }

    /**
     * Get options for tagging template instances.
     *
     * @return array An associative array with 'itemtype' and 'component'.
     */
    public static function get_tag_instance_options() {
        return [
            'itemtype' => 'pulse_autotemplates_ins',
            'component' => 'mod_pulse'
        ];
    }


    /**
     * Insert or update the menu instance to DB. Convert the multiple options select elements to json.
     * setup menu order after insert.
     *
     * Delete the current menu cache after updated the menu.
     *
     * @param stdclass $formdata
     * @return bool
     */
    public static function manage_instance($formdata) {
        global $DB;

        $record = clone $formdata;

        // Encode the multiple value elements into json to store.
        foreach ($record as $key => $value) {
            if (is_array($value)) {
                $record->$key = json_encode($value);
            }
        }

        $transaction = $DB->start_delegated_transaction();

        // Create template record.
        $record->reference = shorten_text(strip_tags($record->reference), 30);
        $record->timemodified = time();
        if (isset($formdata->id) && $DB->record_exists('pulse_autotemplates', ['id' => $formdata->id])) {
            $templateid = $formdata->id;
            // Update the template.
            $DB->update_record('pulse_autotemplates', $record);

            // Show the edited success notification.
            \core\notification::success(get_string('templateupdatesuccess', 'pulse'));
        } else {
            $templateid = $DB->insert_record('pulse_autotemplates', $record);
            // Show the inserted success notification.
            \core\notification::success(get_string('templateinsertsuccess', 'pulse'));
        }

        // Update template tags.
        $tagoptions = self::get_tag_options();
        $context = context_system::instance();

        if (!empty($formdata->tags)) {
            \core_tag_tag::set_item_tags(
                $tagoptions['component'], $tagoptions['itemtype'], $templateid, $context, $formdata->tags);
        }

        // Store actions data.
        // Send the data to action plugins for perform the data store.
        // Find list of actions.
        $actionplugins = new \mod_pulse\plugininfo\pulseaction();
        $plugins = $actionplugins->get_plugins_base();

        foreach ($plugins as $component => $pluginbase) {
            $formdata->templateid = $templateid;
            $pluginbase->process_save($formdata, $component);
        }
        // Allow to update the DB changes to Database.
        $transaction->allow_commit();

        return $templateid;
    }

    /**
     * Update instance data.
     *
     * @param int $instanceid
     * @param array $options
     * @return void
     */
    public static function update_instance_data($instanceid, $options) {
        global $DB;

        if (isset($options['reference'])) {
            $options['reference'] = shorten_text(strip_tags($options['reference']), 30);
        }

        if ($record = $DB->get_record('pulse_autotemplates_ins', ['instanceid' => $instanceid])) {
            $diff = array_diff_key((array) $record, $options);
            $removeoverrides = array_combine(array_keys($diff), array_fill(0, count($diff), null));
            $removeoverrides['id'] = $record->id;
            $removeoverrides['instanceid'] = $record->instanceid;
            $removeoverrides = array_merge($removeoverrides, $options);

            return $DB->update_record('pulse_autotemplates_ins', $removeoverrides);
        } else {
            $options['instanceid'] = $instanceid;
            return $DB->insert_record('pulse_autotemplates_ins', $options);
        }

        return false;
    }

    /**
     * Get records for a list of templates.
     *
     * @param array $templates An array of template IDs.
     * @return array An associative array of template records.
     */
    public static function get_templates_record($templates) {
        global $DB;

        if (empty($templates)) {
            return true;
        }
        // Generate SQL for IN clause and prepare parameters.
        list($insql, $inparams) = $DB->get_in_or_equal($templates, SQL_PARAMS_NAMED, 'ins');
        $sql = "SELECT * FROM {pulse_autotemplates} te WHERE te.id $insql";
        // Fetch template records.
        $tempoverride = $DB->get_records_sql($sql, $inparams);

        return $tempoverride;
    }

}
