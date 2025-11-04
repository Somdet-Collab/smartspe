<?php
namespace mod_smartspe;

require_once(__DIR__ . '/../../../config.php');

use core\exception\moodle_exception;

class db_team_manager
{
    public function get_members_id($userid, $courseid)
    {
        global $DB;

        if (!$this->record_exist('groups_members', ['userid' => $userid])) {
            return [];
        }

        $userrecord = $DB->get_record('groups_members', ['userid' => $userid], '*', MUST_EXIST);
        $groupid = $userrecord->groupid;

        if (!$this->record_exist('groups', ['id' => $groupid, 'courseid' => $courseid])) {
            throw new moodle_exception("User {$userid}’s group does not belong to course {$courseid}.");
        }

        // get full records and map to integer ids
        $members = $DB->get_records('groups_members', ['groupid' => $groupid], '', '*');
        if (empty($members)) {
            throw new moodle_exception("No members found in the group for user {$userid}.");
        }

        $members_id = array_map(fn($m) => (int)$m->userid, $members);
        $members_id = array_values(array_unique($members_id, SORT_NUMERIC)); // reindex and dedupe

        return $members_id;
    }

    public function record_exist($table, $record)
    {
        global $DB;

        //$record should be array ['column' => 'value']
        return $DB->record_exists($table, $record);
    }
}