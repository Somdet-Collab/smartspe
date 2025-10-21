<?php

//This class carries core logic
//Mainly interact with UI
namespace mod_smartspe;

use core\exception\moodle_exception;
use mod_quiz\quiz_attempt;
use mod_smartspe\event\notification_handler;
use mod_smartspe\event\questions_handler;
use mod_smartspe\data_class\data_persistence;
use mod_smartspe\event\download_handler;
use mod_smartspe\smartspe_quiz_attempt;
use mod_smartspe\event\duration_controller;
use mod_smartspe\event\submission_handler;
use mod_smartspe\data_class\data_handler;
use mod_smartspe\db_evaluation;
use mod_smartspe\db_team_manager;


class smartspe_quiz_manager
{
    protected $quiz_attempt;
    protected $data_persistence; //To handle loading saved data
    protected $questions_handler; //To handle loading questions onto UI
    protected $submission_handler; //To handle submission and save data and info to database
    protected $notification_handler; //To handle notification after submission
    protected $download_handler; //To handle report download
    protected $data_handler; //handle data format

    protected $courseid;
    protected $context;
    protected $attemptid;
    protected $smartspeid;
    protected $userid;

    public function __construct($userid, $courseid, $context, $smartspeid)
    {
        //Get all questions
        global $DB;
        $this->courseid = $courseid;
        $this->context = $context;
        $this->smartspeid = $smartspeid;
        $this->userid = $userid;
        $this->questions_handler = new questions_handler();
        $this->submission_handler = new submission_handler($userid, $courseid);
        $this->notification_handler = new notification_handler();
        $this->download_handler = new download_handler();
        $this->data_handler = new data_handler();
    }

    /**
     * Create attempt once student attempt the evaluation
     *
     * Called when student start attempting.
     *
     * @param $data the data getting from mod_smartspe_mod_form
     * @return $attemptid
     */
    public function create_evaluation_attempt($data)
    {
        $this->quiz_attempt = new smartspe_quiz_attempt($this->userid, $this->smartspeid, null, $data);

        if(!$this->quiz_attempt)
            throw new moodle_exception("Quiz attempt creation failed!!");

        //Get quiz id
        $this->attemptid = $this->quiz_attempt->get_attempt_id();

        return $this->attemptid;
    }

    
    public function start_attempt_evaluation($newdata=null)
    {
        //Create persistence object
        $this->data_persistence = $this->quiz_attempt->create_persistence();
        
    }

    public function get_questions($data)
    {
        return $this->questions_handler->get_all_questions($data);
    }

    public function get_members()
    {
        $team_manager = new db_team_manager();
        $members = $team_manager->get_members($this->userid, $this->courseid);

        if (empty($members))
            throw new moodle_exception("The members are empty in section get_members() in quiz_manager");
        
        return $members;
    }

    /**
     * Save answers into database
     *
     * Called when student start submitting.
     *
     * @param $answers answers array
     * @param $comment comment on members or self
     * @param $self_comment second self comment
     * @param $memberid member being evaluated
     * @return boolean
     */
    public function quiz_is_submitted($answers, $comment, $self_comment=null, $memberid)
    {
        //Return boolean
        $submitted = $this->submission_handler->is_submitted($answers, $comment, 
                                $self_comment, $memberid);

        //if success in submitting, send notification to email
        if($submitted)
            $this->notification_handler->noti_eval_submitted($this->userid);

        return $submitted;
    }

    public function download_file_output($filename, $extension="csv")
    {
        
    }

    public function write_file_data($filename, $content, $extension= "csv")
    {

    }

}