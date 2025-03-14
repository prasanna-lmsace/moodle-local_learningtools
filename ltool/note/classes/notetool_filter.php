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
 * List of the user Notes filter action.
 *
 * @package   ltool_note
 * @copyright bdecent GmbH 2021
 * @category  filter
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace ltool_note;
use moodle_url;
use html_writer;
use stdclass;
use context_system;
use context_course;
use context_user;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot. '/local/learningtools/lib.php');
require_once($CFG->dirroot. '/local/learningtools/ltool/note/lib.php');

/**
 *  List of the user notes filter action.
 */
class notetool_filter {
    /**
     * @param int current user id
     * @param int select course
     * @param string sort type
     * @param int activity base status
     * @param int course id
     * @param int related user
     * @param int teacher view status
     * @param array page url params
     * @param string page url
     * 
     */
    public function __construct($userid, $selectcourse, $sort, $activity, $courseid,
        $childid, $teacher, $urlparams, $pageurl) {

        $this->userid = $userid;
        $this->selectcourse = $selectcourse;
        $this->sort = $sort;
        $this->activity = $activity;
        $this->courseid = $courseid;
        $this->childid = $childid;
        $this->teacher = $teacher;
        $this->urlparams = $urlparams;
        $this->pageurl = $pageurl;

    }

    /**
     * Gets the available user sql.
     * @return array user sql and params
     */

    public function get_user_sql() {
        global $DB;

        $usersql = '';
        $userparams = [];

        if ($this->courseid) {
            if (!$this->childid) {
                $students = get_students_incourse($this->courseid);
                if (!empty($students)) {
                    list($studentsql, $userparams) = $DB->get_in_or_equal($students, SQL_PARAMS_NAMED);
                    $usersql .= 'user '. $studentsql;
                }
            } else {
                $usersql = 'user = :userid';
                $userparams = ['userid' => $this->childid];
            }
        } else if ($this->childid) {
            $usersql = 'user = :userid';
            $userparams = ['userid' => $this->childid];
        } else {
            $usersql = 'user = :userid';
            $userparams = ['userid' => $this->userid];
        }
        return ['sql' => $usersql, 'params' => $userparams];
    }

