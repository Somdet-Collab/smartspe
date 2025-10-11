<?php

namespace mod_smartspe\classes\event;

use question_engine;
use question_usage_by_activity;

defined('MOODLE_INTERNAL') || die();

class questions_handler
{
    protected $categoryid;

    public function __construct($categoryid)
    {
        $this->categoryid=$categoryid;
    }

    public function questions_create($name, $qtype='multichoice', $text)
    {
        global $DB;

        //Create question
        $question = new \stdClass();
        $question->category = $this->categoryid;       // From mdl_question_categories
        $question->qtype = $qtype;        // e.g. shortanswer, multichoice
        $question->name = $name;
        $qtext = '<p>'.$text.'</p>';
        $question->questiontext = $qtext;
        $question->questiontextformat = FORMAT_HTML;
        $question->defaultmark = 0;
        $question->timecreated = time();
        $question->timemodified = time();

        $questionid = $DB->insert_record('question', $question);

        return $questionid;
    }

    public function load_attempt_questions($attemptid) 
    {
        global $DB;

        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);

        // Load all questions and their current state
        $quba = question_engine::load_questions_usage_by_activity($attempt->uniqueid);

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

    public function load_all_questions()
    {
        global $DB;

        // Get all questions in this category
        $records = $DB->get_records('question', ['category' => $this->categoryid]);

        // Format results as array
        $questions = [];
        foreach ($records as $q) 
        {
            $questions[] = 
            [
                'id' => $q->id,
                'name' => $q->name,
                'text' => $q->questiontext,
                'qtype' => $q->qtype,
                'defaultmark' => $q->defaultmark
            ];
        }

        return $questions;
    }
}