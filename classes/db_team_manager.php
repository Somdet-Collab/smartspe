<?php
namespace mod_smartspe;

require(__DIR__ . '/config.php');

class db_team_manager
{
    public function create_team($teamid, $project_name, $courseid)
    {
        #Declare variable
        global $DB;
        $success = false;

        if (!$this->record_exist('smartspe_team', ['teamcode' => $teamid]))
        {
            #Collet record
            $record = new \stdClass();
            $record->teamcode = $teamid;
            $record->project = $project_name;
            $record->course = $courseid;

            $success = true;
        }
        else
        {
            echo "This teamid ($teamid) has already existed in the database <br>";
            return $success;
        }

        #Insert data into databas
        if ($DB->insert_record('smartspe_team', $record))
            echo "Team {$teamid} has been created. <br>";

        
        return $success;
    }

    public function update_team($teamid, $project)
    {
        global $DB;
        $success = false;

        if ($this->record_exist('smartspe_team', ['teamcode' => $teamid]))
        {
            #Record to be updated
            $record = $DB->get_record('smartspe_team', ['teamcode' => $teamid]);
            $record->project = $project; //Update row with new value
            $DB->update_record('smartspe_team', $record); //Update row in db
            $success = true;
        }
        else
        {
            echo "This teamid ($teamid) is not in the database <br>";
        }

        return $success;
    }

    public function delete_team($teamid)
    {
        global $DB;
        $success = false;

        if ($this->record_exist('smartspe_team', ['teamcode' => $teamid]))
        {
            //Delete members in team_member first
            $DB->delete_records('smartspe_team_member', ['teamid' => $teamid]);
            
            //Delete this team
            $DB->delete_record('smartspe_team', ['teamcode' => $teamid]);
            $success = true;
        }
        else
        {
            echo "This teamid ($teamid) is not in the database <br>";
        }

        return $success;

    }
    
    public function assign_team_member($userid, $teamid)
    {
        global $DB;
        $success = false;

        if ($this->record_exist('smartspe_team_member', ['teamid' => $teamid]))
        {
            //Create class object to store data
            $record = new \stdClass();
            $record->studentID = $userid;
            $record->teamid = $teamid;

            // Insert into database
            $DB->insert_record('smartspe_team_member', $record);
            $success = true;
        }
        else
        {
            echo "Team ($teamid) hasn't been created <br>";
            echo "Please create team first <br>";
        }

        return $success;
    }

    public function delete_team_member($userid)
    {
        global $DB;
        $success = false;

        if ($this->record_exist('smartspe_team_member', ['studentID' => $userid]))
        {
            //Delete row by student id
            $DB->delete_record('smartspe_team_member', ['studentID' => $userid]);
            $success = true;
        }
        else
        {
            echo "This student ($userid) is not in the database <br>";
        }
        return $success;
    }

    public function get_members($userid)
    {
        global $DB;
        $members = [];

        if ($this->record_exist('smartspe_team_member', ['studentID' => $userid]))
        {
            #Get record of $userid
            $record = $DB->get_record_select('smartspe_team_member', 'studentID = ?', [$userid]);
            $teamid = $record->teamid;#get team id of this user

            #Get all members in the same team
            $members = $DB->get_records('smartspe_team_member', ['teamid' => $teamid]);
        }
        else
        {
            echo "This userid ($userid) is not in the database <br>";
        }

        return $members;
    }

    public function record_exist($table, $record)
    {
        global $DB;

        //$record should be array ['column' => 'value']
        return $DB->record_exists($table, $record);
    }
}