<?php

//This class carries core logic
//Mainly interact with UI
namespace mod_smartspe;

use core\exception\moodle_exception;
use mod_quiz\quiz_attempt;
use mod_smartspe\handler\notification_handler;
use mod_smartspe\handler\questions_handler;
use mod_smartspe\handler\data_persistence;
use mod_smartspe\handler\download_handler;
use mod_smartspe\smartspe_quiz_attempt;
use mod_smartspe\handler\duration_controller;
use mod_smartspe\handler\submission_handler;
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
    }

    /**
     * Create attempt once student attempt the evaluation
     * Create persistence for specific evaluatee
     *
     * Called when student start attempting.
     *
     * @param $memberid attempt on this member
     * @param $data the data getting from mod_smartspe_mod_form
     * @return $attemptid
     */
    public function start_attempt_evaluation($memberid, $questionids)
    {
        $this->quiz_attempt = new smartspe_quiz_attempt($this->smartspeid, $this->userid, 
                                                $memberid, null, $questionids);
        
        if(!$this->quiz_attempt)
            throw new moodle_exception("Quiz attempt creation failed!!");

        //Create persistence object for specific member
        $this->data_persistence = $this->quiz_attempt->create_persistence($this->context, $memberid);

        if (!$this->data_persistence)
            throw new moodle_exception("Failed to create data persistence");

        //Get quiz id
        $this->attemptid = $this->quiz_attempt->get_attempt_id();

        return $this->attemptid;
    }
    
    public function process_attempt_evaluation($newdata=null, $finish=false)
    {
        //Process autosave
        if(!$this->data_persistence->auto_save($newdata))
            throw new moodle_exception("Failed autosave data");

        //If the evaluation finish
        if($finish)
            $this->data_persistence->finish_attempt();

        return true;
    }

    public function get_questions($data)
    {
        return $this->questions_handler->get_all_questions($data);
    }

    public function get_saved_questions_answers()
    {
        return $this->data_persistence->load_attempt_questions();
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