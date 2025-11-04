<?php

namespace mod_smartspe\handler;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/question/engine/lib.php');

class questions_handler
{
    /**
     * Get questions from questions bank
     *
     * Called when loading data and display questions to users.
     *
     * @param $data the data getting from mod_smartspe_mod_form
     * @return array $questions
     */
    public function get_all_questions($questionids)
    {
        if (empty($questionids) || !is_array($questionids)) {
            return [];
        }

        $questions = [];

        foreach ($questionids as $q) {
            $questionobj = \question_bank::load_question($q);

            $options = [];

            // Normalize options source (handle stdClass, arrays, nested ->answers)
            if (!empty($questionobj->options)) {
                $optsource = null;
                if (is_object($questionobj->options) && isset($questionobj->options->answers)) {
                    $optsource = $questionobj->options->answers;
                } else {
                    $optsource = $questionobj->options;
                }

                // If it's an object convert to array for foreach
                if (is_object($optsource)) {
                    $optsource = (array)$optsource;
                }

                if (is_array($optsource)) {
                    foreach ($optsource as $opt) {
                        // opt can be object or array
                        $optobj = is_object($opt) ? $opt : (object)$opt;

                        // Determine value and text safely
                        $value = null;
                        if (isset($optobj->id)) {
                            $value = (int)$optobj->id;
                        } elseif (isset($optobj->value)) {
                            $value = is_numeric($optobj->value) ? (int)$optobj->value : (string)$optobj->value;
                        }

                        $text = $optobj->answer ?? $optobj->text ?? '';

                        // Fallback if value still null (avoid empty value in radios)
                        if ($value === null) {
                            $value = count($options) + 1;
                        }

                        // Ensure text is a string and safe to render
                        if (!is_scalar($text)) {
                            $text = @json_encode($text);
                        }
                        $text = (string)$text;

                        $options[] = [
                            'value' => $value,
                            'text'  => $text
                        ];
                    }
                }
            }

            // Fallback: some question shapes expose answers in ->answers (not ->options)
            if (empty($options) && !empty($questionobj->answers)) {
                foreach ((array)$questionobj->answers as $aid => $a) {
                    $aobj = is_object($a) ? $a : (object)$a;
                    $value = isset($aobj->id) ? (int)$aobj->id : (count($options) + 1);
                    $text = $aobj->answer ?? $aobj->text ?? '';
                    $options[] = ['value' => $value, 'text' => (string)$text];
                }
            }

            // qtype extraction and normalization (make canonical strings used by template)
            $qtype = 'multichoice';
            if (isset($questionobj->qtype)) {
                if (is_object($questionobj->qtype) && method_exists($questionobj->qtype, 'name')) {
                    $rawq = strtolower($questionobj->qtype->name());
                } else {
                    $rawq = strtolower((string)$questionobj->qtype);
                }
                if (strpos($rawq, 'multichoice') !== false || strpos($rawq, 'choice') !== false) {
                    $qtype = 'multichoice';
                } elseif (strpos($rawq, 'essay') !== false) {
                    $qtype = 'essay';
                } else {
                    $qtype = $rawq;
                }
            }

            // DEBUG: log when options are unexpectedly empty (remove after debugging)
            if ($qtype === 'multichoice' && empty($options)) {
                error_log('SMARTSPE: get_all_questions q=' . $q . ' returned NO options; questionobj keys: ' . implode(',', array_keys((array)$questionobj)));
            }

            $questions[] = [
                'id' => $questionobj->id,
                'name' => $questionobj->name ?? '',
                'text' => format_text($questionobj->questiontext ?? '', $questionobj->questiontextformat ?? FORMAT_HTML),
                'qtype' => $qtype,
                'defaultmark' => $questionobj->defaultmark ?? 0,
                'questiontextformat' => $questionobj->questiontextformat ?? FORMAT_HTML,
                'options' => $options
            ];
        }

        return $questions;
    }


    /**
     * Add questions into question bank using question id
     *
     * Called after the mod_form is created and teacher selected questions.
     *
     * @param $context add questions to specific context
     * @param $attemptid the current attemptid
     * @param $data the data getting from mod_smartspe_mod_form
     * @return $quba
     */
    public function add_all_questions($context, $questionids, $attemptid)
    {
        global $DB;

        $quba = \question_engine::make_questions_usage_by_activity('mod_smartspe', $context);
        $quba->set_preferred_behaviour('deferredfeedback');

        // $data comes from $mform->get_data() after submission
        if (empty($questionids))
            return [];

        $qids = $questionids;
        
        foreach ($qids as $q)
        {
            $question = \question_bank::load_question($q);
            $qa = $quba->add_question($question);
        }

        //Save the usage
        $quba->start_all_questions();
        \question_engine::save_questions_usage_by_activity($quba);

        $qubaid = $quba->get_id(); // usage ID after saving
        $DB->set_field('smartspe_attempts', 'uniqueid', $qubaid, ['id' => $attemptid]);

        return $quba;
    }
}