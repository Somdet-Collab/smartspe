<?php

namespace mod_smartspe\event;

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
        $this->attempt = $DB->get_record('smartspe_attempts', ['id' => $attemptid], '*', MUST_EXIST);
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

    public function finish_attempt()
    {
        global $DB;

        // Load the question usage for this attempt
        $quba = question_engine::load_questions_usage_by_activity($this->attempt->uniqueid);

        // Mark all questions as finished
        $quba->finish_all_questions();

        // Save the updated question usage
        question_engine::save_questions_usage_by_activity($quba);

        // Update the attempt record to finished
        $DB->set_field('smartspe_attempts', 'state', 'finished', ['id' => $this->attemptid]);
        $DB->set_field('smartspe_attempts', 'timemodified', time(), ['id' => $this->attemptid]);

        // Optionally reload attempt object
        $this->attempt = $DB->get_record('smartspe_attempts', ['id' => $this->attemptid], '*', MUST_EXIST);

        return true;
    }


}