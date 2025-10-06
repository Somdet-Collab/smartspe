<?php
namespace mod_smartspe;

require(__DIR__ . '/config.php');

class db_team_manager
{
    public function get_members($userid)
    {
        global $DB;
        $members = [];

        if ($this->record_exist('mdl_groups_members', ['userid' => $userid]))
        {
            #Get record of $userid
            $record = $DB->get_record_select('mdl_groups_members', 'userid = ?', [$userid]);
            $teamid = $record->groupid;#get team id of this user

            #Get all members in the same team
            $members = $DB->get_records('mdl_groups_members', ['teamid' => $teamid]);
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