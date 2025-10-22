<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_smartspe\smartspe_quiz_manager;
use core\exception\moodle_exception;

global $DB, $USER;

$id = required_param('id', PARAM_INT); // Course module ID
$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$sectionid = $cm->section;
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = \context_module::instance($cm->id);
$instanceid = $DB->get_record('smartspe', ['course' => $course], 'id');

// Security and access check
require_login($course, true, $cm);

if (!$smartspeid) {
    die("smartspeid is required. Example: view.php?smartspeid=1");
}

// --- Get teacher-selected questions from the module instance ---
$smartspe = $DB->get_record('smartspe', ['id' => $smartspeid], '*', MUST_EXIST);

// `questionids` field stores selected question IDs (assuming serialized or comma-separated)
$teacher_selected_questionids = explode(',', $smartspe->questionids);
if (empty($teacher_selected_questionids)) {
    die("No questions selected for this SmartSpe activity.");
}

$quiz_manager = new smartspe_quiz_manager($USER->id, $id, $context, $smartspeid);

// --- Step 1: Get members of team ---
try {
    $members = $quiz_manager->get_members();
} catch (moodle_exception $e) {
    die("Error getting members: " . $e->getMessage());
}

// --- Step 2: For each member, start attempt and submit ---
foreach ($members as $memberid) 
{
    $memberid;

    // --- Step 2a: Start attempt with teacher-selected question IDs ---
    try {
        $attemptid = $quiz_manager->start_attempt_evaluation($memberid, $teacher_selected_questionids);
        echo "Attempt created for member $memberid: Attempt ID $attemptid<br>";
    } catch (moodle_exception $e) {
        echo "Failed to start attempt for member $memberid: " . $e->getMessage() . "<br>";
        continue;
    }

    // --- Step 2b: Prepare fake answers --
    $answers = [];
    $mcq_count = 0;
    $comment_count = 0;

    foreach ($teacher_selected_questionids as $qid) {
        // Check question type from DB
        $question = $DB->get_record('question', ['id' => $qid], 'id, qtype', MUST_EXIST);
        if ($question->qtype === 'multichoice' && $mcq_count < 5) {
            $answers[$qid] = rand(1, 4); // simulate MCQ answer
            $mcq_count++;
        } elseif ($question->qtype === 'comment' && $comment_count < 1) {
            $answers[$qid] = "This is a comment for member $memberid";
            $comment_count++;
        }
    }

    $comment = "Peer comment for member $memberid";
    $self_comment = "My self comment";

    // --- Step 2c: Autosave ---
    try {
        $quiz_manager->process_attempt_evaluation($answers, false);
        echo "Autosaved answers for member $memberid<br>";
    } catch (moodle_exception $e) {
        echo "Failed autosave for member $memberid: " . $e->getMessage() . "<br>";
    }

    // --- Step 2d: Submit ---
    try {
        $submitted = $quiz_manager->quiz_is_submitted($answers, $comment, $self_comment, $memberid);
        echo $submitted ? "Submitted evaluation for member $memberid<br>" : "Failed submission for member $memberid<br>";
    } catch (moodle_exception $e) {
        echo "Submission error for member $memberid: " . $e->getMessage() . "<br>";
    }
}

echo "<hr>Test completed.";
