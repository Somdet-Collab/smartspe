<?php

namespace mod_smartspe\classes\event;

use question_engine;
use question_bank;
use question_category;
use context_course;

defined('MOODLE_INTERNAL') || die();

class questions_handler
{
    protected $category;
    protected $context;
    protected $questionbankname;

    public function __construct($courseid, $questionbankname)
    {
        // Get or create the question bank
        $this->context = context_course::instance($courseid);
        $category = question_category::get_category_by_name($this->context, $questionbankname);

        //Create category if no category
        if (!$category) 
            $category = question_category::create_category($this->context, $questionbankname);

        $this->category = $category;
        $this->questionbankname = $questionbankname;
    }

    public function questions_create($name, $qtype='multichoice', $text)
    {
        //Create question
        $question = new \stdClass();
        $question->category = $this->category->id;       // From mdl_question_categories
        $question->qtype = $qtype;        // e.g. shortanswer, multichoice
        $question->name = $name;
        $qtext = '<p>'.$text.'</p>';
        $question->questiontext = $qtext;
        $question->questiontextformat = 'FORMAT_HTML';
        $question->defaultmark = 0;
        $question->timecreated = time();
        $question->timemodified = time();

        //Moodle API to save questions
        $questionid = question_bank::save_question($question, true);

        return $questionid;
    }

    public function load_attempt_questions($attemptid) 
    {
        global $DB;

        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);

        // Load all questions and their current state
        $quba = question_engine::load_questions_usage_by_activity($attempt->uniqueid);

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

    public function get_all_questions()
    {
        global $DB;

        // Get all questions in this category
        $records = $DB->get_records('question', ['category' => $this->category]);

        // Format results as array
        $questions = [];
        foreach ($records as $q) 
        {
            // Load the full question object
            $questionobj = question_bank::load_question($q->id);

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
}