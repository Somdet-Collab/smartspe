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
        
        // Clean Moodle output buffers to avoid messing up headers
        \core\session\manager::write_close(); // close session to avoid lock
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type:text/csv');
        header('Content-Disposition:attachment;filename="'.$filename.'"');

        // Create CSV in memory
        $fp = fopen('php://output', 'w');
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

        $userid = $record->evaluator; //Get evalutor id
        $name = $DB->get_record('user', ['id' => $userid], 'firstname'); //Get member name
        $memberid = $record->evaluatee; //Get evalutee id
        $member_name = $DB->get_record('user', ['id' => $memberid], 'firstname'); //Get member name
        $group = $DB->get_record('groups_members', ['userid' => $userid], 'groupid'); //get teamid
        $polarity = $DB->get_record('smartspe_sentiment_analysis', ['evaluationid' => $record->id], 'polarity');
        $sentiment_score = $DB->get_record('smartspe_sentiment_analysis', ['evaluationid' => $record->id], 'sentimentscore');
        $q1 = $record->q1;
        $q2 = $record->q2;
        $q3 = $record->q3;
        $q4 = $record->q4;
        $q5 = $record->q5;
        $comment = $record->comment;
        $self_comment = $record->self_comment;

        $line = [$userid,$name,$memberid,$member_name,$group,$polarity,
                $sentiment_score,$q1,$q2,$q3,$q4,$q5,$comment,$self_comment];


        return $line;
    }
}