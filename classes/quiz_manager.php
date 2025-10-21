<?php

//This class carries core logic
//Mainly interact with UI
namespace mod_smartspe;

use core\exception\moodle_exception;
use mod_quiz\quiz_attempt;
use mod_smartspe\event\notification_handler;
use mod_smartspe\event\questions_handler;
use mod_smartspe\event\data_persistence;
use mod_smartspe\event\download_handler;
use mod_smartspe\smartspe_quiz_attempt;
use mod_smartspe\event\duration_controller;
use mod_smartspe\event\submission_handler;
use mod_smartspe\event\data_handler;
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

    /*
    **
    **return array of questions
    **
    */
    public function create_evaluation_attempt($data)
    {
        $this->quiz_attempt = new smartspe_quiz_attempt($this->userid, $this->smartspeid, null, $data);

        if(!$this->quiz_attempt)
            throw new moodle_exception("Quiz attempt creation failed!!");

        //Get quiz id
        $this->attemptid = $this->quiz_attempt->get_attempt_id();
    }

    public function attempt_evaluation()
    {

    }

    public function get_questions()
    {

    }

    public function get_members()
    {
        $team_manager = new db_team_manager();
        $members = $team_manager->get_members($this->userid, $this->courseid);

        if (empty($members))
            throw new moodle_exception("The members are empty in section get_members() in quiz_manager");
        
        return $members;
    }

    public function download_file_output($filename, $extension="csv")
    {
        
    }

    public function write_file_data($filename, $content, $extension= "csv")
    {

    }

    public function save_evaluation($answers, $comment, $userid, $evaluateeid)
    {
        
    }

    public function quiz_notification()
    {
        
    }
}