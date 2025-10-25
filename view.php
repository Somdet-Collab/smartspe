<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
//require_once($CFG->dirroot . '/mod/smartspe/mod_smartspe_mod_form.php');
// commented the above line out, doesn't seem to be required

use mod_smartspe\smartspe_quiz_manager;
use core\exception\moodle_exception;

global $DB, $USER;

$id = required_param('id', PARAM_INT); // Course module ID
$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$sectionid = $cm->section;
$courseid = $cm->course;
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = \context_module::instance($cm->id);
$instance = $DB->get_record('smartspe', ['course' => $course->id], '*', MUST_EXIST);
$instanceid = $instance->id;
// fixed the two lines above -- 16 & 17, because there seemed to be a bug

// Security and access check
require_login($course, true, $cm);

if (!$instanceid) {
    die("smartspeid is required. Example: view.php?smartspeid=1");
}

// --- Get teacher-selected questions from the module instance ---
$smartspe = $DB->get_record('smartspe', ['id' => $instanceid], '*', MUST_EXIST);
$questionids = explode(',', $smartspe->questionids);

//Create attempt
//$attemptid = $quiz_manager->start_attempt_evaluation($data, $teacher_selected_questionids); // changed this function to align with the one from quiz_manager.php -- commenting this out because i don't think we have to create it here
$quiz_manager = new smartspe_quiz_manager($USER->id, $courseid, $context, $instanceid);

// --- Step 1: Get members of team ---
try {
    //Get member ids
    $members = $quiz_manager->get_members();
} catch (moodle_exception $e) {
    die("Error getting members: " . $e->getMessage());
}

// --- Step 2: For each member, start attempt and submit ---
foreach ($members as $memberid) 
{
    // --- Step 2a: Start attempt with teacher-selected question IDs ---
    try {
        $attemptid = $quiz_manager->start_attempt_evaluation($memberid, $questionids);
        echo "Attempt created for member $memberid: Attempt ID $attemptid<br>";
    } catch (moodle_exception $e) {
        echo "Failed to start attempt for member $memberid: " . $e->getMessage() . "<br>";
        continue;
    }

    // --- Step 2b: Prepare fake answers --
    $answers = [];
    $mcq_count = 0;
    $comment_count = 0;

    $questions = $quiz_manager->get_questions($questionids);
    $member = $DB->get_record('user', ['id' => $memberid]);
    $member_name = $member->firstname;

    if (!$questions || !$questions['qtype'])
    {
        echo "Question is empty (view.php) <br>";
        break;
    }

    $comment = null;

    foreach ($questions as $question) 
    {
        $qtext = $question['text'];
        echo "Question for $member_name: $qtext <br>";
        if ($question['qtype'] === 'multichoice' && $mcq_count < 5) 
        {
            $answers[$mcq_count] = rand(1, 3); // simulate MCQ answer
            $current_answer = $answers[$mcq_count];
            echo "Answer: $current_answer <br>";
            $mcq_count++;
        } 
        elseif ($question['qtype'] === 'essay' && $comment_count < 1) 
        {
            $comment = "Peer comment for member $memberid";
            echo "Comment: $comment <br>";
            $comment_count++;
        }
        else
        {
            echo "There is no match type ($qtext) <br>";
            break;
        }
    }
    
    if ($USER->id == $memberid)
    {
        $self_comment = "My self comment";
        echo "Self Comment: $self_comment <br>";
    }
    else
        $self_comment = null;

    // --- Step 2c: Autosave ---
    try {
        $quiz_manager->process_attempt_evaluation($answers, $comment, $self_comment, false);
        echo "Autosaved answers for member $memberid<br>";
    } catch (moodle_exception $e) {
        echo "Failed autosave for member $memberid: " . $e->getMessage() . "<br>";
    }

    // --- Step 2d: Submit ---
    try {
        $quiz_manager->process_attempt_evaluation($answers, $comment, $self_comment, true);
    } catch (moodle_exception $e) {
        echo "Submission error for member $memberid: " . $e->getMessage() . "<br>";
    }
}

//Final Submit
$submitted = $quiz_manager->quiz_is_submitted();
echo $submitted ? "Submitted evaluation<br>" : "Failed submission";

echo "<hr>Test completed.";
