<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_smartspe\smartspe_quiz_manager;
use core\exception\moodle_exception;

global $DB, $USER, $PAGE;

// get basic parameters
$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST); 
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = \context_module::instance($cm->id);
$smartspe = $DB->get_record('smartspe', array('id' => $cm->instance), '*', MUST_EXIST);
$instanceid = $smartspe->id;
require_login($course, true, $cm);

// set up the page
$PAGE->set_url('/mod/smartspe/view.php', ['id' => $id]);
$PAGE->set_title(get_string('pluginname', 'mod_smartspe'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

// --- 3. Load activity instance --- REMOVING THIS BECAUSE IT'S A DUPLICATE
//$smartspe = $DB->get_record('smartspe', ['id' => $instanceid], '*', MUST_EXIST);
//$questionids = explode(',', $smartspe->questionids);

// --- 4. Determine user role ---
$is_teacher = has_capability('mod/smartspe:manage', $context);
$is_student = !$is_teacher && has_capability('mod/smartspe:submit', $context);

// get renderer
$output = $PAGE->get_renderer('mod_smartspe');

// output starts here
echo $OUTPUT->header();

$quiz_manager = null;
if ($is_student) 
{
    $quiz_manager = new mod_smartspe\smartspe_quiz_manager($USER->id, $course->id, $context, $instanceid, $cm->id);
    echo $output->render(new \mod_smartspe\output\student_view($quiz_manager));
} 
else if ($is_teacher)
{
    try {
        $quiz_manager = new mod_smartspe\smartspe_quiz_manager($USER->id, $course->id, $context, $instanceid, $cm->id);
    } catch (Exception $e) {
        echo "Quiz manager creation failed: " . $e->getMessage();
        die();
    }
    echo $output->render(new \mod_smartspe\output\teacher_view($quiz_manager));
}

else 
{
    echo $OUTPUT->notification('You do not have permission to view this activity.', 'notifyproblem');
}

echo $OUTPUT->footer();
