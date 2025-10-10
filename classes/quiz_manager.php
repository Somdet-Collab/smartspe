<?php

//This class carries core logic
//Mainly interact with UI
namespace mod_smartspe;

use mod_smartspe\db_team_manager as team_manager;
use mod_smartspe\db_evaluation as evaluation;


class quiz_manager
{

    public function write_file_output($filename, $content, $extension="csv")
    {
        
    }

    public function write_file_data($filename, $content, $extension= "csv")
    {

    }

    public function save_answers($answers, $comment, $userid, $evaluateeid)
    {
        $manager = new team_manager(); //Team management 
        $evaluation = new evaluation(); //evaluation database

        //Ensure both students exists
        if ($manager->record_exist('groups_members', ['userid' => $userid])
            && $manager->record_exist('groups_members', ['userid' => $evaluateeid]))
        {
            $evaluation->save_answers_db($answers, $comment, $userid, $evaluateeid);
        }
        else
        {
            if(!$manager->record_exist('groups_members', ['userid' => $userid]))
                $err_msg = "The student id ($userid) doesn't exist. <br>";
            else
                $err_msg = "The evaluee id ($evaluateeid) doesn't exist. <br>";
        }

    }
}