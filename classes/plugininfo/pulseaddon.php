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
 * Pulse addon plugin info class.
 * @package   mod_pulse
 * @copyright 2023 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\plugininfo;

use core\plugininfo\base;
use part_of_admin_tree;
use admin_settingpage;

/**
 * Class for pulse addon plugins.
 */
class pulseaddon extends base {

    /**
     * List of enabled addons.
     *
     * @var array
     */
    public static $enabledaddons = [];

    /**
     * Returns the information about plugin availability
     *
     * @return bool
     */
    public function is_enabled() {
        return get_config('pulseaddon_' . $this->name, 'enabled');
    }

    /**
     * Should there be a way to uninstall the plugin via the administration UI.
     *
     * By default uninstallation is not allowed, plugin developers must enable it explicitly!
     *
     * @return bool
     */
    public function is_uninstall_allowed() {
        return true;
    }

    /**
     * Returns the node name used in admin settings menu
     *
     * @return string node name
     */
    public function get_settings_section_name() {
        return 'pulseaddon_' . $this->name;
    }

    /**
     * Loads plugin settings to the settings tree
     *
     * @param \part_of_admin_tree $adminroot
     * @param string $parentnodename
     * @param bool $hassiteconfig
     */
    public function load_settings(part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE;

        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig) {
            return;
        }

        $section = $this->get_settings_section_name();

        $settings = null;
        if (file_exists($this->full_path('settings.php'))) {
            $settings = new admin_settingpage($section, $this->displayname, 'moodle/site:config', $this->is_enabled() === false);
            include($this->full_path('settings.php'));
        }

        if ($settings) {
            $adminroot->add($parentnodename, $settings);
        }
    }

    /**
     * Get the list of available pulse addons.
     *
     * @return array Array of addon plugins
     */
    public static function get_enabled_addons() {

        if (self::$enabledaddons) {
            return self::$enabledaddons;
        }

        $plugins = \core_plugin_manager::instance()->get_plugins_of_type('pulseaddon');

        $enabled = [];
        foreach ($plugins as $plugin) {
            if ($plugin->is_enabled()) {
                $enabled[$plugin->name] = $plugin->component;
            }
        }

        self::$enabledaddons = $enabled;

        return $enabled;
    }
}
