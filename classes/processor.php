<?php

namespace mod_smartspe;

require 'vendor/autoload.php';

use mod_smartspe\db_team_manager as team_manager;
use mod_smartspe\db_evaluation as evaluation;


class processor
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

    public function find_column($row, $column_name)
    {
        $index = 0;

        //Loop the header
        //Key is index
        foreach($row as $key => $column)
        {
            if ($column_name == $column)
            {
                $index = $key;
                return $index;
            }
        }
        return -1;
    }
}