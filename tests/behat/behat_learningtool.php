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
 * Behat pulse-related steps definitions.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\DriverException as DriverException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Course-related steps definitions.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_learningtool extends behat_base {

    /**
     * Moodle branch number.
     *
     * @return string Moodle branch number.
     */
    public function moodle_branch() {
        global $CFG;
        return $CFG->branch;
    }

    /**
     * Check that the activity has the given automatic completion condition.
     *
     * @Given the FAB button should exist
     * @param string $activityname The activity name.
     * @param string $conditionname The automatic condition name.
     * @param string $completiontype The completion type text.
     */
    public function fab_button_should_exist(): void {
        $this->execute("behat_general::should_exist", ['#tool-action-button', 'css_element']);
    }

    /**
     * Check that the activity has the given automatic completion condition.
     *
     * @Given I click on FAB button
     * @param string $activityname The activity name.
     * @param string $conditionname The automatic condition name.
     * @param string $completiontype The completion type text.
     */
    public function click_fab_button(): void {
        $this->execute("behat_general::i_click_on", ['#tool-action-button', 'css_element']);
    }

    
}