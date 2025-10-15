<?php

//This class carries core logic
//Mainly interact with UI
namespace mod_smartspe;

use mod_smartspe\db_team_manager as team_manager;
use mod_smartspe\db_evaluation as evaluation;


class quiz_manager
{
    protected $autosave_handler; //To handle auto save
    protected $data_persistence; //To handle loading saved data
    protected $questions_handler; //To handle loading questions onto UI
    protected $submission_handler; //To handle submission and save data and info to database
    protected $notification_handler; //To handle notification after submission
    protected $download_handler; //To handle report download

    /*
    **
    **return array of questions
    **
    */
    public function load_questions($categoryid)
    {

        //Get all questions

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