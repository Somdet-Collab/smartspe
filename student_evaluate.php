<?php

require_once(__DIR__ . '/../../config.php');

// --- 0. Hide PHP warnings/notices and Moodle debug messages ---
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
@ini_set('display_errors', 0);
// Disable Moodle debugging for this page
if (function_exists('debugging')) {
    debugging('', DEBUG_NONE);
    if (isset($CFG)) {
        $CFG->debug = 0;
        $CFG->debugdisplay = 0;
    }
}

require_once(__DIR__ . '/lib.php');

use core\exception\moodle_exception;
use moodle_url;

global $DB, $USER, $PAGE, $OUTPUT;

// --- 1. Get parameters ---
$cmid = required_param('id', PARAM_INT);
$evaluateeid = optional_param('evaluateeid', 0, PARAM_INT);
$current_evaluateeid = optional_param('current_evaluateeid', 0, PARAM_INT);
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

// --- 6. Determine team member order (self first) ---
$member_ids = $quiz_manager->get_member_ids();
$member_ids = array_map('intval', $member_ids);
$member_ids = array_values(array_unique($member_ids, SORT_NUMERIC));

$current_user_id = (int)$USER->id;

// Ensure self first
$key = array_search($current_user_id, $member_ids, true);
if ($key !== false) {
    unset($member_ids[$key]);
    array_unshift($member_ids, $current_user_id);
    $member_ids = array_values($member_ids);
}

// --- determine displayed evaluateeid ---
if ($evaluateeid == 0 && $current_evaluateeid == 0) {
    $evaluateeid = $current_user_id;
} elseif ($current_evaluateeid) {
    $evaluateeid = $current_evaluateeid;
}

$type = ($evaluateeid === $current_user_id) ? 'self' : 'peer';

// --- 7. Determine current position ---
$current_index = array_search($evaluateeid, $member_ids);
$is_first = ($current_index === 0);
$is_last = ($current_index === count($member_ids) - 1);

$prev_evaluateeid = !$is_first ? $member_ids[$current_index - 1] : null;
$next_evaluateeid = !$is_last ? $member_ids[$current_index + 1] : null;

// --- 8. Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {

    $displayed = $current_evaluateeid ?: $evaluateeid;
    $target = optional_param('evaluateeid', 0, PARAM_INT);

    $questions = $quiz_manager->get_questions($questionids);
    $answers = [];
    $comments = [];

    $require_full = ($action !== 'back');

    foreach ($questions as $idx => $q) {
        $fieldname = 'answer_' . ($idx + 1);
        $qtype = $q['qtype'] ?? 'multichoice';

        if ($qtype === 'multichoice') {
            $ans = optional_param($fieldname, null, PARAM_INT);
            if ($require_full && $ans === null) {
                throw new moodle_exception("Index {$idx}: no answer");
            }
            //answer from db
            $record = $DB->get_record('question_answers', ['id' => $ans]);
            $answer = $record->answer;
            $answer_int = 0;

            if (!is_numeric($answer))
            {
                // Step 1: remove HTML tags
                $clean = strip_tags($answer);

                // Step 2: convert to integer
                $answer_int = (int) $clean;
            }
            else
            {
                $answer_int = $answer;
            }

            $answers[] = $answer_int;
        } elseif ($qtype === 'essay') {
            $ans = optional_param($fieldname, '', PARAM_TEXT);
            $comments[] = trim($ans);
        } else {
            throw new moodle_exception("Unhandled question type: {$qtype}");
        }
    }

    $comment2 = null;
    $comment = null;
    if($type === 'peer') {
        $comment = $comments[0] ?? null;
    } else {
        $comment = $comments[0] ?? null;
        $comment2 = $comments[1] ?? null;
    }

    $attemptid = $quiz_manager->start_attempt_evaluation($displayed, $questionids);
    $quiz_manager->process_attempt_evaluation(
        $answers,
        $comment,
        $comment2,
        ($action === 'submit' || $is_last)
    );

    if ($action === 'next' && $target) {
        redirect(new moodle_url('/mod/smartspe/student_evaluate.php', [
            'id' => $cmid,
            'evaluateeid' => $target
        ]));
    } elseif ($action === 'back' && $target) {
        redirect(new moodle_url('/mod/smartspe/student_evaluate.php', [
            'id' => $cmid,
            'evaluateeid' => $target
        ]));
    } elseif ($action === 'submit' || $is_last) {
        $quiz_manager->quiz_is_submitted();
        redirect(
            new moodle_url('/course/view.php', ['id' => $course->id]),
            get_string('submissionsuccess', 'mod_smartspe'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        redirect(new moodle_url('/mod/smartspe/student_evaluate.php', [
            'id' => $cmid,
            'evaluateeid' => $displayed
        ]));
    }
}

// --- 9. Load saved answers for current member ---
$saved_questions = [];
try {
    if (in_array($evaluateeid, $member_ids, true)) {
        $saved_questions = $quiz_manager->get_saved_questions_answers($evaluateeid);
    }
} catch (\Exception $e) {
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
    $saved_questions,
    $next_evaluateeid,
    $prev_evaluateeid,
    $is_first,
    $is_last
);

echo $output->render($studentview);

echo $OUTPUT->footer();
