<?php

namespace mod_smartspe\classes\event;

use question_bank;
use core_question\category as question_category;

defined('MOODLE_INTERNAL') || die();
class questions_handler
{
    protected $category;
    protected $context;
    protected $questionbankname;

    public function __construct($courseid, $questionbankname)
    {
        // Get or create the question bank
        $this->context = \context_course::instance($courseid);
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
        $question = new \stdclass();
        $question->category = $this->category->id;       // From mdl_question_categories
        $question->qtype = $qtype;        // e.g. shortanswer, multichoice
        $question->name = $name;
        $qtext = '<p>'.$text.'</p>';
        $question->questiontext = $qtext;
        $question->questiontextformat = FORMAT_HTML;
        $question->defaultmark = 0;
        $question->timecreated = time();
        $question->timemodified = time();

        //Moodle API to save questions
        $questionid = question_bank::save_answer($question);

        return $questionid;
    }

    public function get_all_questions()
    {
        global $DB;

        // Get all questions in this category
        $records = $DB->get_records('question', ['category' => $this->category->id]);

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