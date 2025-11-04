<?php
namespace mod_smartspe\output;

use renderable;
use templatable;
use renderer_base;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class student_view implements renderable, templatable {
    protected $quiz_manager;
    protected $evaluateeid;
    protected $type;
    protected $questionids;
    protected $saved_questions;

    // Navigation-related props
    protected $next_evaluateeid;
    protected $prev_evaluateeid;
    protected $is_first;
    protected $is_last;
    protected $has_next;
    protected $has_prev;

    public function __construct(
        $quiz_manager,
        $evaluationid,
        $type,
        $questionids,
        $saved_questions = [],
        $next_evaluateeid = null,
        $prev_evaluateeid = null,
        $is_first = false,
        $is_last = false
    ) {
        $this->quiz_manager = $quiz_manager;
        $this->evaluateeid = $evaluationid;
        $this->type = $type;
        $this->questionids = $questionids;
        $this->saved_questions = $saved_questions;

        // Assign navigation flags
        $this->next_evaluateeid = $next_evaluateeid;
        $this->prev_evaluateeid = $prev_evaluateeid;
        $this->is_first = $is_first;
        $this->is_last = $is_last;
        $this->has_next = !$is_last;
        $this->has_prev = !$is_first;
    }

    public function export_for_template(renderer_base $output) {
        global $USER;

        $data = new stdClass();

        // Fetch questions
        $questionsraw = $this->quiz_manager->get_questions($this->questionids);
        $questionsdata = [];
        $displayNumber = 1;

        foreach ($questionsraw as $q) {
            // --- ensure id and qtype are safe scalars ---
            $qid = isset($q['id']) ? $q['id'] : '';
            $qtype = isset($q['qtype']) ? $q['qtype'] : 'multichoice';
            if (!is_scalar($qtype)) {
                if (is_object($qtype) && isset($qtype->name)) {
                    $qtype = (string)$qtype->name;
                } else {
                    $qtype = (string)@json_encode($qtype);
                }
            } else {
                $qtype = (string)$qtype;
            }

            // --- normalize question text to string ---
            $qtext = $q['text'] ?? '';
            if (!is_scalar($qtext)) {
                if (is_array($qtext) && isset($qtext['text'])) {
                    $qtext = (string)$qtext['text'];
                } elseif (is_object($qtext) && property_exists($qtext, 'text')) {
                    $qtext = (string)$qtext->text;
                } else {
                    $qtext = (string)@json_encode($qtext);
                }
            } else {
                $qtext = (string)$qtext;
            }

            // --- find saved current answer for this question (if any) ---
            $currentAnswer = null;
            if (!empty($this->saved_questions)) {
                foreach ($this->saved_questions as $saved) {
                    if ((isset($saved['id']) && $saved['id'] == $qid) || (isset($saved->id) && $saved->id == $qid)) {
                        $currentAnswer = $saved['current_answer'] ?? null;
                        break;
                    }
                }
            }

            // --- normalize currentAnswer to scalar ---
            if (is_array($currentAnswer)) {
                // common saved shapes: ['comment'=>..,'self_comment'=>..] or option arrays
                if (isset($currentAnswer['comment'])) {
                    $currentAnswer = (string)$currentAnswer['comment'];
                } elseif (isset($currentAnswer['self_comment'])) {
                    $currentAnswer = (string)$currentAnswer['self_comment'];
                } else {
                    // pick first scalar value if present
                    $vals = array_values($currentAnswer);
                    $first = reset($vals);
                    $currentAnswer = is_scalar($first) ? $first : (string)@json_encode($first);
                }
            } elseif (is_object($currentAnswer)) {
                $currentAnswer = method_exists($currentAnswer, '__toString')
                    ? (string)$currentAnswer
                    : (string)@json_encode($currentAnswer);
            } else {
                $currentAnswer = $currentAnswer === null ? '' : $currentAnswer;
            }

            // --- type-specific normalization ---
            if ($qtype === 'multichoice') {
                // ensure option value comparison uses integers where possible
                $currentAnswer = is_numeric($currentAnswer) ? (int)$currentAnswer : '';
            } else { // essay or other
                $currentAnswer = (string)$currentAnswer;
            }

            // --- normalize options to scalars ---
            $options = [];
            if (!empty($q['options']) && is_array($q['options'])) {
                foreach ($q['options'] as $opt) {
                    $optvalue = $opt['value'] ?? '';
                    $opttext = $opt['text'] ?? '';

                    if (!is_scalar($optvalue)) {
                        $optvalue = is_scalar($optvalue) ? $optvalue : (string)@json_encode($optvalue);
                    }
                    if (!is_scalar($opttext)) {
                        $opttext = is_scalar($opttext) ? $opttext : (string)@json_encode($opttext);
                    }

                    $options[] = [
                        'value' => is_numeric($optvalue) ? (int)$optvalue : $optvalue,
                        'text'  => (string)$opttext,
                        'checked' => ($qtype === 'multichoice' && $currentAnswer !== '' && $currentAnswer == $optvalue)
                    ];
                }
            }

            $questionsdata[] = [
                'id'             => $qid,
                'display_number' => $displayNumber++,
                'text'           => $qtext,
                'qtype'          => $qtype,
                'qtype_essay'    => ($qtype === 'essay'),
                'qtype_mcq'      => ($qtype === 'multichoice'),
                'options'        => $options,
                'current_answer' => ($qtype === 'multichoice' ? ($currentAnswer === '' ? '' : (int)$currentAnswer) : (string)$currentAnswer)
            ];
        }

        // Get team members for progress label
        $members = $this->quiz_manager->get_members();
        $member_ids = array_map(function($m){ return is_object($m) ? ($m->id ?? 0) : ($m['id'] ?? 0); }, $members);
        $current_index = array_search($this->evaluateeid, $member_ids, true);
        $total_members = count($members);

        // Evaluatee name
        $evaluatee_name = ($this->type === 'self') ? 'Yourself' : '';
        if ($this->type === 'peer') {
            foreach ($members as $m) {
                $mid = is_object($m) ? ($m->id ?? null) : ($m['id'] ?? null);
                if ($mid == $this->evaluateeid) {
                    $evaluatee_name = fullname($m);
                    break;
                }
            }
        }

        // Pack template data
        $data->fullname = fullname($USER);
        $data->evaluatee_name = (string)$evaluatee_name;
        $data->questions = $questionsdata;
        $data->evaluateeid = $this->evaluateeid;
        $data->type = $this->type;
        $data->is_peer = ($this->type === 'peer');
        $data->is_self = ($this->type === 'self');

        // Navigation info
        $data->has_next = $this->has_next;
        $data->has_prev = $this->has_prev;
        $data->is_first = $this->is_first;
        $data->is_last = $this->is_last;
        $data->next_evaluateeid = $this->next_evaluateeid;
        $data->prev_evaluateeid = $this->prev_evaluateeid;

        // Misc
        $data->sesskey = sesskey();
        $data->cmid = $this->quiz_manager->get_cmid();
        $data->progress = (($current_index === false ? 0 : $current_index + 1) . ' of ' . $total_members);

        return $data;
    }
}
