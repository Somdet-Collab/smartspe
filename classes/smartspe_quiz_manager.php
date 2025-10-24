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
use mod_smartspe\handler\data_persistence;

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
    protected $smartspeid;
    protected $userid;
    protected $attemptids;
    protected $members; //it also includes attemptid for specific members

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

        //Get members of this $userid
        $team_manager = new db_team_manager();
        $this->members = $team_manager->get_members_id($this->userid, $this->courseid);

        if (empty($this->members))
            throw new moodle_exception("The members are empty in section get_members() in quiz_manager");
    }

    /**
     * Create attempt once student attempt the evaluation
     * Create persistence for specific evaluatee
     *
     * Called when student start attempting.
     *
     * @param $memberid attempt on this member
     * @param $data the data getting from mod_smartspe_mod_form
     * @return array attempt ids
     */
    public function start_attempt_evaluation($memberid, $questionids)
    {
        $this->quiz_attempt = new smartspe_quiz_attempt($this->smartspeid, $this->userid, 
                                                $memberid, $questionids);
        
        //Failed in creating a quiz attempt
        if(!$this->quiz_attempt)
            throw new moodle_exception("Quiz attempt creation failed!!");

        //Collect attemptid for specific member
        $this->attemptids[$memberid] = $this->quiz_attempt->get_attempt_id();
        if (isset($this->attemptids[$memberid])) //Create persistence object for specific member
            $this->data_persistence = $this->quiz_attempt->create_persistence($this->context, $memberid);
        else
            throw new moodle_exception("Failed to create quiz attempt for {$memberid}");

        if (!$this->data_persistence)
            throw new moodle_exception("Failed to create data persistence");

        //return attemptid
        return $this->attemptids[$memberid];
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
    public function process_attempt_evaluation($answers, $comment, $self_comment=null, $finish=false)
    {
        //Assign comment
        $comments['comment'] = $comment;

        if ($self_comment)
            $comments['self_comment'] = $self_comment;

        //Wrap data
        $newdata = [
            'answers' => $answers,
            'comment' => $comments
        ];

        //Process autosave
        if(!$this->data_persistence->auto_save($newdata))
            throw new moodle_exception("Failed autosave data");

        //If the evaluation finish
        if($finish)
        {
            $this->data_persistence->finish_attempt();
        }

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
     * @return array comments
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
        return $this->members;
    }

    /**
     * Save all answers into database
     *
     * Called when student confirm submitting.
     *
     * @return boolean
     */
    public function quiz_is_submitted()
    {
        foreach($this->members as $memberid)
        {
            //Initialize
            $comment = null;
            $self_comment = null;
            $answers = [];

            //Create object for specific member
            $data_persistence = new data_persistence($this->attemptids[$memberid], $memberid);

            //Load autosaved questions with answers and comments
            [$questions, $comments] = $data_persistence->load_attempt_questions();

            //Get all saved answers
            foreach ($data_persistence->get_slots() as $slot) 
            {
                //Ensure it is in correct format array[1,2,3,4]
                $current = $questions[$slot]['current_answer'];
                if (is_array($current)) {
                    $answers[] = (int) reset($current);
                } else {
                    $answers[] = (int) $current;
                }
            }

            //Get comment
            if($comments)
            {
                $comment = $comments['comment'];
                //Get self comment
                if(!$comments['self_comment'])
                    $self_comment = null;
                else
                    $self_comment = $comments['self_comment'];
            }

            $this->submission_handler = new submission_handler($this->userid, 
                            $this->courseid, $this->attemptids[$memberid]);

            //Return evaluation id
            $evaluationid = $this->submission_handler->is_submitted($answers, $memberid, 
                                                        $comment, $self_comment);

            if (!$evaluationid)
                throw new moodle_exception('In quiz_manager: Failed in submitting the evaluation');
        }

        //if success in submitting all data, send notification to email
        $this->notification_handler->noti_eval_submitted($this->userid);

        return true;
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