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
 * Submission and grading notifications.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_processassign\local;

use core_user;
use html_writer;
use moodle_url;

/**
 * Submission and grading notifications.
 *
 * @package    mod_processassign
 * @copyright  2026 Murdoch Business School
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_manager {
    /**
     * Send a notification message via the message API.
     *
     * @param string $name the message provider name
     * @param \stdClass $userfrom the sending user
     * @param \stdClass $userto the receiving user
     * @param string $subject the message subject
     * @param string $body the plain-text message body
     * @param moodle_url $url context url for the message
     * @param string $urlname label for the context url
     * @param array $customdata optional custom data attached to the message
     */
    public static function send_message(
        string $name,
        $userfrom,
        $userto,
        string $subject,
        string $body,
        moodle_url $url,
        string $urlname,
        array $customdata = []
    ): void {
        $message = new \core\message\message();
        $message->component = 'mod_processassign';
        $message->name = $name;
        $message->userfrom = $userfrom;
        $message->userto = $userto;
        $message->subject = $subject;
        $message->fullmessageformat = FORMAT_HTML;
        $message->fullmessage = $body;
        $message->fullmessagehtml = html_writer::tag('p', s($body));
        $message->fullmessagesms = $subject;
        $message->smallmessage = $subject;
        $message->notification = 1;
        $message->contexturl = $url->out(false);
        $message->contexturlname = $urlname;
        if ($customdata) {
            $message->customdata = $customdata;
        }

        message_send($message);
    }

    /**
     * Filter a list of graders down to those allowed to see the student under separate groups mode.
     *
     * @param array $graders candidate grader user records
     * @param \stdClass $cm the course module record
     * @param \stdClass $course the course record
     * @param \context_module $context the module context
     * @param \stdClass $student the student user record
     * @return array the filtered grader user records
     */
    public static function filter_graders_for_student_groups(
        array $graders,
        $cm,
        $course,
        $context,
        $student
    ): array {
        global $CFG;

        require_once($CFG->dirroot . '/group/lib.php');

        if (groups_get_activity_groupmode($cm, $course) !== SEPARATEGROUPS) {
            return $graders;
        }

        $groupingid = $cm->groupingid ?? 0;
        $studentgroups = groups_get_all_groups($course->id, $student->id, $groupingid, 'g.id');
        $studentgroupids = array_map('intval', array_keys($studentgroups ?: []));

        return array_filter($graders, function ($grader) use ($course, $context, $groupingid, $studentgroupids) {
            if (has_capability('moodle/site:accessallgroups', $context, $grader->id)) {
                return true;
            }
            if (!$studentgroupids) {
                return false;
            }
            $gradergroups = groups_get_all_groups($course->id, $grader->id, $groupingid, 'g.id');
            $gradergroupids = array_map('intval', array_keys($gradergroups ?: []));
            return (bool)array_intersect($studentgroupids, $gradergroupids);
        });
    }

    /**
     * Notify the graders that a student has submitted a stage.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $cm the course module record
     * @param \stdClass $course the course record
     * @param \context_module $context the module context
     * @param \stdClass $stage the stage record
     * @param \stdClass $student the submitting student
     */
    public static function notify_graders($processassign, $cm, $course, $context, $stage, $student) {
        $graders = get_enrolled_users($context, 'mod/processassign:grade');
        $graders = self::filter_graders_for_student_groups($graders, $cm, $course, $context, $student);
        if (!$graders) {
            return;
        }

        $subject = get_string('submissionnotificationsubject', 'processassign', format_string($processassign->name));
        $url = new moodle_url('/mod/processassign/view.php', ['id' => $cm->id]);
        $body = get_string('submissionnotificationbody', 'processassign', (object)[
            'student' => fullname($student),
            'stage' => format_string($stage->name),
            'activity' => format_string($processassign->name),
            'course' => format_string($course->fullname),
            'url' => $url->out(false),
        ]);

        foreach ($graders as $grader) {
            self::send_message(
                'grader_notification',
                $student,
                $grader,
                $subject,
                $body,
                $url,
                format_string($processassign->name),
                [
                    'processassignid' => $processassign->id,
                    'stageid' => $stage->id,
                    'studentid' => $student->id,
                ]
            );
        }
    }

    /**
     * Notify a student that their stage submission has been graded.
     *
     * @param \stdClass $processassign the instance record
     * @param \stdClass $cm the course module record
     * @param \stdClass $course the course record
     * @param \stdClass $stage the stage record
     * @param \stdClass $student the student to notify
     */
    public static function notify_student($processassign, $cm, $course, $stage, $student) {
        global $USER;

        $subject = get_string('gradenotificationsubject', 'processassign', format_string($processassign->name));
        $url = new moodle_url('/mod/processassign/view.php', ['id' => $cm->id]);
        $body = get_string('gradenotificationbody', 'processassign', (object)[
            'stage' => format_string($stage->name),
            'activity' => format_string($processassign->name),
            'course' => format_string($course->fullname),
            'url' => $url->out(false),
        ]);

        $userfrom = !empty($USER->id) ? $USER : core_user::get_noreply_user();
        self::send_message(
            'student_notification',
            $userfrom,
            $student,
            $subject,
            $body,
            $url,
            format_string($processassign->name),
            [
                'processassignid' => $processassign->id,
                'stageid' => $stage->id,
                'studentid' => $student->id,
            ]
        );
    }
}
