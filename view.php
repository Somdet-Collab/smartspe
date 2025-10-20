<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT); // Course module ID
$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

// Security and access check
require_login($course, true, $cm);

// Page setup
$PAGE->set_url('/mod/smartspe/view.php', ['id' => $id]);
$PAGE->set_title(format_string('Smart Self & Peer Evaluation'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

// Get renderer
$output = $PAGE->get_renderer('mod_smartspe');

// Render page content
echo $output->header();
echo $output->render_mainpage(); // Custom renderer method (in classes/output/main.php)
echo $output->footer();
