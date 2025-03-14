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
 * User notes popout action define.
 *
 * @package   ltool_note
 * @copyright bdecent GmbH 2021
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../../../config.php');
require_once($CFG->dirroot. '/local/learningtools/ltool/note/lib.php');

require_login();
require_note_status();

$contextid = optional_param('contextid', 0, PARAM_INT);
$courseid = optional_param('course', 0, PARAM_INT);
$user = optional_param('user', 0, PARAM_INT);
$contextlevel = optional_param('contextlevel', 0, PARAM_INT);
$pagetype = optional_param('pagetype', '', PARAM_TEXT);
$pageurl = optional_param('pageurl', '', PARAM_RAW);
$pagetitle = optional_param('title', '', PARAM_TEXT);
$pageheading = optional_param('heading' , '', PARAM_TEXT);

$params = [];
$params['contextid'] = $contextid;
$params['course'] = $courseid;
$params['user'] = $user;
$params['contextlevel'] = $contextlevel;
$params['pagetype'] = $pagetype;
$params['pageurl'] = $pageurl;


list($context, $course, $cm) = get_context_info_array($contextid);

$url = new moodle_url('/local/learningtools/ltool/note/popout.php');
$pagetitle = !empty($pagetitle) ? $pagetitle : $SITE->shortname;
$pageheading = !empty($pageheading) ? $pageheading : $SITE->fullname;
$course = !empty($course) ? $course : $SITE;
$url->params($params);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_course($course);
if (isset($cm->id)) {
    $PAGE->set_cm($cm);
}
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pageheading);
$PAGE->set_pagetype($pagetype);

sesskey();

if ($contextid && $courseid && $user && $contextlevel
    && $pagetype && $pageurl) {

    $params['popoutaction'] = true;
    $actionurl = $url->out(false);
    $mform = new editorform($actionurl, $params);
    if ($mform->is_cancelled()) {
        redirect($pageurl);
    } else if ($formdata = (array)$mform->get_data()) {
        $formdata['ltnoteeditor'] = $formdata['ltnoteeditor']['text'];
        user_save_notes($contextid, $formdata);

        redirect($pageurl, get_string('successnotemessage', 'local_learningtools'),
            null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('newnote', 'local_learningtools'));
        $mform->display();
        echo $OUTPUT->footer();
    }
}

