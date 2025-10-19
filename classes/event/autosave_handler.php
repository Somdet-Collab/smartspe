<?php

namespace mod_smartspe\event;

defined('MOODLE_INTERNAL') || die();

class autosave_handler
{

    /*
    **
    **This class is used to auto save data temporarily for data persistent
    **
    **
    **@return bool
    */
    public function autosave_data($attemptid, $userid, $data)
    {
        global $DB;

        //Create object
        $record = new \stdclass();

        //Assign value
        $record->attemptid = $attemptid;
        $record->evaluateeid = $userid;
        $record->data = json_encode($data);
        $record->timemodified = time(); //Track current time

        //Check if table already hold this attemptid + userid
        $existing = $DB->get_record('smartspe_autosave', ['attemptid' => $attemptid, 'evaluateeid' => $userid]);
        if ($existing)
            return $DB->update_record('smartspe_autosave', $record); //If evaluateeid already exist, then update data
        else
            return $DB->insert_record('smartspe_autosave', $record); //If evaluteeid hasn't existed, insert new row
    }

    /*
    **
    **Delete all temporarily data when user submitted evaluation
    **
    **@return bool
    */
    public function delete_autosave_data($attemptid, $userid)
    {
        global $DB;
        return $DB->delete_records('smartspe_autosave');
    }
}