<?php

namespace mod_smartspe\classes\event;

use mod_smartspe\db_team_manager as team_manager;
use mod_smartspe\db_evaluation as evaluation;
use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die();

class submission_handler
{
    protected $evaluator;
    protected $courseid;

    public function __construct($evaluator, $courseid)
    {
        $this->evaluator = $evaluator;
        $this->courseid = $courseid;
    }

    /*User submit the form
    **
    **@return bool
    **
    */
    public function is_submitted($answers, $comment, $self_comment = null, $evaluateeid)
    {
        $manager = new team_manager(); //Team management 
        $evaluation = new evaluation(); //evaluation database

        $userid = $this->evaluator;

        //Ensure both students exists
        if ($manager->record_exist('groups_members', ['userid' => $userid])
            && $manager->record_exist('groups_members', ['userid' => $evaluateeid]))
        {
            //Save evaluation info into database
            //Return true if data are saved in db successfully
            return $evaluation->save_answers_db($answers, $comment, $self_comment, 
                                        $userid, $evaluateeid, $this->courseid);
        }
        else
        {
            if(!$manager->record_exist('groups_members', ['userid' => $userid]))
                throw new moodle_exception("The student id ($userid) doesn't exist. <br>");
            else
                throw new moodle_exception("The evaluee id ($evaluateeid) doesn't exist. <br>");
        }
    }

    //If the submission is not done by time
    public function is_due()
    {

    }
}