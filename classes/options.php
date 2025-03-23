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

namespace mod_pulse;

use stdClass;

/**
 * Class options
 *
 * @package    mod_pulse
 * @copyright  2024 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class options {

    /**
     * Pulse ID.
     *
     * @var int
     */
    protected int $pulseid;

    /**
     * Constructor.
     *
     * @param int $pulseid
     */
    protected function __construct(int $pulseid) {
        $this->pulseid = $pulseid;
    }

    /**
     * Initialize the pulse options.
     *
     * @param int $pulseid
     * @return \mod_pulse\options
     */
    public static function init(int $pulseid) {
        return new self($pulseid);
    }

    /**
     * Manage options.
     *
     * NOTE: For the checkbox use advcheckbox for options.
     *
     * @param array $options
     *
     * @return bool
     */
    public function manage_options(array $options): bool {
        global $DB;

        foreach ($options as $name => $value) {
            $option = new stdClass();
            $option->pulseid = $this->pulseid;
            $option->name = $name;

            $record = $DB->get_record('pulse_options', (array) $option);
            if ($record) {
                $record->value = $value;
                $DB->update_record('pulse_options', $record);
            } else {
                $option->value = $value;
                $DB->insert_record('pulse_options', $option);
            }
        }

        return false;
    }

    /**
     * Get options.
     *
     * @return array An associative array of options where the key is the option name and the value is the option value.
     */
    public function get_options(): array {
        global $DB;

        $options = $DB->get_records_menu('pulse_options', ['pulseid' => $this->pulseid], '', 'name, value');

        return $options ?: [];
    }

}
