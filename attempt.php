<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_smartspe\smartspe_quiz_manager;
use mod_smartspe\output\quiz_page;

global $DB, $USER;

// Params
$id = required_param('id', PARAM_INT); // Course module ID
$memberindex = optional_param('memberindex', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);
$answers = optional_param_array('answers', [], PARAM_RAW);
$questionids = optional_param_array('questionids', [], PARAM_INT);

// Setup course + context
$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$sectionid = $cm->section;
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = \context_module::instance($cm->id);
$smartspe = $DB->get_record('smartspe', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

// Instantiate manager
$quiz_manager = new smartspe_quiz_manager($USER->id, $course->id, $context, $smartspe->id);
$members = $quiz_manager->get_members();
$totalmembers = count($members);

// Validate member index
if ($memberindex >= $totalmembers) 
{
    redirect(new moodle_url('/mod/smartspe/view.php', ['id' => $id, 'done' => 1]));
}

$currentmember = $members[$memberindex];

$quiz_manager->start_attempt_evaluation($currentmember, $questionids);

// Save data if coming from form
if ($action === 'next' || $action === 'submit') 
{
    if (!empty($answers))
        $quiz_manager->process_attempt_evaluation($answers, false);
    else
        $quiz_manager->process_attempt_evaluation(null, false);
}

if ($action === 'next') 
{
    $nextmemberindex = $memberindex + 1;

    redirect(new moodle_url('/mod/smartspe/attempt.php', [
        'id' => $id,
        'memberindex' => $nextmemberindex
    ]));
}

// If last member and submitted → finish
if ($action === 'submit' && $memberindex === $totalmembers - 1) 
{
    if (!$answers)
        $quiz_manager->process_attempt_evaluation($answers, true);
    else
        $quiz_manager->process_attempt_evaluation(null, true);

    redirect(new moodle_url('/mod/smartspe/preview.php', ['id' => $id,]));
}

// Load questions with saved data
$questions = $quiz_manager->get_saved_questions_answers();

// Page setup
$PAGE->set_url('/mod/smartspe/attempt.php', ['id' => $id, 'memberindex' => $memberindex]);
$PAGE->set_title(format_string($smartspe->name));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

// Render the quiz page
$page = new quiz_page($smartspe->id, $currentmember, $questions, $memberindex, $totalmembers);

echo $OUTPUT->header();
echo $OUTPUT->render($page);
echo $OUTPUT->footer();
