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
 * rtcproctorv module
 *
 * @package mod_rtcproctorv
 * @copyright  2020 Nopparut Saelim
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require("../../config.php");
require_once("$CFG->dirroot/mod/rtcproctorv/lib.php");
global $USER, $OUTPUT;

$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$CurPageURL = $protocol . $_SERVER['HTTP_HOST'] . '/mod/rtcproctorv/';

define("TEACHER_URL", $CurPageURL . "rtcproctor/rtcproctor-m.php");
define("STUDENT_URL", $CurPageURL . "rtcproctor/rtcps3.php");
define("WEBRTC_SERVER", get_config("rtcproctorv", "rtc_signaling_server"));

$id       = optional_param('id', 0, PARAM_INT);        // Course module ID
$u        = optional_param('u', 0, PARAM_INT);         // URL instance id

if ($u) {  // Two ways to specify the module
    $conf = $DB->get_record('rtcproctorv', array('id'=>$u), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('rtcproctorv', $conf->id, $conf->courseid, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('rtcproctorv', $id, 0, false, MUST_EXIST);
    $conf = $DB->get_record('rtcproctorv', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/rtcproctorv:view', $context);

// Completion and trigger events.
rtcproctorv_view($conf, $course, $cm, $context);

$PAGE->set_url('/mod/rtcproctorv/view.php', array('id' => $cm->id));

rtcproctorv_print_header($conf, $cm, $course);
rtcproctorv_print_heading($conf, $cm, $course);
rtcproctorv_print_intro($conf, $cm, $course);

$allowedit  = has_capability('mod/rtcproctorv:edit', $context);
$allowview  = has_capability('mod/rtcproctorv:view', $context);

$out = "";
if ($allowedit) {
//    echo("<br><br><br>Teacher<br><br><br>");
    echo("<script src='./copy-cilpboard.js'></script>");
    $course_context = context_course::instance($course->id);
    $students = get_role_users(5 , $course_context);
    $conf_room = $students;
    $table = new html_table();
    $table->id = "student-list";
    $table->head = array('Student ID','Passcode');
    foreach ($conf_room as &$value) {
        $studentId = explode("@", $value->email)[0];
        $table->data[] = array($studentId, hash('fnv1a32', $studentId . "-in-" . $cm->id));
    }
    $out .= html_writer::link(TEACHER_URL , TEACHER_URL);
    $out .= "<br><br>";
    $out .= html_writer::start_div();
    $out .= '<input class="btn btn-default" type="button" value="Copy to clipboard" onclick="selectElementContents(document.querySelector(\'#student-list > tbody\'))">';
    $out .= rtcproctorv_teacher_form($table->data);
    $out .= html_writer::end_div();
    $out .= "<br>";
    $out .= html_writer::table($table);
} else if ($allowview) {
//    echo("<br><br><br>Student<br><br><br>");
    echo("<script src='./qrcode.min.js'></script>");

    $username = explode("@", $USER->email)[0];
    $conf_st = new StdClass();
    $conf_st->uuid = hash('fnv1a32', $username . "-in-" . $cm->id);

    $url_st = STUDENT_URL . "#" . $conf_st->uuid;
    $out .= html_writer::label("Your passcode is : {$conf_st->uuid}", "");
    $out .= "<br>";
    $out .= rtcproctorv_student_form($url_st);
    $out .= "<br><br>";
    $out .= html_writer::div("", "", array('id'=>'qrcode'));
    $out .= "<br>";
    $out .= html_writer::script('new QRCode(document.getElementById("qrcode"), "' . $url_st . '");');
}
echo $out;
echo $OUTPUT->footer();

function rtcproctorv_print_header($conf, $cm, $course) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname.': '.$conf->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($conf);
    echo $OUTPUT->header();
}

/**
 * Print rtcproctorv heading.
 * @param object $conf
 * @param object $cm
 * @param object $course
 * @param bool $notused This variable is no longer used.
 * @return void
 */
function rtcproctorv_print_heading($conf, $cm, $course, $notused = false) {
    global $OUTPUT;
    echo $OUTPUT->heading(format_string($conf->name), 2);
}

/**
 * Print rtcproctorv introduction.
 * @param object $conf
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function rtcproctorv_print_intro($conf, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($conf->displayoptions) ? array() : unserialize($conf->displayoptions);
    if ($ignoresettings or !empty($options['printintro'])) {
        if (trim(strip_tags($conf->intro))) {
            echo $OUTPUT->box_start('mod_introbox', 'rtcproctorvintro');
            echo format_module_intro('rtcproctorv', $conf, $cm->id);
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * Post form teacher view

 * @param object $data
 * @return string
 */
function rtcproctorv_teacher_form($data) {
    $form_data = '  ';
    $form_data .= '<form id="teacher_form" method="post" style="display: inline-block;" action="'.TEACHER_URL.'" target="_blank" >';
    foreach ($data as &$value) {
        $form_data .= '<input type="hidden" name="' . $value[0] . '" value="' . $value[1] . '" />';
    }
    $form_data .= '<input class="btn btn-default" type="submit" value="Open teacher view">';
    $form_data .= '</form >';
    return $form_data;
}

function rtcproctorv_student_form($url) {
    $form_data = '<form id="student_form" method="post" style="display: inline-block;" action="'.$url.'" target="_blank" >';
    $form_data .= '<input class="btn btn-default" type="submit" value="Open student live">';
    $form_data .= '</form >';
    return $form_data;
}