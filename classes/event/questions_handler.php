<?php

namespace mod_smartspe\event;

use question_engine;

defined('MOODLE_INTERNAL') || die();
class questions_handler
{
    public function get_all_questions($data)
    {

        // $data comes from $mform->get_data() after submission
        if (empty($data->questionids))
            return [];

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

    public function add_all_questions($userid, $data, $attemptid)
    {
        global $DB;

        $quba = question_engine::make_questions_usage_by_activity('mod_smartspe', $userid);

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
        $quba->finish_all_questions();

        $qubaid = $quba->get_id(); // usage ID after saving
        $DB->set_field('smartspe_attempts', 'uniqueid', $qubaid, ['id' => $attemptid]);

        return $quba;
    }
}