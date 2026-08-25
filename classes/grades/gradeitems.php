<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Grade item mappings for the Process Assignment module.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_processassign\grades;

defined('MOODLE_INTERNAL') || die();

use core_grades\local\gradeitem\advancedgrading_mapping;
use core_grades\local\gradeitem\itemnumber_mapping;

/**
 * Grade item mappings for the Process Assignment module.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradeitems implements advancedgrading_mapping, itemnumber_mapping {
    /**
     * Map grade item numbers to item names.
     *
     * @return array item number => item name
     */
    public static function get_itemname_mapping_for_component(): array {
        return [
            0 => 'submissions',
            1 => 'stage1',
            2 => 'stage2',
            3 => 'stage3',
            4 => 'stage4',
            5 => 'stage5',
        ];
    }

    /**
     * List the item names that support advanced grading.
     *
     * @return array item names
     */
    public static function get_advancedgrading_itemnames(): array {
        return [
            'stage1',
            'stage2',
            'stage3',
            'stage4',
            'stage5',
        ];
    }
}
