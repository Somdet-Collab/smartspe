<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_smartspe\smartspe_quiz_manager;

global $DB, $USER;

$id = required_param('id', PARAM_INT); // Course module ID
$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = \context_module::instance($cm->id);
$instanceid = $DB->get_record('smartspe', ['course' => $course], 'id');

$memberindex = optional_param('memberindex', 0, PARAM_INT);
$action = required_param('action', PARAM_TEXT);
$userid = $USER->id;

$quiz_manager = new smartspe_quiz_manager($USER->id, $course->id, $context, $instanceid);
$members = $quiz_manager->get_members();
$total_member = count($members);
$current_member = $members[$memberindex];

// Collect answers if any
$answers = optional_param_array('answers', [], PARAM_RAW);

switch ($action) 
{
    case 'next':
        // Save current member's answers
        $quiz_manager->process_attempt_evaluation(null,false);

        // Redirect to next member
        $memberindex++;
        redirect(new moodle_url('/mod/smartspe/view.php', ['id'=>$smartspeid, 'member'=>$memberindex]));
        break;

    case 'preview':
        // Save last member answers
        $quiz_manager->quiz_is_submitted($answers, '', '', $current_member->id);

        // Collect all answers for preview (fetch from database or data_persistence)
        $questions = $quiz_manager->get_saved_questions_answers();
        $all_answers = $questions['current_answer'];

        $page = new \mod_smartspe\output\preview_page($all_answers, $members);
        echo $OUTPUT->header();
        echo $OUTPUT->render($page);
        echo $OUTPUT->footer();
        break;

    case 'submit_final':
        // Final submission
        $quiz_manager->process_attempt_evaluation(null, true); // Mark attempt finished
        $quiz_manager->quiz_is_submitted($answers, $comment, $self_comment, $member[$memberindex]);
        
        redirect(new moodle_url('/mod/smartspe/view.php', ['id'=>$smartspeid, 'submitted'=>1]));
        break;

    default:
        throw new moodle_exception('Invalid action.');
}
