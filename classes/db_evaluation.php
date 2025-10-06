<?php
namespace mod_smartspe;

use mod_smartspe\db_team_manager as team_manager;

class db_evaluation
{
    public function save_answers_db($answers, $comment, $userid, $evaluateeid)
    {
        global $DB;
        $success = false;

        $manager = new team_manager();

        //Call check function from team_manager
        //To confirm that userid is assigned to the team
        if ($manager->record_exist('mdl_groups_members', ['userid' => $userid]))
        {
            $record = new \stdClass();
            $courseid = "ICT302";

            $record->evaluator = $userid;
            $record->evaluatee = $evaluateeid;
            $record->course = $courseid;

            //Loop all answers
            foreach ($answers as $index => $answer)
            {
                $field = 'q'.($index+1); //q1, q2, etc. (database column for questions)
                $record->$field = $answer;
            }
            $record->comment = $comment;

            //Insert record into database
            $DB->insert_record('smartspe_evaluation', $record);

            $success = true;
        }
        else
        {
            $err_msg = "This student {$userid} has not been assigned to any team <br>";
        }

        return $success;
    }

    public function get_answers_db($userid)
    {
        global $DB;
        $answers = [];
        
        //get record
        $record = $DB->get_record('smartspe_evaluation', ['evaluator' => $userid]);
        
        if ($record)
        {
            for($i = 0; $i < 5; $i++)
            {   
                //Access questions column
                $field = 'q'.($i+1);
                $answers[$i] = $record->$field;
            }
        }

        return $answers; //Array

    }

    public function get_comment_db($userid)
    {
        global $DB;
        $comment = null;

        $record = $DB->get_record('smartspe_evaluation', ['evaluator' => $userid]);
        if ($record)
            $comment = $record->comment;

        return $comment;
    }
    
}

?>
