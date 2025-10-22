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
use mod_smartspe\handler\data_persistence;
use mod_smartspe\handler\questions_handler;

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
    protected $data;
    protected $attemptnumber; //Total number of attempts

    /**
     * Create attempt if not already created or else get retrieve the existing attempt
     *
     * Called when an attempt is created or continue processing from the existing attempt.
     *
     * @param $userid the evaluator id
     * @param $smartspeid the instance id
     * @param $attemptid the current attemptid
     * @param $data the data getting from mod_smartspe_mod_form
     * @return void
     */
    public function __construct($userid, $smartspeid, $attemptid=null, $data)
    {
        global $DB, $USER;

        $this->smartspeid = $smartspeid;
        $this->userid = $userid;
        $this->data = $data;
        
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
            $this->attemptid = $DB->insert_record('smartspe_attempts', $record);
            //Get current attempt
            $this->attempt = $DB->get_record('smartspe_attempts', ['id' => $this->$attemptid]);
        }

    }

    public function get_attempt_questions()
    {
        return $this->questions;
    }

    /**
     * Create questions usage and link usage to each attempt
     * Make for data persistence purpose
     *
     * Called when a new instance of the module is created.
     *
     * @return data_persistence $data_persistence 
     */
    public function create_persistence()
    {
        $question_handler = new questions_handler();
        $this->data_persistence = new data_persistence($this->attemptid);

        // Load or create question usage
        if (!empty($this->attempt->uniqueid)) 
        {
            //Load questions 
            $this->questions = $this->data_persistence->load_attempt_questions();
        } 
        else 
        {
            //Cretae questions usage and link to each attempt
            $this->quba = $question_handler->add_all_questions($this->userid, $this->data, $this->attemptid);
            $this->questions = $question_handler->get_all_questions($this->data);
        }

        return $this->data_persistence;
    }

    public function get_attempt_id()
    {
        return $this->attemptid;
    }

}