<?php

namespace mod_smartspe\classes\event;
use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die();

class download_handler
{
    public function download_file($filename, $extension)
    {
        //Check the extension
        if ($extension == "csv")
            $this->create_file_csv($filename);
        else if($extension == "pdf")
            $this->create_file_pdf(($filename));
        else
            throw new moodle_exception(("The file extension is not supported: {$extension}"));
    }

    public function create_file_csv($filename)
    {
        global $DB;

        //Open file 
        $fp = fopen('php://output', 'w');
        $header = array("StudentID", "Name", "Memberid", "Member_Name", "Group", "Polarity", "Sentiment_Scores", 
                        "Q1", "Q2", "Q3", "Q4","Q5", "comment", "self_comment"); //header line
        fputcsv($fp, $header); //Write header

        //Get records of evaluation
        $records = $DB->get_records('smartspe_evaluation');

        foreach ($records as $key => $record)
        {
            $line = $this->get_line_record($record);
            fputcsv($fp, $line); //Insert record row
        }

        fclose($fp);
        header('Content-type:application/csv');
        header('Content-disposition:attachment;filename="'.$filename.'"');
    }

    public function create_file_pdf($filename)
    {
        global $DB;
    }

    /*
    *@return line of record
    *
    *
    */
    public function get_line_record($record)
    {
        global $DB;

        $stdid = $record->evaluator; //Get evalutor id
        $name = $DB->get_record('user', ['id' => $stdid], 'name'); //Get member name
        $memberid = $record->evaluatee; //Get evalutee id
        $member_name = $DB->get_record('user', ['id' => $memberid], 'name'); //Get member name
        $group = $DB->get_record('groups_members', ['userid' => $stdid], 'groupid'); //get teamid
        $polarity = $DB->get_record('smartspe_sentiment_analysis', ['evalutionid' => $record->id], 'polarity');
        $sentiment_score = $DB->get_record('smartspe_sentiment_analysis', ['evalutionid' => $record->id], 'sentimentscore');
        $q1 = $record->q1;
        $q2 = $record->q2;
        $q3 = $record->q3;
        $q4 = $record->q4;
        $q5 = $record->q5;
        $comment = $record->comment;
        $self_comment = $record->self_comment;

        $line = array($stdid, $name, $memberid, $member_name, $group, $polarity, $sentiment_score,
                        $q1, $q2, $q3, $q4, $q5, $comment, $self_comment);

        return $line;
    }
}