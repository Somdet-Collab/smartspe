<?php

//This class carries core logic
//Mainly interact with UI
namespace mod_smartspe;

use mod_smartspe\event\notification_handler;
use mod_smartspe\event\questions_handler;
use mod_smartspe\event\data_persistence;
use mod_smartspe\event\download_handler;
use mod_smartspe\event\duration_controller;
use mod_smartspe\event\submission_handler;
use mod_smartspe\event\data_handler;
use mod_smartspe\db_evaluation;
use mod_smartspe\db_team_manager;


class smartspe_quiz_manager
{
    protected $autosave_handler; //To handle auto save
    protected $data_persistence; //To handle loading saved data
    protected $questions_handler; //To handle loading questions onto UI
    protected $submission_handler; //To handle submission and save data and info to database
    protected $notification_handler; //To handle notification after submission
    protected $download_handler; //To handle report download
    protected $data_handler; //handle data format

    protected $courseid;
    protected $context;
    protected $quizid;

    public function __construct($userid, $courseid, $context, $quizid)
    {
        //Get all questions
        global $DB;
        $this->courseid = $courseid;
        $this->context = $context;
        $this->quizid = $quizid;
        $this->questions_handler = new questions_handler($context, "Self Peer Evaluation");
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
    public function attempt_evaluation($userid, $attemptid)
    {
        //Get all questions
        global $DB;

        // Get current attempt
        $attempt = $DB->get_record('quiz_attempts', [
            'quiz' => $this->quizid,
            'userid' => $userid,
            'state' => 'inprogress'  // only active attempt
        ]);

        $this->data_persistence = new data_persistence($attempt->id);

        $this->data_persistence->load_attempt_questions();
        
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
}