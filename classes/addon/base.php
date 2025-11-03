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
 * Base class that all pulse addons should extend.
 *
 * @package   mod_pulse
 * @copyright 2024 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse\addon;

use stdClass;

/**
 * Base class that all pulse addons should extend.
 */
abstract class base {
    /**
     * The pulse id
     *
     * @var int
     */
    protected $pulseid;

    /**
     * Constructor
     *
     * @param int $pulseid
     */
    protected function __construct(int $pulseid) {
        $this->pulseid = $pulseid;
    }

    /**
     * Get the name of the addon
     *
     * @return string
     */
    abstract public function get_name();

    /**
     * Initialize the addon
     *
     * @param int $id The pulse id
     * @return static
     */
    public static function init(int $id) {
        // Function implementation here.
        return new static($id);
    }

    /**
     * Check if the addon is enabled
     *
     * @return bool
     */
    public function is_enabled() {
        return get_config('pulseaddon_' . $this->get_name(), 'enabled');
    }

    /**
     * Get addon settings
     *
     * @return array
     */
    public function get_settings() {
        return [];
    }
}
