<?php

namespace mod_smartspe\classes\event;

use question_engine;

defined('MOODLE_INTERNAL') || die();

class data_persistence
{
    protected $attempt;

    public function __contruct($attemptid)
    {
        global $DB;

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
                'current_answer' => $qa->get_last_step() ? $qa->get_last_step()->get_data() : null
            ];
        }

        return $questions;
    }

    public function update_attempt_answers($slot, $newdata)
    {
        $quba = question_engine::load_questions_usage_by_activity($this->attempt->uniqueid);

        $qa = $quba->get_question_attempt($slot);

        if (!$qa) 
        {
            throw new \Exception("Question slot {$slot} not found in this attempt.");
        }

        // Start a new step for this attempt
        $qa->start_new_step();

        // Set the new answer
        $qa->set_response($newdata);

        // Finish the step (not graded yet)
        $qa->finish_step();

        // Save the question usage (persist changes)
        $quba->save_all_steps();

        return true;
    }

}