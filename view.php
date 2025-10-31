<?php
// view.php - team / peer+self auto-evaluation using CSV comments
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
$instanceid = $cm->instance;
require_login($course, true, $cm);

// --- Get teacher-selected questions from the module instance ---
$smartspe = $DB->get_record('smartspe', ['id' => $instanceid], '*', MUST_EXIST);
$questionids = explode(',', $smartspe->questionids);

// --- Read CSV file (comments and self_comments) ---
// Expect CSV header on first row. Columns assumed: comment, self_comment (self_comment may be empty).
$csvpath = __DIR__ . '/comments.csv';
$peer_comments = [];   // queue for peer comments
$self_comments = [];   // queue for self comments (used only for self evaluations)

if (!file_exists($csvpath)) {
    // If CSV missing, continue but warn
    echo '<div class="alert alert-warning">CSV file not found at ' . s($csvpath) . '. Using generated comments instead.</div>';
} else {
    if (($handle = fopen($csvpath, 'r')) !== false) {
        // Skip header
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            // Normalize row columns: support cases where CSV has 1 or 2 columns
            $comment = isset($row[0]) ? trim($row[0]) : '';
            $self_comment = isset($row[1]) ? trim($row[1]) : '';

            if ($comment !== '') {
                $peer_comments[] = $comment;
            } else {
                // push an empty placeholder so counts remain comparable
                $peer_comments[] = '';
            }

            // If CSV contains a self_comment, enqueue it; else enqueue empty string
            if ($self_comment !== '') {
                $self_comments[] = $self_comment;
            } else {
                $self_comments[] = '';
            }
        }
        fclose($handle);
    }
}

// helper to pop from queue safely (returns '' if empty)
$pop_peer_comment = function() use (&$peer_comments) {
    if (count($peer_comments) === 0) {
        return '';
    }
    return array_shift($peer_comments);
};
$pop_self_comment = function() use (&$self_comments) {
    if (count($self_comments) === 0) {
        return '';
    }
    return array_shift($self_comments);
};

// Debug print counts
echo '<pre>CSV loaded: peer_comments=' . count($peer_comments) . ', self_comments=' . count($self_comments) . '</pre>';

// --- Step A: Get all groups (teams) in the course ---
$groups = groups_get_all_groups($course->id); // returns array of group objects keyed by id

if (empty($groups)) {
    echo '<div class="alert alert-info">No groups (teams) found in this course.</div>';
} else {
    echo '<h3>Found ' . count($groups) . ' teams</h3>';
}

