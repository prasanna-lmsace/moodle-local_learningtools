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
 * List of the user bookmarks filter action.
 *
 * @package   ltool_bookmarks
 * @copyright bdecent GmbH 2021
 * @category  filter
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace ltool_bookmarks;
use moodle_url;
use context_user;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/learningtools/lib.php');
/**
 * List of the user bookmarks filter action.
 */
class bookmarkstool_filter {
    /**
     * @param int current user id
     * @param int course id
     * @param int other user id
     * @param int teacher view stauts
     * @param array page url parameters
     * @param string base url
     */
    public function __construct($userid, $courseid, $childid, $teacher, $urlparams, $baseurl) {
        $this->userid = $userid;
        $this->courseid = $courseid;
        $this->child = $childid;
        $this->urlparams = $urlparams;
        $this->teacher = $teacher;
        $this->baseurl = $baseurl;

        $pageurlparams = [];
        if ($teacher) {
            $pageurlparams['teacher'] = $teacher;
        }
        if ($courseid) {
            $pageurlparams['courseid'] = $courseid;
        }

        if ($childid) {
            $pageurlparams['userid'] = $childid;
        }
        $this->pageurl = new moodle_url('/local/learningtools/ltool/bookmarks/list.php', $pageurlparams);
    }

    /**
     * Displays the course selector info.
     * @param int select course id.
     * @param string user select sql
     * @param array user params
     * @return string course selector html.
     */
    public function get_course_selector($selectcourse, $usercondition, $userparams) {
        global $DB, $OUTPUT;

        $template = [];
        $courses = [];
        $urlparams = [];
        $records = $DB->get_records_sql("SELECT * FROM {learningtools_bookmarks} WHERE $usercondition", $userparams);

        if (!empty($records)) {
            foreach ($records as $record) {
                $instanceblock = check_instanceof_block($record);
                if (isset($instanceblock->instance) && $instanceblock->instance == 'course' || $instanceblock->instance == 'mod') {
                    $courses[] = $instanceblock->courseid;
                }
            }
        }
        // Get courses.
        $courses = get_courses_name(array_unique($courses), $this->baseurl, $selectcourse, $this->child);
        $template['courses'] = $courses;
        $template['pageurl'] = $this->pageurl->out(false);

        return $template;
    }


    /**
     * Displays the Bookmarks sort selector info.
     * @return string bookmarks sort html.
     */
    public function get_sort_instance() {
        global $OUTPUT;

        $template = [];
        $coursesortparams = array('sort' => 'course');
        $coursesortparams = array_merge($this->urlparams, $coursesortparams);

        $datesortparams = array('sort' => 'date');
        $datesortparams = array_merge($this->urlparams, $datesortparams);

        $dateselect = '';
        $courseselect = '';
        if (isset($this->urlparams['sort'])) {
            $sort = $this->urlparams['sort'];
            if ($sort == 'date') {
                $dateselect = "selected";
            } else if ($sort == 'course') {
                $courseselect = "selected";
            }
        }

        if (isset($this->urlparams['sorttype'])) {
            $sorttype = $this->urlparams['sorttype'];
            if ($sorttype == 'desc') {
                $iclass = 'fa fa-sort-amount-desc';
            } else {
                $iclass = 'fa fa-sort-amount-asc';
            }
        } else {
            $iclass = 'fa fa-sort-amount-asc';
            $sorttype = 'asc';
        }

        $coursesort = new moodle_url('/local/learningtools/ltool/bookmarks/list.php', $coursesortparams);
        $datesort = new moodle_url('/local/learningtools/ltool/bookmarks/list.php', $datesortparams);
        $template['coursesort'] = $coursesort->out(false);
        $template['datesort'] = $datesort->out(false);
        $template['dateselect'] = $dateselect;
        $template['courseselect'] = $courseselect;
        $template['iclass'] = $iclass;
        $template['sorttype'] = $sorttype;
        return $template;
    }

    /**
     * bookmarks filter main function
     * @param string record condition sql 
     * @param array record conditon params
     * @param string sort type
     * @param int current records page count
     * @param int display perpage info
     * @return array available records to display bookmarks list data.
     */

