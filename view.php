<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_smartspe\smartspe_quiz_manager;
use core\exception\moodle_exception;

global $DB, $USER;

// --- 1. Get basic parameters ---
$id = required_param('id', PARAM_INT); // Course module ID

$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = \context_module::instance($cm->id);
$instance = $DB->get_record('smartspe', ['course' => $course->id], '*', MUST_EXIST);
$instanceid = $instance->id;
require_login($course, true, $cm);

// --- Get teacher-selected questions from the module instance ---
$smartspe = $DB->get_record('smartspe', ['id' => $instanceid], '*', MUST_EXIST);
$questionids = explode(',', $smartspe->questionids);

//Create attempt
//$attemptid = $quiz_manager->start_attempt_evaluation($data, $teacher_selected_questionids); // changed this function to align with the one from quiz_manager.php -- commenting this out because i don't think we have to create it here
$quiz_manager = new smartspe_quiz_manager($USER->id, $cm->course, $context, $instanceid);

// --- 4. Determine user role ---
$is_teacher = has_capability('mod/smartspe:manage', $context);
$is_student = !$is_teacher && has_capability('mod/smartspe:submit', $context);

    foreach($questions as $question)
    {
        $qtext = $question['text'];
        $qtype = $question['qtype'];
        echo "Question for $member_name: $qtext <br>";
        if ($question['qtype'] === 'multichoice' && $mcq_count < 5) 
        {
            $answers[$mcq_count] = rand(1, 5); // simulate MCQ answer
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
            echo "There is no match type ($qtype) <br>";
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

// output starts here
echo $OUTPUT->header();

$quiz_manager = null;
if ($is_student) 
{
    $quiz_manager = new \mod_smartspe\smartspe_quiz_manager($USER->id, $course->id, $context, $instanceid, $cm->id);
    echo $output->render(new \mod_smartspe\output\student_view($quiz_manager));
} 
else if ($is_teacher)
{
    try {
        $quiz_manager = new \mod_smartspe\smartspe_quiz_manager(
            $USER->id, $course->id, $context, $instanceid, $cm->id
        );
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

<form method="get" action="">
    <input type="hidden" name="id" value="<?php echo $cm->id; ?>">
    <input type="hidden" name="extension" value="csv">
    <button type="submit" name="download_csv" value="1" class="btn btn-primary">Download CSV</button>

</form>

<?php
// Check if download button clicked
if (optional_param('download_csv', 0, PARAM_INT)) {
    $extension = required_param('extension', PARAM_ALPHA);
    
    try {
        $quiz_manager->download_report($extension);
    } catch (moodle_exception $e) {
        echo '<div class="alert alert-danger">Download error: ' . $e->getMessage() . '</div>';
    }
}
?>
