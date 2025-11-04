<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_smartspe\event\attempt_start;

global $DB, $USER, $PAGE, $OUTPUT;

// --- 1. Get parameters ---
$cmid = required_param('id', PARAM_INT);
$evaluateeid = optional_param('evaluateeid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

// --- 2. Load course module ---
$cm = get_coursemodule_from_id('smartspe', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$smartspe = $DB->get_record('smartspe', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/smartspe:submit', $context);

// --- 3. Setup page ---
$PAGE->set_url('/mod/smartspe/student_evaluate.php', ['id' => $cmid]);
$PAGE->set_title('Self & Peer Evaluation');
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

// --- 4. Create quiz manager ---
$quiz_manager = new \mod_smartspe\smartspe_quiz_manager(
    $USER->id, 
    $course->id, 
    $context, 
    $smartspe->id, 
    $cmid
);

// --- 5. Get question IDs ---
$questionids = !empty($smartspe->questionids) 
    ? array_map('intval', explode(',', trim($smartspe->questionids))) 
    : [];

if (empty($questionids)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification('No questions have been set up for this evaluation.', 'notifyproblem');
    echo $OUTPUT->footer();
    die();
}

// --- 6. Get team members ---
$members = $quiz_manager->get_members();
if (empty($members)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification('You must be in a group to complete this evaluation.', 'notifyproblem');
    echo $OUTPUT->footer();
    die();
}

// --- 7. Determine evaluation sequence: self first ---
$member_ids = $quiz_manager->get_member_ids();
$current_user_id = $USER->id;

// Reorder: self first
if (($key = array_search($current_user_id, $member_ids)) !== false) {
    unset($member_ids[$key]);
    array_unshift($member_ids, $current_user_id);
    $member_ids = array_values($member_ids); // reindex keys 0,1,2...
}

// If no evaluateeid, start with self
if ($evaluateeid == 0) {
    $evaluateeid = $USER->id;
}
$type = ($evaluateeid == $USER->id) ? 'self' : 'peer';

// --- 8. Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {

    // Collect answers
    $answers = [];
    $comment = '';
    
    foreach ($questionids as $idx => $qid) {
        $answer_key = 'answer_' . ($idx + 1);
        $answer = optional_param($answer_key, '', PARAM_RAW);
        
        if (!$answer)
            throw new moodle_exception("Index $idx: no answer");
        
        if (strlen($answer) > 3 && strpos($answer, ' ') !== false) {
            $comment = $answer;
        } else {
            $answers[] = intval($answer);
        }
    }

    if (!$answers)
        throw new moodle_exception("Answers before autosaving is empty");

    // Start attempt for this evaluatee
    $attemptid = $quiz_manager->start_attempt_evaluation($evaluateeid, $questionids);
    
    // Determine if this is the last evaluation
    if (!$attemptid) {
        throw new moodle_exception("Attemptid invalid: {$attemptid}");
    }

    // Determine current index & last member
    $current_index = array_search($evaluateeid, $member_ids);
    $is_last = ($current_index === count($member_ids) - 1);
    
    if ($action === 'next' || $is_last) 
    {
        // Save with finish = false (moving to next)
        $self_comment = ($type === 'self') ? $comment : null;
        $peer_comment = ($type === 'peer') ? $comment : null;
        
        $quiz_manager->process_attempt_evaluation(
            $answers, 
            $peer_comment, 
            $self_comment, 
            false  // Not finished yet
        );
        
        if ($is_last) 
        {
            //Autosave before submitting
            //And change state to finished
            $quiz_manager->process_attempt_evaluation(
            $answers, 
            $peer_comment, 
            $self_comment, 
            true  // finished
            );

            // This was the last person - now call final submission
            $quiz_manager->quiz_is_submitted();
            
            redirect(
                new moodle_url('/course/view.php', ['id' => $course->id]),
                get_string('submissionsuccess', 'mod_smartspe'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );

        } 
        else 
        {
            // Move to next person
            $next_index = $current_index + 1;
            $next_evaluateeid = $member_ids[$next_index];
            
            redirect(new moodle_url('/mod/smartspe/student_evaluate.php', [
                'id' => $cmid,
                'evaluateeid' => $next_evaluateeid
            ]));
        }

    } 

    // Save attempt
    $self_comment = ($type === 'self') ? $comment : null;
    $peer_comment = ($type === 'peer') ? $comment : null;

    $quiz_manager->process_attempt_evaluation(
        $answers,
        $peer_comment,
        $self_comment,
        $is_last
    );

    // Redirect to next evaluation or finish
    if (!$is_last) {
        $next_index = $current_index + 1;
        $next_evaluateeid = $member_ids[$next_index];
        redirect(new moodle_url('/mod/smartspe/student_evaluate.php', [
            'id' => $cmid,
            'evaluateeid' => $next_evaluateeid
        ]));
    } else {
        // Last member: finalize quiz
        $quiz_manager->quiz_is_submitted();
        redirect(
            new moodle_url('/course/view.php', ['id' => $course->id]),
            get_string('submissionsuccess', 'mod_smartspe'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

// --- 9. Load saved answers ---
$saved_questions = [];
try {
    $members = $quiz_manager->get_members();
    $member_ids_temp = array_column($members, 'id');
    
    if (in_array($evaluateeid, $member_ids_temp)) {
        $saved_questions = [];
    }
} catch (Exception $e) {
    $saved_questions = [];
}

// --- 10. Render page ---
echo $OUTPUT->header();

$output = $PAGE->get_renderer('mod_smartspe');
$studentview = new \mod_smartspe\output\student_view(
    $quiz_manager,
    $evaluateeid,
    $type,
    $questionids,
    $saved_questions
);
echo $output->render($studentview);

echo $OUTPUT->footer();
