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
 * Module pulse 2.0 - Plugin information for the pulse action class.
 *
 * @package   mod_pulse
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\plugininfo;

use core\plugininfo\base, part_of_admin_tree, admin_settingpage;
use moodle_exception;

/**
 * Pulse action class extends base class providing access to the information about a pulse 2.0 plugin.
 */
class pulseaction extends base {


    /**
     * Returns the information about plugin availability
     *
     * True means that the plugin is enabled. False means that the plugin is
     * disabled. Null means that the information is not available, or the
     * plugin does not support configurable availability or the availability
     * can not be changed.
     *
     * @return null|bool
     */
    public function is_enabled() {
        return true;
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
     * Loads plugin setting into the settings tree.
     *
     * @param \part_of_admin_tree $adminroot
     * @param string $parentnodename
     * @param bool $hassiteconfig whether the current user has moodle/site:config capability
     */
    public function load_settings(part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {

        $ADMIN = $adminroot; // May be used in settings.php.
        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig || !file_exists($this->full_path('settings.php'))) {
            return;
        }

        $section = $this->get_settings_section_name();
        $page = new admin_settingpage($section, $this->displayname, 'moodle/site:config', $this->is_enabled() === false);
        include($this->full_path('settings.php')); // This may also set $settings to null.

        if ($page) {
            $ADMIN->add($parentnodename, $page);
        }
    }

    /**
     * Get a sub plugins in the ace tools plugin.
     *
     * @return array $subplugins.
     */
    public function get_plugins_list() {
        $actionplugins = \core_component::get_plugin_list('pulseaction');
        return $actionplugins;
    }

    /**
     * Get the list of action plugins woithj its base class.
     */
    public function get_plugins_base() {
        $plugins = $this->get_plugins_list();

        if (!empty($plugins)) {
            foreach ($plugins as $componentname => $pluginpath) {
                $instance = $this->get_plugin($componentname);
                $actions[$componentname] = $instance;
            }
        }

        return $actions ?? [];
    }

    /**
     * Get the action component actionform instance.
     *
     * @param string $componentname
     * @return \actionform
     */
    public function get_plugin($componentname) {

        $classname = "pulseaction_$componentname\actionform";
        if (!class_exists($classname)) {
            throw new moodle_exception('actioncomponentmissing', 'pulse');
        }
        $instance = new $classname();
        $instance->set_component($componentname);

        return $instance;
    }

    /**
     * Instance.
     *
     * @return \pulseaction
     */
    public static function instance() {
        static $instance;
        return $instance ?: new self();
    }

    /**
     * Get list of action plugins base class instance.
     *
     * @return stdclass
     */
    public static function get_list() {
        static $actionplugins = null;

        if (!$actionplugins) {
            $actionplugins = new self();
        }

        $plugins = $actionplugins->get_plugins_base();
        return $plugins;
    }

}
