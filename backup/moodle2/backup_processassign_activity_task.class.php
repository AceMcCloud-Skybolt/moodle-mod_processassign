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
 * Backup task definition for the Process Assignment module.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/processassign/backup/moodle2/backup_processassign_stepslib.php');

/**
 * Backup task for a Process Assignment activity.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_processassign_activity_task extends backup_activity_task {
    /**
     * No specific settings for this activity.
     */
    protected function define_my_settings() {
    }

    /**
     * Define the backup steps.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_processassign_activity_structure_step('processassign_structure', 'processassign.xml'));
    }

    /**
     * Encode links to processassign pages so they can be decoded on restore.
     *
     * @param string $content content to encode
     * @return string the encoded content
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        $search = "/(" . $base . "\/mod\/processassign\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@PROCESSASSIGNINDEX*$2@$', $content);

        $search = "/(" . $base . "\/mod\/processassign\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@PROCESSASSIGNVIEWBYID*$2@$', $content);

        return $content;
    }
}
