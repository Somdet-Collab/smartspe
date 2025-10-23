<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
//require_once($CFG->dirroot . '/mod/smartspe/mod_smartspe_mod_form.php');
// commented the above line out, doesn't seem to be required

use mod_smartspe\smartspe_quiz_manager;

global $DB, $USER;

$id = required_param('id', PARAM_INT); // Course module ID
$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$sectionid = $cm->section;
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = \context_module::instance($cm->id);
$instance = $DB->get_record('smartspe', ['course' => $course->id], '*', MUST_EXIST);
$instanceid = $instance->id;
// fixed the two lines above -- 16 & 17, because there seemed to be a bug

// Security and access check
require_login($course, true, $cm);

// Page setup
$PAGE->set_url('/mod/smartspe/view.php', ['id' => $id]);
$PAGE->set_title(format_string('Smart Self & Peer Evaluation'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

//Create form for specific smartspe instance
$mform = new mod_smartspe_mod_form($instanceid, $sectionid, $cm, $course);
$data = $mform->get_data();

$quiz_manager = new smartspe_quiz_manager($USER->id, $course->id, $context, $instanceid);

//Create attempt
$attemptid = $quiz_manager->start_attempt_evaluation($data); // changed this function to align with the one from quiz_manager.php

$questions = $quiz_manager->get_questions($data); //Load questions
$members = $quiz_manager->get_members(); //Load members

// Get renderer
$output = $PAGE->get_renderer('mod_smartspe');

// Render page content
echo $output->header();
echo $output->render_mainpage(); // Custom renderer method (in classes/output/main.php)
echo $output->footer();
