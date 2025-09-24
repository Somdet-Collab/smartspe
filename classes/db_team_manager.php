<?php
namespace mod\smartspe;

require(__DIR__ . '/config.php');

class db_manager
{
    public function create_team($teamid, $project_name, $courseid)
    {
        #Declare variable
        global $DB;

        if ($this->record_exist('sentiment_analysis_team', ['teamcode' => $teamid]))
        {
            #Collet record
            $record = new \stdClass();
            $record->teamcode = $teamid;
            $record->project = $project_name;
            $record->course = $courseid;
        }
        else
        {
            echo "This teamid ($teamid) has already existed in the database <br>";
        }

        #Insert data into databas
        if ($DB->insert_record('sentiment_analysis_team', $record))
            echo "Team {$teamid} has been created. <br>";

    }

    public function update_team($teamid, $teamname)
    {
        global $DB;

        
    }

    public function delete_team($teamid)
    {
        global $DB;
    }
    
    public function assign_team_member($userid, $teamid)
    {
        global $DB;
    }

    public function delete_team_member($userid, $teamid)
    {
        global $DB;
    }

    public function get_members($userid)
    {
        global $DB;
    }

    public function record_exist($table, $record)
    {
        global $DB;

        //$record should be array ['column' => 'value']
        return $DB->record_exists($table, $record);
    }
}