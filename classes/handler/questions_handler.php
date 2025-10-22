<?php

namespace mod_smartspe\handler;

use question_engine;

defined('MOODLE_INTERNAL') || die();
class questions_handler
{
    /**
     * Get questions from questions bank
     *
     * Called when loading data and display questions to users.
     *
     * @param $data the data getting from mod_smartspe_mod_form
     * @return $questions
     */
    public function get_all_questions($data)
    {

        // $data comes from $mform->get_data() after submission
        if (empty($data->questionids))
            return [];

        //split array{1,2,3}
        $qids = explode(',', $data->questionids);

        // Format results as array
        $questions = [];
        foreach ($qids as $q) 
        {
            // Load the full question object
            $questionobj = \question_bank::load_question($q);

            $questions[] = 
            [
                'id' => $questionobj->id,
                'name' => $questionobj->name,
                'text' => $questionobj->questiontext,
                'qtype' => $questionobj->qtype,
                'defaultmark' => $questionobj->defaultmark,
                'questiontextformat' => $questionobj->questiontextformat,
                'answers' => $questionobj->answers
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
    public function add_all_questions($context, $data, $attemptid)
    {
        global $DB;

        $quba = question_engine::make_questions_usage_by_activity('mod_smartspe', $context);
        $quba->set_preferred_behaviour('deferredfeedback');

        // $data comes from $mform->get_data() after submission
        if (empty($data->questionids))
            return [];

        $qids = explode(',', $data->questionids);
        
        foreach ($qids as $q)
        {
            $question = \question_bank::load_question($q);
            $qa = $quba->add_question($question);
        }

        //Save the usage
        $quba->start_all_questions();
        question_engine::save_questions_usage_by_activity($quba);

        $qubaid = $quba->get_id(); // usage ID after saving
        $DB->set_field('smartspe_attempts', 'uniqueid', $qubaid, ['id' => $attemptid]);

        return $quba;
    }
}