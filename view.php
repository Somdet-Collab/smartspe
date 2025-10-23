<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_smartspe\smartspe_quiz_manager;
use core\exception\moodle_exception;

global $DB, $USER, $PAGE;

// --- 1. Get basic parameters ---
$id = required_param('id', PARAM_INT); // Course module ID

$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = \context_module::instance($cm->id);
require_login($course, true, $cm);

// --- 2. Set up the page ---
$PAGE->set_url('/mod/smartspe/view.php', ['id' => $id]);
$PAGE->set_title(get_string('pluginname', 'mod_smartspe'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

// --- 3. Load activity instance ---
$smartspe = $DB->get_record('smartspe', ['id' => $cm->instance], '*', MUST_EXIST);
$instanceid = $smartspe->id;

// --- 4. Create the quiz manager ---
$quiz_manager = new smartspe_quiz_manager($USER->id, $course->id, $context, $instanceid);

// --- 5. Determine user role ---
$is_teacher = has_capability('mod/smartspe:grade', $context);

// --- 6. Get renderer ---
$output = $PAGE->get_renderer('mod_smartspe');

// --- 7️. Handle UI rendering ---
echo $OUTPUT->header();

if ($is_teacher) {
    // TEACHER VIEW
    echo $output->render(new \mod_smartspe\output\teacher_view($quiz_manager));
} else {
    // STUDENT VIEW
    echo $output->render(new \mod_smartspe\output\student_view($quiz_manager));
}

echo $OUTPUT->footer();
