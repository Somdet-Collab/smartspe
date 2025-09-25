<?php
namespace mod\smartspe\classes;

use mod\smartspe\classes\db_manager as team_manager;

class db_evaluation
{
    public function save_answers_db($answers, $comment, $userid, $evaluateeid)
    {
        global $DB;
        $success = false;

        $manager = new team_manager();

        //Call check function from team_manager
        //To confirm that userid is assigned to the team
        if ($manager->record_exist('smartspe_team_member', ['studentID' => $userid]))
        {
            $record = new \stdClass();

            $record->evaluator = $userid;
            $record->evaluatee = $evaluateeid;

            //Loop all answers
            foreach ($answers as $index => $answer)
            {
                $question = 'q'.($index+1); //q1, q2, etc. (database column for questions)
                $record->question = $answer;
            }
            $record->comment = $comment;

            //Insert record into database
            $DB->insert_record('smartspe_evaluation', $record);

            $success = true;
        }
        else
        {
            echo "This student {$userid} has not been assigned to any team <br>";
        }

        return $success;
    }

    public function get_answers_db($userid)
    {
        global $DB;
        $answers = [];
        
        for($i = 0; $i < 5; $i++)
        {   
            //Access questions column
            $question = 'q'.($i+1);
            $record = $DB->get_records('smartspe_evaluation', ['evaluator' => $userid]);
            $answers[$i] = $record->$question;
        }

        return $answers; //Array

    }

    public function get_comment_db($userid)
    {
        global $DB;

        $record = $DB->get_records('smartspe_evaluation', ['evaluator' => $userid]);
        $comment = $record->comment;

        return $comment;
    }

    
}

?>
