<?php

namespace mod_smartspe;

use cm_info;
use coding_exception;
use context;
use context_module;
use moodle_exception;
use moodle_url;
use question_bank;
use stdClass;
use question_engine;
use mod_smartspe\event\data_persistence;
use mod_smartspe\event\questions_handler;

defined('MOODLE_INTERNAL') || die();

class smartspe_quiz_attempt
{
    protected $smartspeid; //Instance id
    protected $userid; //Evaluator id
    protected $attempt; //Attempt object
    protected $quba; //question_usage_by_activity
    protected $data_persistence; //Track student's answers
    protected $questions;
    protected $attemptid; //Attempt id
    protected $attemptnumber; //Total number of attempts

    public function __construct($userid, $smartspeid, $attemptid=null, $data)
    {
        global $DB, $USER;

        $this->smartspeid = $smartspeid;
        $this->userid = $userid;
        
        if ($attemptid)
        {
            $this->attempt = $DB->get_record('smartspe_attempts', ['id' => $attemptid], '*', MUST_EXIST);
            $this->attemptnumber = $this->attempt->attempt;
        }
        else
        {
            // Create new attempt
            $this->attemptnumber = $DB->count_records('smartspe_attempts', ['smartspeid' => $smartspeid, 'userid' => $userid]) + 1;

            $record = new stdClass();
            $record->smartspeid = $smartspeid;
            $record->userid = $userid;
            $record->attempt = $this->attemptnumber;
            $record->timecreated = time();
            $record->timemodified = time();

            //Insert current attempt into database
            $this->attempt->id = $DB->insert_record('smartspe_attempts', $record);
            //Get current attempt
            $this->attempt = $DB->get_record('smartspe_attempts', ['id' => $this->attempt->id]);
        }

        $question_handler = new questions_handler();
        $this->data_persistence = new data_persistence($attemptid);

        // Load or create question usage
        if (!empty($this->attempt->uniqueid)) 
        {
            //Load questions 
            $this->questions = $this->data_persistence->load_attempt_questions();
        } 
        else 
        {
            //Cretae questions usage and link to each attempt
            $this->quba = $question_handler->add_all_questions($userid, $data, $attemptid);
            $this->questions = $question_handler->get_all_questions($data);
        }
    }

    public function get_attempt_id()
    {
        return $this->attemptid;
    }

    public function attempt_evaluation()
    {
        //Get all questions
        global $DB;

        // Get current attempt
        
    }
}