<?php

namespace mod_smartspe\handler;
use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/filelib.php');

class download_handler
{
    /**
     * Download the report
     *
     * Called when teacher/Unit coordinator request download
     * 
     *@param $filename file name
     *@param $extension file extension
     * @return boolean if download is successful
     */
    public function download_file($filename, $extension, $course)
    {
        //Check the extension
        if ($extension == "csv")
            return $this->create_file_csv($filename.'.'.$extension, $course);
        else if ($extension == "pdf")
            return $this->create_file_pdf($filename.'.'.$extension);
        else
            throw new moodle_exception(("The file extension is not supported: {$extension}"));
    }

    /**
     * Create report for .csv
     *
     * Called when teacher/Unit coordinator request download for csv file
     * 
     *@param $filename file name
     * @return boolean if download is successful
     */
    private function create_file_csv($filename, $course)
    {
        global $DB;
        
        // Create temporary file in Moodle temp dir
        $tempdir = make_temp_directory('smartspe');
        $tempfile = $tempdir . '/' . $filename;

        // Create CSV in memory
        $fp = fopen($tempfile, 'w');
        if (!$fp) {
            throw new moodle_exception("Cannot open file stream for CSV");
        }

        $header = ["StudentID","Name","Memberid","Member_Name","Group","Polarity",
                    "Sentiment_Scores","Q1","Q2","Q3","Q4","Q5","comment","self_comment"];

        fputcsv($fp, $header);

        $records = $DB->get_records('smartspe_evaluation', ['course' => $course]);
        foreach ($records as $record) {
            fputcsv($fp, $this->get_line_record($record));
        }

        fclose($fp);

        // Use Moodle’s send_file() to serve download safely
        send_file($tempfile, $filename, 0, 0, false, true, 'text/csv');

        // Stop Moodle rendering page
        exit;
    }


    private function create_file_pdf($filename)
    {
        global $DB;

        return true;
    }

    /**
     *Helper in splitting data into columns
     * 
     *@param $record record of evaluation
     * @return array of data
     */
    private function get_line_record($record)
    {
        global $DB;

        //User
        $userid = $record->evaluator; //Get evalutor id
        $user = $DB->get_record('user', ['id' => $userid], 'firstname'); //Get member name
        $name = $user->firstname ?? '';

        //Member
        $memberid = $record->evaluatee; //Get evalutee id
        $member = $DB->get_record('user', ['id' => $memberid], 'firstname'); //Get member name
        $member_name = $member->firstname ?? '';

        //Groups
        $group_member = $DB->get_record('groups_members', ['userid' => $userid]); //get teamid
        $group = $DB->get_record('groups', ['id' => $group_member->groupid]);
        $group_name = $group->name ?? '';

        //Get analysis result
        $result = $DB->get_record('smartspe_sentiment_analysis', ['evaluationid' => $record->id]);
        $polarity = $result->polarity ?? null;
        $sentiment_score = $result->sentimentscore ?? null;
        $q1 = $record->q1 ?? null;
        $q2 = $record->q2 ?? null;
        $q3 = $record->q3 ?? null;
        $q4 = $record->q4 ?? null;
        $q5 = $record->q5 ?? null;
        $comment = $record->comment ?? null;
        $self_comment = $record->self_comment ?? null;

        $line = [$userid,$name,$memberid,$member_name,$group_name,$polarity,
                $sentiment_score,$q1,$q2,$q3,$q4,$q5,$comment,$self_comment];

        return $line;
    }
}