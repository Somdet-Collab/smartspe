<?php

namespace mod_smartspe\classes\event;

use question_engine;
use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die();

class data_persistence
{
    protected $attemptid;
    protected $attempt;

    public function __construct($attemptid)
    {
        global $DB;

        $this->attemptid = $attemptid;
        $this->attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);
    }
    public function load_attempt_questions() 
    {
        // Load all questions and their current state
        $quba = question_engine::load_questions_usage_by_activity($this->attempt->uniqueid);

        //Get all questions
        $questions = [];
        foreach ($quba->get_slots() as $slot) 
        {
            $qa = $quba->get_question_attempt($slot);
            $question = $qa->get_question();

            $questions[$slot] = 
            [
                'id' => $question->id,
                'name' => $question->name,
                'text' => $question->questiontext,
                'state' => $qa->get_state(),
                'current_answer' => $qa->get_submitted_data()
            ];
        }

        return $questions;
    }

    public function update_attempt_answers($slot, $newdata)
    {
        $quba = question_engine::load_questions_usage_by_activity($this->attempt->uniqueid);

        $qa = $quba->get_question_attempt($slot);

        if (!$qa) {
            throw new moodle_exception("Question slot {$slot} not found in this attempt.");
        }

        //
        $updated = $qa->process_autosave($newdata);

        if (!empty($updated['error'])) 
        {
            // Handle validation errors
            throw new moodle_exception("Updated data is invalid.");
        } 
        else 
        {
            // Save the updated quba
            question_engine::save_questions_usage_by_activity($quba);
        }

        return true;
    }

}