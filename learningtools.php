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
 * Ltool overwrite a tools info.
 *
 * @package   local_learningtools
 * @copyright bdecent GmbH 2021
 * @category  autoloading
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();

abstract class learningtools {
    /**
     * Tool Name.
     */
    abstract public function get_tool_name();
    /**
     * Tool icon.
     */
    abstract public function get_tool_icon();

    /**
     * Get tool info.
     */
    public function get_tool_info() {
        global $OUTPUT;

        $data = [];
        $data['name'] = $this->get_tool_name();
        $data['icon'] = $this->get_tool_icon();
        return $data;
    }
}
