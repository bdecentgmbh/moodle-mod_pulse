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
 * Class that handles the display and configuration of the list of tab plugins.
 *
 * @package   mod_pulse
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\addon;

defined('MOODLE_INTERNAL') || die();

use context_system;
use core_component;
use core_plugin_manager;
use flexible_table;
use html_writer;
use moodle_url;
use pix_icon;

require_once($CFG->libdir . '/adminlib.php');

/**
 * Class that handles the display and configuration of the list of tab plugins.
 *
 * @package   mod_pulse
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugin_manager {

    /** @var object the url of the manage plugin page */
    private $pageurl;
    /** @var string any error from the current action */
    private $error = '';
    /** @var string either submission or feedback */
    private $subtype = 'pulseaddon';

    /**
     * Constructor for this dashaddon plugin manager
     * @param string $subtype - only dashaddon implemented
     */
    public function __construct($subtype) {
        $this->subtype = $subtype ?: $this->subtype;
        $this->pageurl = new moodle_url('/mod/pulse/manageaddon.php', ['subtype' => $this->subtype]);
    }

    /**
     * Return a list of plugins sorted by the order defined in the admin interface
     *
     * @return array The list of plugins
     */
    public function get_sorted_plugins_list() {
        $names = core_component::get_plugin_list($this->subtype);

        $result = [];
        $disabled = [];

        foreach ($names as $name => $path) {
            $classname = '\\' . $this->subtype . '_' . $name . '\\instance';
            if (
                !empty(get_config($this->subtype . '_' . $name, 'enabled'))
                && (($this->subtype == 'pulseaddon'))
            ) {
                $result[] = $name;
            } else {
                $disabled[] = $name;
            }
        }
        return array_merge($result, $disabled);
    }

    /**
     * Util function for writing an action icon link
     *
     * @param string $action URL parameter to include in the link
     * @param string $plugin URL parameter to include in the link
     * @param string $icon The key to the icon to use (e.g. 't/up')
     * @param string $alt The string description of the link used as the title and alt text
     * @return string The icon/link
     */
    private function format_icon_link($action, $plugin, $icon, $alt) {
        global $OUTPUT;

        $url = $this->pageurl;

        if ($action === 'delete') {
            $url = core_plugin_manager::instance()->get_uninstall_url($this->subtype.'_'.$plugin, 'manage');
            if (!$url) {
                return '&nbsp;';
            }
            return html_writer::link($url, get_string('uninstallplugin', 'core_admin'));
        }

        return $OUTPUT->action_icon(new moodle_url($url,
                ['action' => $action, 'plugin' => $plugin, 'sesskey' => sesskey()]),
                new pix_icon($icon, $alt, 'moodle', ['title' => $alt]),
                null, ['title' => $alt]) . ' ';
    }

    /**
     * Write the HTML for the submission plugins table.
     *
     * @return None
     */
    private function view_plugins_table() {
        global $OUTPUT, $CFG;
        require_once($CFG->libdir . '/tablelib.php');

        // Set up the table.
        $this->view_header();
        $table = new flexible_table($this->subtype . 'pluginsadminttable');
        $table->define_baseurl($this->pageurl);
        $table->define_columns([
            'pluginname', 'version', 'hideshow', 'status',
        ]);
        $table->define_headers([
            get_string($this->subtype . 'pluginname', 'mod_pulse'),
            get_string('version'), get_string('hideshow', 'mod_pulse'),
            get_string('status'),
        ]);
        $table->set_attribute('id', $this->subtype . 'plugins');
        $table->set_attribute('class', 'admintable generaltable');
        $table->setup();

        $plugins = $this->get_sorted_plugins_list();
        $shortsubtype = $this->subtype;

        $addondependencies = get_plugin_list_with_function('pulseaddon', 'extend_added_dependencies', 'lib.php');
        foreach ($plugins as $idx => $plugin) {
            $row = [];
            $class = '';

            $row[] = get_string('pluginname', $this->subtype . '_' . $plugin);
            $row[] = get_config($this->subtype . '_' . $plugin, 'version');

            $dependenciesfunction = isset($addondependencies[$this->subtype . '_' . $plugin]) ?
                $addondependencies[$this->subtype . '_' . $plugin] : '';

            $visible = !empty(get_config($this->subtype . '_' .$plugin, 'enabled')) &&
                (!function_exists($dependenciesfunction) || empty($dependenciesfunction()));

            if ($visible) {
                $row[] = $this->format_icon_link('hide', $plugin, 't/hide', get_string('disable'));
            } else if (function_exists($dependenciesfunction) && $dependenciesfunction()) {
                $row[] = '';
            } else {
                $row[] = $this->format_icon_link('show', $plugin, 't/show', get_string('enable'));
                $class = 'dimmed_text';
            }

            $row[] = function_exists($dependenciesfunction) ? $dependenciesfunction() : '';

            $table->add_data($row, $class);
        }
        $table->finish_output();

        $this->view_footer();
    }

    /**
     * Write the page header
     *
     * @return None
     */
    private function view_header() {
        global $OUTPUT;
        admin_externalpage_setup('manage' . $this->subtype . 'plugins');
        // Print the page heading.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('manage' . $this->subtype . 'plugins', 'mod_pulse'));
    }

    /**
     * Write the page footer
     *
     * @return None
     */
    private function view_footer() {
        global $OUTPUT;
        echo $OUTPUT->footer();
    }

    /**
     * Check this user has permission to edit the list of installed plugins
     *
     * @return None
     */
    private function check_permissions() {
        // Check permissions.
        require_login();
        $systemcontext = context_system::instance();
        require_capability('moodle/site:config', $systemcontext);
    }

    /**
     * Hide this plugin.
     *
     * @param string $plugin - The plugin to hide
     * @return string The next page to display
     */
    public function hide_plugin($plugin) {
        set_config('enabled', 0, $this->subtype . '_' . $plugin);
        core_plugin_manager::reset_caches();
        return 'view';
    }

    /**
     * Show this plugin.
     *
     * @param string $plugin - The plugin to show
     * @return string The next page to display
     */
    public function show_plugin($plugin) {
        set_config('enabled', 1, $this->subtype . '_' . $plugin);
        core_plugin_manager::reset_caches();
        return 'view';
    }

    /**
     * This is the entry point for this controller class.
     *
     * @param string $action - The action to perform
     * @param string $plugin - Optional name of a plugin type to perform the action on
     * @return None
     */
    public function execute($action, $plugin) {
        if ($action == null) {
            $action = 'view';
        }

        $this->check_permissions();

        // Process.
        if ($action == 'hide' && $plugin != null) {
            $action = $this->hide_plugin($plugin);
        } else if ($action == 'show' && $plugin != null) {
            $action = $this->show_plugin($plugin);
        }

        // View.
        if ($action == 'view') {
            $this->view_plugins_table();
        }
    }
}
