<?php
namespace mod_smartspe\output;

use renderable;
use templatable;
use renderer_base;
use mod_smartspe\smartspe_quiz_manager;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class student_view implements renderable, templatable 
{
    protected $quiz_manager;

    public function __construct(smartspe_quiz_manager $quiz_manager) 
    {
        $this->quiz_manager = $quiz_manager;
    }

    public function export_for_template(renderer_base $output) 
    {
        global $DB;

        $data = new stdClass();
        $smartspeid = $this->quiz_manager->get_smartspeid();

        // --- Fetch all question IDs ---
        $smartspe_record = $DB->get_record('smartspe', ['id' => $smartspeid], 'questionids');
        $questionids = !empty($smartspe_record->questionids) 
            ? array_map('intval', explode(',', $smartspe_record->questionids)) 
            : [];

        // --- Fetch questions from DB ---
        $questions = [];
        if (!empty($questionids)) {
            list($insql, $params) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);
            $sql = "SELECT q.id, q.name, q.questiontext, q.qtype
                    FROM {question} q
                    WHERE id $insql
                    ORDER BY FIELD(id, " . implode(',', $questionids) . ")";
            $questions = $DB->get_records_sql($sql, $params);
        }

        // --- Format questions with options ---
        $formatted_questions = [];
        foreach ($questions as $q) {
            $q = (array)$q;

            $options = [];
            if (in_array($q['qtype'], ['multichoice', 'truefalse'])) {
                $answers = $DB->get_records('question_answers', ['question' => $q['id']], 'id ASC');
                foreach ($answers as $ans) {
                    $options[] = [
                        'id' => $ans->id,
                        'answer' => strip_tags($ans->answer)
                    ];
                }
            }

            $formatted_questions[] = [
                'id' => $q['id'],
                'name' => $q['name'],
                'text' => strip_tags($q['questiontext']),
                'options' => $options
            ];
        }

        // --- Get members ---
        $members = [];
        try {
            $rawmembers = $this->quiz_manager->get_members(); // full user objects
            foreach ($rawmembers as $user) {
                $members[] = (object)[
                    'id' => $user->id,
                    'membername' => fullname($user) // use Moodle fullname()
                ];
            }
        } catch (\moodle_exception $e) {
            $members = [];
        }

        // --- Build evaluations array ---
        $evaluations = [];

        // Self evaluation
        $evaluations[] = [
            'type' => 'self',
            'title' => 'Self Evaluation',
            'questions' => $formatted_questions,
            'is_self' => true,
            'is_peer' => false,
            'membername' => null,
            'memberid' => null
        ];

        // Peer evaluation
        foreach ($members as $member) {
            $evaluations[] = [
                'type' => 'peer',
                'title' => "Evaluation for {$member->membername}",
                'questions' => $formatted_questions,
                'is_self' => false,
                'is_peer' => true,
                'membername' => $member->membername,
                'memberid' => $member->id
            ];
        }

        $data->evaluations = $evaluations;
        $data->activityname = "Smart Self & Peer Evaluation";
        $data->description = "Evaluate yourself first, then your groupmates based on contribution and teamwork.";
        $data->formaction = new \moodle_url('/mod/smartspe/submit_eval.php', ['id' => $this->quiz_manager->get_cmid()]);
        $data->sesskey = sesskey();

        return $data;
    }
}