    /**
     * Gets the course selected records. 
     * @return array course selector info.
     */
    public function get_course_selector() {

        global $DB, $OUTPUT;
        $template = [];
        $courses = [];
        $usercondition = $this->get_user_sql();
        $usersql = $usercondition['sql'];
        $userparams = $usercondition['params'];
        $records = $DB->get_records_sql("SELECT * FROM {learningtools_note} WHERE $usersql", $userparams);
        if (!empty($records)) {
            foreach ($records as $record) {
                $instanceblock = check_note_instanceof_block($record);
                if (isset($instanceblock->instance) && $instanceblock->instance == 'course' || $instanceblock->instance == 'mod') {
                    $courses[] = $instanceblock->courseid;
                }
            }
        }

        $courses = get_courses_name(array_unique($courses), '/local/learningtools/
            ltool/note/list.php', $this->selectcourse, $this->childid, $this->courseid);

        $template['courses'] = $courses;
        $template['coursefilter'] = true;

        $pageparams = [];
        if ($this->childid) {
            $pageparams['userid'] = $childid;
        }
        if ($this->courseid) {
            $pageparams['courseid'] = $courseid;
        }

        $pageurl = new moodle_url('/local/learningtools/ltool/note/list.php', $pageparams);
        $template['pageurl'] = $pageurl->out(false);
        return $template;
    }

    /**
     * Gets the activity selected record.
     * @return array course activity selector records.
     */

    public function get_activity_selector() {
        global $DB, $OUTPUT;

        $usercondition = $this->get_user_sql($this->courseid, $this->childid);
        $usersql = $usercondition['sql'];
        $userparams = $usercondition['params'];
        $sql = "SELECT * FROM {learningtools_note}
        WHERE $usersql AND course = :course AND coursemodule != 0 GROUP BY coursemodule";
        $params = [
        'course' => $this->selectcourse,
        ];

        $params = array_merge($params, $userparams);
        $records = $DB->get_records_sql($sql, $params);
        $data = [];

        if (!empty($records)) {
            foreach ($records as $record) {
                $record->courseid = $record->course;
                $list['mod'] = get_module_name($record);
                $activityparams = $this->urlparams;
                $activityparams['activity'] = $record->coursemodule;
                $filterurl = new moodle_url("/local/learningtools/ltool/note/list.php", $activityparams);
                $list['filterurl'] = $filterurl->out(false);
                if ($record->coursemodule == $this->activity) {
                    $list['selected'] = "selected";
                } else {
                    $list['selected'] = "";
                }
                $data[] = $list;
            }

        }
        return $data;
    }

    /**
     * Gets the sort selected record.
     * @return array sort selector records.
     */
    public function get_sort_instance() {

        global $OUTPUT;
        $template = [];
        $coursesortparams = ['sort' => 'course'];
        $coursesortparams = array_merge($this->urlparams, $coursesortparams);
        $datesortparams = ['sort' => 'date'];
        $datesortparams = array_merge($this->urlparams, $datesortparams);
        $activitysortparams = array('sort' => 'activity');
        $activitysortparams = array_merge($this->urlparams, $activitysortparams);
        $dateselect = '';
        $courseselect = '';
        $activityselect = '';
        $sorttype = '';
        $iclass = '';

        if ($this->sort == 'date') {
            $dateselect = "selected";
        } else if ($this->sort == 'course') {
            $courseselect = "selected";
        } else if ($this->sort == 'activity') {
            $activityselect = "selected";
        }

        if (isset($this->urlparams['sorttype'])) {
            $sorttype = $this->urlparams['sorttype'];
            if ($sorttype == 'desc') {
                $iclass = 'fa fa-sort-amount-desc';
            } else {
                $iclass = 'fa fa-sort-amount-asc';
            }
        }

        $coursesort = new moodle_url('/local/learningtools/ltool/note/list.php', $coursesortparams);
        $datesort = new moodle_url('/local/learningtools/ltool/note/list.php', $datesortparams);
        $activitysort = new moodle_url('/local/learningtools/ltool/note/list.php', $activitysortparams);

        if ($this->selectcourse) {
            $template['activitysort'] = $activitysort->out(false);
        } else {
            $template['coursesort'] = $coursesort->out(false);
        }

        $template['dateselect'] = $dateselect;
        $template['courseselect'] = $courseselect;
        $template['activityselect'] = $activityselect;
        $template['datesort'] = $datesort->out(false);
        $template['sortfilter'] = true;
        $template['sorttype'] = $sorttype;
        $template['iclass'] = $iclass;
        return $template;
    }

    /**
     * Gets the available notes records.
     * @return array list of the records.
     */
    public function get_note_records() {
        global $DB;

        $coursesql = '';
        $sortsql = '';
        $usersql = '';

        $sorttypesql = $this->urlparams['sorttype'];

        if ($this->courseid) {
            if (!$this->childid) {
                $students = get_students_incourse($this->courseid);
                if (!empty($students)) {
                    list($studentsql, $params) = $DB->get_in_or_equal($students, SQL_PARAMS_NAMED);
                    $usersql .= 'user '. $studentsql;
                }
            } else if ($this->childid) {
                    $usersql = 'user = :userid';
                    $params = ['userid' => $this->childid];
            }
        } else if ($this->childid) {
            $usersql = 'user = :userid';
            $params = ['userid' => $this->childid];
        } else {
            $usersql = 'user = :userid';
            $params = ['userid' => $this->userid];
        }

        if ($this->sort == 'date') {
            $select = 'FLOOR(timecreated/86400) AS date,';
            $sortsql .= "GROUP BY FLOOR(timecreated/86400) ORDER BY timecreated $sorttypesql";
        } else if ($this->sort == 'course') {
            $select = 'course,';
            $sortsql .= "AND course != 1  GROUP BY course ORDER BY course $sorttypesql";
        } else if ($this->sort == 'activity') {
            $select = 'coursemodule,';
            $sortsql .= " AND coursemodule != 0 GROUP BY coursemodule ORDER BY coursemodule $sorttypesql";
        }

        if ($this->selectcourse) {
            $coursesql .= 'AND course = :course';
            $params['course'] = $this->selectcourse;
            if ($this->activity) {
                $coursesql .= 'AND coursemodule = :activity';
                $params['activity'] = $this->activity;
            }
        }

        $sql = "SELECT  $select GROUP_CONCAT(id) AS notesgroup
        FROM {learningtools_note} WHERE $usersql $coursesql
        $sortsql";

        $records = $DB->get_records_sql($sql, $params, $this->urlparams['page']
        * $this->urlparams['perpage'], $this->urlparams['perpage']);

        // Get the total notes.
        $countreports = $DB->get_records_sql("SELECT * FROM {learningtools_note} WHERE $usersql $coursesql $sortsql", $params);

        $this->totalnotes = count($countreports);
        return $records;
    }

    /**
     * Loads all the user records info to return.
     * @return string display notes list html
     */
    public function get_main_body() {

        global $OUTPUT, $DB;

        $template = [];
        $reports = [];
        // Gets the available records.
        $records = $this->get_note_records();

        $data = [];

        if (!empty($records)) {
            $sorttype = $this->urlparams['sorttype'];
            $sortsql = "ORDER BY timecreated $sorttype";
            foreach ($records as $record) {
                $res = [];
                if (isset($record->notesgroup)) {
                    list($dbsql, $dbparam) = $DB->get_in_or_equal(explode(",", $record->notesgroup), SQL_PARAMS_NAMED);
                    $list = $DB->get_records_sql("SELECT * FROM {learningtools_note} WHERE id $dbsql $sortsql", $dbparam);
                    $res['notes'] = $list;

                    if ($this->sort == 'date') {
                        $head = userdate(($record->date * 86400), '%B, %dth %Y', '', false);
                    } else if ($this->sort == 'course') {
                        $head = get_course_name($record->course);
                    } else if ($this->sort == 'activity') {
                        $module = new stdclass;
                        $module->coursemodule = $record->coursemodule;
                        $module->courseid = $this->selectcourse;
                        $head = get_module_name($module);
                    }
                    $res['title'] = $head;
                }
                $reports[] = $res;
            }
        }

        $cnt = 1;
        if (!empty($reports)) {
            foreach ($reports as $report) {
                $info = [];
                if (isset($report['notes'])) {
                    $notes = $this->get_speater_plug($report['notes']);
                    $info['notes'] = $notes;
                    $info['title'] = isset($report['title']) ? $report['title'] : '';
                    $info['range'] = $cnt.'-block';
                    $info['active'] = ($cnt == 1) ? true : false;
                }
                $cnt++;
                $data[] = $info;
            }
        }

        if (isset($this->urlparams['sorttype'])) {
            $sorttype = $this->urlparams['sorttype'];
            if ($this->sort == 'course' || $this->sort == 'activity') {
                if ($sorttype == 'asc') {
                    $queryfunction = 'querycoursesortasc';
                } else if ($sorttype == 'desc') {
                    $queryfunction = 'querycoursesortdesc';
                }
                usort($data, array($this, $queryfunction));
            }
        }

        $template['records'] = $data;
        $template['ltnotes'] = true;

        if (!$this->activity) {
            $template['sortfilter'] = $this->get_sort_instance();
        }

        if (!$this->courseid) {
            $template['coursefilter'] = $this->get_course_selector();
        }

            $template['enableactivityfilter'] = !empty($this->selectcourse) ? true : false;

        if ($this->selectcourse) {
            $coursefilterparams = array('selectcourse' => $this->selectcourse);
            $coursefilterparams = array_merge($coursefilterparams, $this->urlparams);
            unset($coursefilterparams['activity']);
            $coursefilterurl = new moodle_url('/local/learningtools/ltool/note/list.php', $coursefilterparams);
            $template['coursefilterurl'] = $coursefilterurl->out(false);
            $template['activityfilter'] = $this->get_activity_selector();
        }

        // Pagination.
        $template['pageingbar'] = $OUTPUT->paging_bar($this->totalnotes,
            $this->urlparams['page'], $this->urlparams['perpage'], $this->pageurl);

        return $OUTPUT->render_from_template('ltool_note/ltnote', $template);

    }
    /**
     * sort ascending order.
     */
    public function querycoursesortasc($x, $y) {
        return strcasecmp($x['title'], $y['title']);
    }
    /**
     * sort descending order.
     */
    public function querycoursesortdesc($x, $y) {
        return strcasecmp($y['title'], $x['title']);
    }

    /**
     * Get the each Notes records.
     * @param object notes record
     * @return array record
     */
    public function get_speater_plug($records) {
        global $USER;

        $report = [];
        $context = context_system::instance();
        if (!empty($records)) {
            foreach ($records as $record) {
                $data = check_instanceof_block($record);
                $list['id'] = $record->id;
                $list['instance'] = $this->get_instance_note($data);
                $list['base'] = $this->get_title_note($data);
                $list['note'] = !empty($record->note) ? $record->note : '';
                $list['time'] = userdate($record->timecreated, '%B %d, %Y, %I:%M', '', false);
                $list['viewurl'] = $this->get_view_url($record);

                if (!empty($this->courseid) && !$this->childid) {
                    $coursecontext = context_course::instance($this->courseid);
                    if (has_capability('ltool/note:managenote', $coursecontext)) {
                        $list['delete'] = $this->delete_note_info($record);
                        $list['edit'] = $this->edit_note_info($record);
                    }

                } else if ($this->childid) {
                    if ($this->teacher) {
                        $coursecontext = context_course::instance($this->courseid);
                        if (has_capability('ltool/note:managenote', $coursecontext)) {
                            $list['delete'] = $this->delete_note_info($record);
                            $list['edit'] = $this->edit_note_info($record);
                        }
                    } else {
                        if ($this->childid != $USER->id) {
                            $usercontext = context_user::instance($this->childid);
                            if (has_capability('ltool/note:managenote', $usercontext, $USER->id)) {
                                $list['delete'] = $this->delete_note_info($record);
                                $list['edit'] = $this->edit_note_info($record);
                            }
                        } else {
                            if (has_capability('ltool/note:manageownnote', $context)) {
                                $list['delete'] = $this->delete_note_info($record);
                                $list['edit'] = $this->edit_note_info($record);
                            }
                        }
                    }

                } else {
                    if (has_capability('ltool/note:manageownnote', $context)) {
                        $list['delete'] = $this->delete_note_info($record);
                        $list['edit'] = $this->edit_note_info($record);
                    }
                }
                $report[] = $list;
            }
        }
        return $report;
    }

    /**
     * Get the notes edit records
     * @param object notes record
     * @return string edit notes html
     */
    public function edit_note_info($row) {
        global $OUTPUT;
        $stredit = get_string('edit');
        $buttons = [];
        $returnurl = new moodle_url('/local/learningtools/ltool/note/editlist.php');
        $optionyes = array('edit' => $row->id, 'sesskey' => sesskey());
        $optionyes = array_merge($optionyes, $this->urlparams);
        $url = new moodle_url($returnurl, $optionyes);
        $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/edit', $stredit));
        $buttonhtml = implode(' ', $buttons);
        return $buttonhtml;

    }

    /**
     * Get the notes delete records
     * @param object note record
     * @return string delete note html
     */
    public function delete_note_info($row) {

        global $OUTPUT;
        $strdelete = get_string('delete');
        $buttons = [];
        $returnurl = new moodle_url('/local/learningtools/ltool/note/list.php');
        $optionyes = array('delete' => $row->id, 'sesskey' => sesskey());
        $optionyes = array_merge($optionyes, $this->urlparams);
        $url = new moodle_url($returnurl, $optionyes);
        $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/delete', $strdelete));
        $buttonhtml = implode(' ', $buttons);
        return $buttonhtml;
    }

    /**
     * Get the notes instance view url.
     * @param object notes record
     * @return string view html
     */
    public function get_view_url($row) {
        global $OUTPUT;
        $data = check_instanceof_block($row);
        $viewurl = '';
        if ($data->instance == 'course') {
            $courseurl = new moodle_url('/course/view.php', array('id' => $data->courseid));
            $viewurl = $OUTPUT->single_button($courseurl, get_string('viewcourse', 'local_learningtools'), 'get');
        } else if ($data->instance == 'user') {
            $viewurl = 'user';
        } else if ($data->instance == 'mod') {
            $modname = get_module_name($data, true);
            $modurl = new moodle_url("/mod/$modname/view.php", array('id' => $data->coursemodule));
            $viewurl = $OUTPUT->single_button($modurl, get_string('viewactivity', 'local_learningtools'), 'get');
        } else if ($data->instance == 'system') {
            $viewurl = 'system';
        } else if ($data->instance == 'block') {
            $viewurl = 'block';
        }
        return $viewurl;
    }

    /**
     * Gets the instance of notes details
     * @param object instance record
     * @return string instance name
     */
    public function get_instance_note($data) {
        $instance = '';
        if ($data->instance == 'course') {
            $instance = get_course_name($data->courseid);
        } else if ($data->instance == 'user') {
            $instance = 'user';
        } else if ($data->instance == 'mod') {
            $instance = get_course_name($data->courseid);
        } else if ($data->instance == 'system') {
             $instance = 'system';
        } else if ($data->instance == 'block') {
             $instance = 'block';
        }
        return $instance;
    }

    /**
     * Gets the notes title instance
     * @param object instance record
     * @return string instance title name
     */
    public function get_title_note($data) {

        $title = '';
        if ($data->instance == 'course') {
            $title = get_course_name($data->courseid);
        } else if ($data->instance == 'user') {
            $title = 'user';
        } else if ($data->instance == 'mod') {
            $title = get_module_coursesection($data, 'note');
        } else if ($data->instance == 'system') {
            $title = 'system';
        } else if ($data->instance == 'block') {
            $title = 'block';
        }
        return $title;
    }
}
