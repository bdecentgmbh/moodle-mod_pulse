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
 * Pulse instance extend features file. contains pro feature extended methods
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse;

use mod_pulse\plugininfo\pulseaddon;

/**
 * Extend the pro feature to pulse instances.
 */
class extendpro {
    /**
     * Get addon instances.
     *
     * @return array List of addon instances.
     */
    protected static function get_addon_instances(): array {
        $subplugins = \core_component::get_plugin_list_with_class('pulseaddon', 'instance');
        $enabledaddons = \mod_pulse\plugininfo\pulseaddon::get_enabled_addons();
        $subplugins = array_filter($subplugins, function ($fullclassname) use ($enabledaddons) {
            return in_array($fullclassname, array_values($enabledaddons));
        }, ARRAY_FILTER_USE_KEY);

        return $subplugins;
    }

    /**
     * Trigger the add pulse instance.
     *
     * @param int $pulseid Pulse instance id.
     * @param mixed $pulse Pulse instance data.
     * @param mixed $method Method name to trigger.
     * @return void
     */
    public static function pulse_extend_instance($pulseid, $pulse, $method) {
        // Inlcude component callback implementation.
        $subplugins = self::get_addon_instances();
        $method = 'instance_' . $method;
        foreach ($subplugins as $fullclassname => $classpath) {
            if (is_subclass_of($classpath, \mod_pulse\addon\base::class) && method_exists($classpath, $method)) {
                $classpath::init($pulseid)->$method($pulse);
            }
        }
    }

    /**
     * Call the extended email placeholder filters to replace the content.
     *
     * @param int $pulseid Pulse id.
     * @param mixed $instance Pulse instance.
     * @return string $html
     */
    public static function pulse_extend_cm_infocontent($pulseid, $instance) {
        $html = '';

        $subplugins = self::get_addon_instances();
        $method = 'get_cm_infocontent';
        foreach ($subplugins as $fullclassname => $classpath) {
            if (is_subclass_of($classpath, \mod_pulse\addon\base::class) && method_exists($classpath, $method)) {
                $html .= $classpath::init($pulseid)->$method($instance);
            }
        }
        return $html;
    }

    /**
     * Inject form elements into mod instance form.
     *
     * @param MoodleQuickForm $mform the form to inject elements into.
     * @param mod_pulse_mod_form $instance Pulse instance.
     * @param string $method Method of form fields (=reaction only returns the reaction form fields)
     * @param array $args additional arguments to pass to the method
     * @return void
     */
    public static function pulse_extend_form(\MoodleQuickForm $mform, \mod_pulse_mod_form $instance, string $method, $args = []) {

        // Inlcude component callback implementation.
        $subplugins = self::get_addon_instances();
        $method = 'form_' . $method;
        foreach ($subplugins as $fullclassname => $classpath) {
            if (is_subclass_of($classpath, \mod_pulse\addon\base::class) && method_exists($classpath, $method)) {
                $classpath::$method($mform, $instance, ...$args);
            }
        }
    }

    /**
     * Extend form post process method from pro plugin.
     *
     * @param object $data module form submitted data object.
     */
    public static function pulse_extend_postprocessing(&$data) {
        // Inlcude component callback implementation.
        $subplugins = self::get_addon_instances();
        $method = 'data_postprocessing';
        foreach ($subplugins as $fullclassname => $classpath) {
            if (is_subclass_of($classpath, \mod_pulse\addon\base::class) && method_exists($classpath, $method)) {
                $classpath::$method($data);
            }
        }
    }

    /**
     * Extend form post process method from pro plugin.
     *
     * @param object $defaultvalues module form submitted data object.
     * @param object $currentinstance module instance object.
     * @param object $context context object.
     *
     * @return void
     */
    public static function pulse_extend_preprocessing(&$defaultvalues, $currentinstance, $context) {
        // Inlcude component callback implementation.
        $subplugins = self::get_addon_instances();
        foreach ($subplugins as $fullclassname => $classpath) {
            $method = 'data_preprocessing';
            if (is_subclass_of($classpath, \mod_pulse\addon\base::class) && method_exists($classpath, $method)) {
                $classpath::$method($defaultvalues, $currentinstance, $context);
            }
        }
    }

    /**
     * Extend the pulse instance with the addon data.
     *
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    public static function pulse_extend_general(string $method, $args = []) {

        // Inlcude component callback implementation.
        $subplugins = self::get_addon_instances();
        foreach ($subplugins as $fullclassname => $classpath) {
            if (is_subclass_of($classpath, \mod_pulse\addon\base::class) && method_exists($classpath, $method)) {
                $results[$fullclassname] = $classpath::$method(...$args);
            }
        }

        return $results ?? [];
    }

    /**
     * Check the pulse addon extended the invitation method.
     * if extended the invitation then the invitations are send using pulse pro plugin.
     *
     * @return bool|mixed
     */
    public static function pulse_extend_invitation() {

        // Inlcude component callback implementation.
        $method = 'invitation_cron_task';
        $subplugins = self::get_addon_instances();
        foreach ($subplugins as $fullclassname => $classpath) {
            if (is_subclass_of($classpath, \mod_pulse\addon\base::class) && method_exists($classpath, $method)) {
                $result = $classpath::$method();
                // Once any plugin return true then confirm the invitation is extended. no need to send invitation from mod pulse.
                if ($result) {
                    $returnresult = true;
                }
            }
        }

        return $returnresult ?? false;
    }

    /**
     * Add columns to the report.
     *
     * @param array $headers
     * @param array $columns
     * @param array $callbacks
     * @return void
     */
    public static function report_add_columns(&$headers, &$columns, &$callbacks) {
        // Include component callback implementation.
        $method = 'report_add_columns';
        $subplugins = self::get_addon_instances();
        foreach ($subplugins as $fullclassname => $classpath) {
            if (is_subclass_of($classpath, \mod_pulse\addon\base::class) && method_exists($classpath, $method)) {
                $classpath::$method($headers, $columns, $callbacks);
            }
        }
    }

    /**
     * List of extended plugins fileareas list to add into pluginfile function.
     *
     * @return array
     */
    public static function pulse_extend_filearea(): array {
        $callbacks = get_plugins_with_function('extend_pulse_filearea');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                $fileareas = $pluginfunction();
                return $fileareas;
            }
        }
        return [];
    }
}
