<?php

namespace mod_smartspe\handler;

use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/question/engine/lib.php');

class data_persistence
{
    protected $attemptid;
    protected $attempt;
    protected $memberid;

    public function __construct($attemptid, $memberid)
    {
        global $DB;

        $this->attemptid = $attemptid;
        $this->attempt = $DB->get_record('smartspe_attempts', ['id' => $attemptid], '*', MUST_EXIST);
        $this->memberid = $memberid;
    }

    /**
     * Load questions usage with current state and current answers
     *
     * Called when student attempting the quiz.
     *
     * @return $questions
     */
    public function load_attempt_questions() 
    {
        // Load all questions and their current state
        $quba = \question_engine::load_questions_usage_by_activity($this->attempt->uniqueid);

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

    /**
     * Auto save answers
     *
     * Called when student attempting the quiz.
     * 
     * Called in quiz_manager
     *
     * @param $newdata new answer to be saved
     * @return boolean
     */
    public function auto_save($newdata=null)
    {
        global $DB;

        // Load all questions and their current state
        $quba = \question_engine::load_questions_usage_by_activity($this->attempt->uniqueid);

        // Loop through all slots in this usage
        foreach ($quba->get_slots() as $slot)
        {
            $qa = $quba->get_question_attempt($slot);

            //if new data is not null
            if ($newdata && isset($newdata[$slot]))
            {
                //Update new data
                $this->update_attempt_answers($slot, $newdata[$slot]);
            }
            else //If no new data added
            {
                $currentdata = $qa->get_last_qt_data();

                //if the question has a saved data
                if($currentdata)
                    $quba->process_action($slot, $currentdata, time());
                else //if no saved data, leave it blank
                    $quba->process_action($slot, [], time());

                // Update time modified
                $DB->set_field('smartspe_attempts', 'timemodified', 
                                time(), ['id' => $this->attemptid]);
            }
        }

        // Save the updated usage
        \question_engine::save_questions_usage_by_activity($quba);

        return true;
    }

    /**
     * Update new answer to specific slot question usage and auto save it
     *
     * Called when student attempting the quiz.
     * Called in quiz_manager
     *
     * @param $slot slot of question usage
     * @param $newdata new answer to be saved
     * @return boolean
     */
    private function update_attempt_answers($slot, $newdata)
    {
        global $DB;
        $quba = \question_engine::load_questions_usage_by_activity($this->attempt->uniqueid);

        $qa = $quba->get_question_attempt($slot);

        if (!$qa) {
            throw new moodle_exception("Question slot {$slot} not found in this attempt.");
        }

        //Process the update and save new data
        $updated = $qa->process_autosave($newdata);

        if (!empty($updated['error'])) 
        {
            // Handle validation errors
            throw new moodle_exception("Updated data is invalid.");
        } 
        else 
        {
            //Update time modified
            $DB->set_field('smartspe_attempts', 'timemodified', time(), ['id' => $this->attemptid]);
            // Save the updated quba
            \question_engine::save_questions_usage_by_activity($quba);
        }

        return true;
    }

    
    /**
     * Update finish state of the quiz
     *
     * Called after student has submitted the quiz
     * Called in quiz_manager
     *
     * @return boolean
     */
    public function finish_attempt()
    {
        global $DB;

        // Load the question usage for this attempt
        $quba = \question_engine::load_questions_usage_by_activity($this->attempt->uniqueid);

        // Mark all questions as finished
        $quba->finish_all_questions();

        // Save the updated question usage
        \question_engine::save_questions_usage_by_activity($quba);

        // Update the attempt record to finished
        $DB->set_field('smartspe_attempts', 'state', 'finished', ['id' => $this->attemptid]);
        $DB->set_field('smartspe_attempts', 'timemodified', time(), ['id' => $this->attemptid]);

        // Reload attempt
        $this->attempt = $DB->get_record('smartspe_attempts', ['id' => $this->attemptid], '*', MUST_EXIST);

        return true;
    }


}