<?php
namespace mod_smartspe\output;

use renderable;
use templatable;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

class student_view implements renderable, templatable {
    protected $quiz_manager;
    protected $evaluateeid;  // current user (self) or peer
    protected $type;          // 'self' or 'peer'
    protected $questionids;

    public function __construct($quiz_manager, $evaluationid, $type, $questionids) {
        $this->quiz_manager = $quiz_manager;
        $this->evaluateeid = $evaluationid;
        $this->type = $type;
        $this->questionids = $questionids;
    }

    public function export_for_template(renderer_base $output) {
        global $USER;

        // Fetch questions from quiz manager
        $questionsraw = $this->quiz_manager->get_questions($this->questionids);
        $questions_usage = $this->quiz_manager->get_saved_questions_answers();

        // Convert usage to associative
        $usage_by_id = [];
        foreach ($questions_usage as $u) {
            $usage_by_id[$u['id']] = $u;
        }

        $questionsdata = [];
        $displayNumber = 1;

        foreach ($questionsraw as $q) {
            $qid = $q['id'];
            $u = $usage_by_id[$qid] ?? [];

            $qtype = $q['qtype'] ?? 'essay';
            $currentAnswer = $u['current_answer'] ?? null;

            // Prepare MCQ options
            $options = [];
            if ($qtype === 'multichoice' && !empty($q['options'])) {
                foreach ($q['options'] as $opt) {
                    $options[] = [
                        'value'   => $opt['value'],
                        'text'    => $opt['text'],
                        'checked' => ($currentAnswer !== null && $currentAnswer == $opt['value'])
                    ];
                }
            }

            $questionsdata[] = [
                'id'             => $qid,
                'display_number' => $displayNumber++,
                'text'           => $q['text'],
                'qtype'          => $qtype,
                'qtype_essay'    => ($qtype === 'essay'),
                'options'        => $options,
                'current_answer' => $currentAnswer ?? ''
            ];
        }

        // Determine next peer for sequential peer evaluation
        $nextpeerid = null;
        if ($this->type === 'peer') {
            $members = $this->quiz_manager->get_members();
            $ids = array_column($members, 'id');
            $currentIndex = array_search($this->evaluateeid, $ids);
            $nextpeerid = $members[$currentIndex + 1]->id ?? null;
        }

        return [
            'fullname'    => fullname($USER),
            'questions'   => $questionsdata,
            'evaluateeid' => $this->evaluateeid,
            'nextpeerid'  => $nextpeerid,
            'type'        => $this->type,
            'is_peer'     => ($this->type === 'peer')
        ];
    }
}