    public function get_main_body($sqlconditions, $sqlparams, $sort, $sorttype, $page, $perpage) {
        global $DB, $OUTPUT;

        $orderconditions  = '';
        if ($sort == 'course') {
            $orderconditions .= "ORDER BY c.fullname $sorttype, coursemodule";
        } else {
            $orderconditions .= "ORDER BY timecreated $sorttype";
        }

        $sql = "SELECT b.*, c.fullname
            FROM {learningtools_bookmarks} b
            LEFT JOIN {course} c ON c.id = b.course
            WHERE $sqlconditions $orderconditions";
        $records = $DB->get_records_sql($sql, $sqlparams, $page * $perpage, $perpage);

        $totalbookmarks = $DB->count_records_sql("SELECT count(*) FROM {learningtools_bookmarks}
            WHERE $sqlconditions", $sqlparams);
        $pageingbar = $OUTPUT->paging_bar($totalbookmarks, $page, $perpage, $this->baseurl);

        $res = [];
        $reports = [];
        if (!empty($records)) {
            foreach ($records as $row) {
                $list = [];
                $data = check_instanceof_block($row);
                $list['instance'] = $this->get_instance_bookmark($data);
                $list['instanceinfo'] = $this->get_instance_bookmarkinfo($data);
                $list['courseinstance'] = ($data->instance == 'course') ? true : false;
                $list['time'] = $this->get_bookmark_time($row);
                $list['delete'] = $this->get_bookmark_deleteinfo($row);
                $list['view'] = $this->get_bookmark_viewinfo($row);
                $list['course'] = $row->course;
                $reports[] = $list;
            }
        }

        $res['pageingbar'] = $pageingbar;
        $res['bookmarks'] = $reports;
        return $res;
    }

    /**
     * List of the bookmarks get the instance name column.
     * @param mixed $row
     * @return string result
     */
    public function get_instance_bookmark($data) {
        $bookmark = '';
        if ($data->instance == 'course') {
            $bookmark = get_course_name($data->courseid);
        } else if ($data->instance == 'user') {
            $bookmark = 'user';
        } else if ($data->instance == 'mod') {
            $bookmark = get_module_name($data);
        } else if ($data->instance == 'system') {
             $bookmark = 'system';
        } else if ($data->instance == 'block') {
             $bookmark = 'block';
        }
        return $bookmark;
    }

    /**
     * List of the bookmarks get info column.
     * @param  mixed $row
     * @return string result
     */
    public function get_instance_bookmarkinfo($data) {
         $bookmarkinfo = '';
        if ($data->instance == 'course') {
            $bookmarkinfo = get_course_categoryname($data->courseid);
        } else if ($data->instance == 'user') {
            $bookmarkinfo = 'user';
        } else if ($data->instance == 'mod') {
            $bookmarkinfo = get_module_coursesection($data);
        } else if ($data->instance == 'system') {
             $bookmarkinfo = 'system';
        } else if ($data->instance == 'block') {
             $bookmarkinfo = 'block';
        }
        return $bookmarkinfo;
    }

    /**
     * List of the bookmarks get the started time.
     * @param  mixed $row
     * @return mixed result
     */
    public function get_bookmark_time($record) {
        return userdate($record->timecreated, '%B %d, %Y, %I:%M %p', '', false);
    }

    /**
     * List of the bookmarks get the delete action.
     * @param  mixed $row
     * @return string result
     */

    public function get_bookmark_deleteinfo($row) {
        global $OUTPUT, $USER;
        $context = \context_system::instance();
        $particularuser = null;

        if ($this->courseid || $this->child) {
            $capability = "ltool/bookmarks:managebookmarks";

            if ($this->courseid && !$this->child) {
                $context = \context_course::instance($this->courseid);
            } else if ($this->child) {

                if ($this->teacher) {
                    $context = \context_course::instance($this->courseid);
                } else {
                    if ($this->child != $USER->id) {
                        $context = context_user::instance($this->child);
                        $particularuser = $USER->id;
                    } else {
                        $capability = 'ltool/bookmarks:manageownbookmarks';
                        $context = \context_system::instance();
                    }
                }
            }

            if (has_capability($capability, $context, $particularuser)) {
                $strdelete = get_string('delete');
                $buttons = [];
                $returnurl = new moodle_url('/local/learningtools/ltool/bookmarks/list.php');
                $deleteparams = array('delete' => $row->id, 'sesskey' => sesskey(),
                    'courseid' => $this->courseid);
                $deleteparams = array_merge($deleteparams, $this->urlparams);
                $url = new moodle_url($returnurl, $deleteparams);
                $buttons[] = \html_writer::link($url, $OUTPUT->pix_icon('t/delete', $strdelete));
                $buttonhtml = implode(' ', $buttons);
                return $buttonhtml;
            }

        } else {
            if (has_capability('ltool/bookmarks:manageownbookmarks', $context)) {
                $strdelete = get_string('delete');
                $buttons = [];
                $returnurl = new moodle_url('/local/learningtools/ltool/bookmarks/list.php');
                $deleteparams = array('delete' => $row->id, 'sesskey' => sesskey());
                $deleteparams = array_merge($deleteparams, $this->urlparams);
                $url = new moodle_url($returnurl, $deleteparams);;
                $buttons[] = \html_writer::link($url, $OUTPUT->pix_icon('t/delete', $strdelete));
                $buttonhtml = implode(' ', $buttons);
                return $buttonhtml;
            }
        }
        return '';
    }

    /**
     * list of the bookmarks get the view action.
     * @param  mixed $row
     * @return mixed result
     */

    public function get_bookmark_viewinfo($row) {
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

}