// --- Outer loop: for each team ---
foreach ($groups as $group) {
    echo '<hr><h4>Team: ' . format_string($group->name) . ' (id=' . $group->id . ')</h4>';

    // Get members of the group
    // groups_get_members returns array of user objects keyed by id (Moodle core function)
    $members = groups_get_members($group->id);

    if (empty($members)) {
        echo '<div class="alert alert-warning">No members in team ' . s($group->name) . '</div>';
        continue;
    }

    // make an indexed array of member IDs for stable ordering
    $memberids = array_keys($members);

    echo '<p>Members: ';
    $names = [];
    foreach ($members as $m) {
        $names[] = fullname($m) . ' (id=' . $m->id . ')';
    }
    echo implode(', ', $names) . '</p>';

    // For each *evaluator* in this team (each member evaluates every member, including self)
    foreach ($memberids as $evaluatorid) {
        // instantiate quiz_manager for this evaluator (so user context inside manager is evaluator)
        $quiz_manager = new smartspe_quiz_manager($evaluatorid, $cm->course, $context, $instanceid);

        echo '<div style="margin-left:10px;"><strong>Evaluator:</strong> ' . fullname($members[$evaluatorid]) . ' (id=' . $evaluatorid . ")</div>\n";

        // For each *evaluated member* in the same team
        foreach ($memberids as $memberid) {
            // Start attempt for this evaluated member
            try {
                $attemptid = $quiz_manager->start_attempt_evaluation($memberid, $questionids);
                echo '<div style="margin-left:20px;">Started attempt for evaluatee id=' . $memberid . ' (attemptid=' . $attemptid . ")</div>\n";
            } catch (moodle_exception $e) {
                echo '<div class="alert alert-danger" style="margin-left:20px;">Failed to start attempt for member ' . $memberid . ': ' . s($e->getMessage()) . "</div>\n";
                continue;
            }

            // Prepare answers: simulate MCQ five numbers (1..5)
            $answers = [];
            // We'll fetch questions list from manager (assumes it returns structure same as your sample)
            $questions = $quiz_manager->get_questions($questionids);
            $mcq_count = 0;
            $comment = null;
            $self_comment = null;

            // iterate through questions and fill answers/comment/self_comment
            foreach ($questions as $question) {
                if ($question['qtype'] === 'multichoice' && $mcq_count < 5) {
                    // random scale 1..5
                    $answers[$mcq_count] = rand(1, 5);
                    $mcq_count++;
                } elseif ($question['qtype'] === 'essay') {
                    // Decide comment vs self_comment
                    if ($evaluatorid == $memberid) {
                        // Self-evaluation: pull a self_comment entry
                        $self_comment = $pop_self_comment();
                        if ($self_comment === '') {
                            // fallback to a generated text if CSV empty
                            $self_comment = "Self-evaluation comment for user $memberid (generated)";
                        }
                        // Also assign a peer-like comment for the essay field (optional) or keep null
                        $comment = $self_comment;
                    } else {
                        // Peer evaluation: pull peer comment queue
                        $comment = $pop_peer_comment();
                        if ($comment === '') {
                            $comment = "Peer comment by evaluator $evaluatorid for member $memberid (generated)";
                        }
                        // self_comment remains null for peer eval
                        $self_comment = null;
                    }
                } else {
                    // other types: ignore or break
                }
            } // end questions loop

            // Display prepared data for debugging
            echo '<div style="margin-left:30px;"><pre>';
            echo "Evaluating member id=$memberid by evaluator id=$evaluatorid\n";
            echo "Answers: ";
            print_r($answers);
            echo "Comment: " . s($comment) . "\n";
            echo "Self Comment: " . s($self_comment) . "\n";
            echo '</pre></div>';

            // Autosave (process_attempt_evaluation with $submit=false)
            try {
                $quiz_manager->process_attempt_evaluation($answers, $comment, $self_comment, false);
                echo '<div style="margin-left:30px;">Autosaved evaluation (evaluator=' . $evaluatorid . ', evaluatee=' . $memberid . ")</div>\n";
            } catch (moodle_exception $e) {
                echo '<div class="alert alert-danger" style="margin-left:30px;">Autosave failed for evaluator=' . $evaluatorid . ', evaluatee=' . $memberid . ' : ' . s($e->getMessage()) . "</div>\n";
            }

            // Optionally modify answers (simulate change) before final submit
            foreach ($answers as $k => $v) {
                $answers[$k] = rand(1, 5);
            }

            // Final submit
            try {
                $quiz_manager->process_attempt_evaluation($answers, $comment, $self_comment, true);
                echo '<div style="margin-left:30px;">Submitted evaluation (evaluator=' . $evaluatorid . ', evaluatee=' . $memberid . ")</div>\n";
            } catch (moodle_exception $e) {
                echo '<div class="alert alert-danger" style="margin-left:30px;">Submit failed for evaluator=' . $evaluatorid . ', evaluatee=' . $memberid . ' : ' . s($e->getMessage()) . "</div>\n";
            }

        } // end each evaluated member

        // Optionally check quiz_is_submitted or other finalization per evaluator
        try {
            $submitted = $quiz_manager->quiz_is_submitted();
            echo '<div style="margin-left:20px;">Quiz submitted? ' . ($submitted ? 'Yes' : 'No') . "</div>\n";
        } catch (Exception $e) {
            // ignore if method absent
        }

    } // end each evaluator

} // end each group

// --- Download buttons (kept from your original) ---
?>
<hr>
<h3>Download Test</h3>

<form method="get" action="">
    <input type="hidden" name="id" value="<?php echo $cm->id; ?>">
    <input type="hidden" name="extension" value="csv">

    <!-- Button for detailed report -->
    <button type="submit" name="download_csv" value="details" class="btn btn-primary">
        Download CSV (Details)
    </button>

    <!-- Button for summary report -->
    <button type="submit" name="download_csv" value="summary" class="btn btn-secondary">
        Download CSV (Summary)
    </button>
</form>

<?php
// Check if download button clicked
$download_type = optional_param('download_csv', '', PARAM_ALPHANUM);

if ($download_type) {
    $extension = required_param('extension', PARAM_ALPHA);

    try {
        if ($download_type === 'details') {
            $quiz_manager->download_report_details($extension);
        } else if ($download_type === 'summary') {
            $quiz_manager->download_report_summary($extension);
        }
    } catch (moodle_exception $e) {
        echo '<div class="alert alert-danger">Download error: ' . $e->getMessage() . '</div>';
    }
}
?>
