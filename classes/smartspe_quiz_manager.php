<?php

//This class carries core logic
//Mainly interact with UI
namespace mod_smartspe;

use core\exception\moodle_exception;
use mod_smartspe\handler\notification_handler;
use mod_smartspe\handler\questions_handler;
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

    /**
     * Initializing context and plugin details
     * 
     *@param $userid user who attempting the quiz
     *@param $courseid course where this attempt belong to
     *@param $context context of the plugin
     *@param $smartspeid instance id
     * @return void
     */
    public function __construct($userid, $courseid, $context, $smartspeid)
    {
        //Get all questions
        $this->courseid = $courseid;
        $this->context = $context;
        $this->smartspeid = $smartspeid;
        $this->userid = $userid;
        $this->questions_handler = new questions_handler();
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
     * @return int $attemptid
     */
    public function start_attempt_evaluation($memberid, $questionids)
    {
        $this->quiz_attempt = new smartspe_quiz_attempt($this->smartspeid, $this->userid, 
                                                $memberid, $questionids);
        
        //Failed in creating a quiz attempt
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
    
    /**
     *Auto save the answers
     *if the evaluation is done, mark finish
     *
     *Called after start_attempt_evaluation
     * 
     *@param $newdata data to be autosaved
     *@param $finish if the evaluation finish
     * @return boolean
     */
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

     /**
     *Get list of questions from question bank with no saved answers 
     *
     * 
     *@param $questionids questionids selected by teacher
     * @return array questions
     */
    public function get_questions($questionids)
    {
        return $this->questions_handler->get_all_questions($questionids);
    }

    /**
     * Get list of questions with state and saved answers
     * 
     * @return array questions
     */
    public function get_saved_questions_answers()
    {
        return $this->data_persistence->load_attempt_questions();
    }

    /**
     * Return member ids
     *
     * @return array $member ids
     */
    public function get_members()
    {
        $team_manager = new db_team_manager();
        $members = $team_manager->get_members_id($this->userid, $this->courseid);

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
    public function quiz_is_submitted($answers, $memberid, $comment, $self_comment=null)
    {
        $this->submission_handler = new submission_handler($this->userid, $this->courseid, $this->attemptid);
        //Return boolean
        $submitted = $this->submission_handler->is_submitted($answers, $memberid, 
                                                        $comment, $self_comment);

        //if success in submitting, send notification to email
        if($submitted)
            $this->notification_handler->noti_eval_submitted($this->userid);

        return $submitted;
    }

    /**
     * Download the report
     *
     * Called when teacher/Unit coordinator request download
     * 
     *@param $filename file name
     *@param $extension file extension
     * @return boolean if download is successful
     */
    public function download_report($filename, $extension="csv")
    {
        if (!strcasecmp($extension, "csv") || !strcasecmp($extension, "pdf"))
            throw new moodle_exception("quiz_manager: error file extension");

        return $this->download_handler->download_file($filename, $extension);
    }

    public function write_file_data($filename, $content, $extension= "csv")
    {

    }

}