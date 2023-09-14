<?php


namespace mod_pulse\automation;

use context_system;
use context_module;
use core_tag_tag;

class templates {


    const VISIBILITY_SHOW = 1;

    const VISIBILITY_HIDDEN = 0;

    const STATUS_ENABLE = 1;

    const STATUS_DISABLE = 0;

    protected $templateid; // Template id.

    protected $template;

    protected function __construct($templateid) {
        $this->templateid = $templateid;
        $this->template = $this->get_template_record();
    }

    public function get_template() {
        return $this->template;
    }

    public static function create($templateid) {
        // TODO: template exist checks goes here.

        return new self($templateid);
    }

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

    public function get_formdata() {
        global $DB;

        if ($autotemplate = $DB->get_record('pulse_autotemplates', ['id' => $this->templateid], '*', MUST_EXIST)) {
            return $autotemplate;
        }
    }

    public function get_data_forinstance(&$instance) {
        global $DB;

        $autotemplateins = $DB->get_record('pulse_autotemplates_ins', ['instanceid' => $instance->id], '*', MUST_EXIST);
        $autotemplate = $this->get_formdata();

        $overridedata = array_filter((array) $autotemplateins);
        foreach ($overridedata as $key => $value) {
            if (!in_array($key, ['id', 'timemodified', 'instanceid'])) {
                $instance->override[$key] = 1;
            }
        }

        // Merge the override data intop of template data.
        $data = (object) \mod_pulse\automation\helper::merge_instance_overrides($autotemplateins, $autotemplate);

        // Convert the json values to array.
        $data->tenants = json_decode($data->tenants);
        $data->categories = json_decode($data->categories);
        $data->triggerconditions = json_decode($data->triggerconditions);
        $data->tags = json_decode($data->tags);

        $data->templateid = $autotemplate->id;
        unset($data->id); // Remove the template id.

        $instance = (object) array_merge((array) $instance, (array) $data);

        return $data;
    }


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
     * Include actions data.
     *
     * @param [type] $data
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
     * @return bool True if the update was successful, false otherwise.
     */
    public function update_status(bool $status, bool $instance=false) {
        global $DB;

        if ($instance) {
            $DB->set_field('pulse_autoinstances', 'status', $status, ['templateid' => $this->templateid]);
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

    public static function get_tag_options() {

        return [
            'itemtype' => 'pulse_autotemplates',
            'component' => 'mod_pulse'
        ];
    }

    public static function get_tag_instance_options() {

        return [
            'itemtype' => 'pulse_autotemplates_ins',
            'component' => 'mod_pulse'
        ];
    }

    public function get_template_option_override($count=true) {

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
        if (isset($formdata->id) && $DB->record_exists('pulse_autotemplates', ['id' => $formdata->id])) {
            $templateid = $formdata->id;
            // Update the template.
            $DB->update_record('pulse_autotemplates', $record);

            // Show the edited success notification.
            \core\notification::success(get_string('templateupdatesuccess', 'pulse'));
        } else {
            // print_object($record);exit;
            $templateid = $DB->insert_record('pulse_autotemplates', $record);
            // Show the inserted success notification.
            \core\notification::success(get_string('templateinsertsuccess', 'pulse'));
        }

        // Update template tags.
        $tagoptions = self::get_tag_options();
        $context = context_system::instance();

        if (!empty($formdata->tags)) {
            \core_tag_tag::set_item_tags($tagoptions['component'], $tagoptions['itemtype'], $templateid, $context, $formdata->tags);
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
     * @param [type] $instanceid
     * @param [type] $options
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
            $removeoverrides['timemodified'] = date('Y-m-d H:i');
            $removeoverrides = array_merge($removeoverrides, $options);

            return $DB->update_record('pulse_autotemplates_ins', $removeoverrides);
        } else {
            $options['instanceid'] = $instanceid;
            return $DB->insert_record('pulse_autotemplates_ins', $options);
        }

        return false;
    }

    public static function get_templates_record($templates) {
        global $DB;

        if (empty($templates)) {
            return true;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($templates, SQL_PARAMS_NAMED, 'ins');
        $sql = "SELECT * FROM {pulse_autotemplates} te WHERE te.id $insql";

        $tempoverride = $DB->get_records_sql($sql, $inparams);

        return $tempoverride;
        /* foreach ($rawdata as $key => $data) {
            $templateid = $data->templateid;
        } */
    }

}
