<?php
namespace mod_smartspe;

require(__DIR__ . '/config.php');

class db_team_manager
{
    public function get_members($userid, $courseid)
    {
        global $DB;
        $members = [];

        if ($this->record_exist('groups_members', ['userid' => $userid]))
        {
            //Get teams regarding to courseid
            $teams = $DB->get_records('groups', 'courseid ?', [$courseid]);

            #Get record of $userid
            $record = $DB->get_record('groups_members', 'userid = ?', [$userid]);

            //Check if user team id is in any of the team
            if (in_array($record->teamid, $teams))
                $teamid = $record->groupid;#get team id of this user

            else
                return false;

            #Get all members in the same team
            $members = $DB->get_records('groups_members', ['teamid' => $teamid]);
        }
        else
        {
            $err_msg = "This userid ($userid) is not in the database <br>";
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