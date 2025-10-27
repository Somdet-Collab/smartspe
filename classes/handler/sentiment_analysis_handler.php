<?php

namespace mod_smartspe\handler;

use mod_smartspe\db_evaluation;
use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die();

//This class carries sentiment analysis process

class sentiment_analysis_handler
{
    /**
     * Feed data into AI model and get polarity and score
     * 
     *@param $evaluationid process through each evaluationid
     * @return boolean if download is successful
     */
    public function process_sentiment_analysis($evaluationid)
    {
        global $DB;
        //collect records all records
        $record = $DB->get_record('smartspe_evaluation', ['evaluationid' => $evaluationid]);
        $comment = $record->comment;
        $self_comment = $record->self_comment;

        //crete AI model

        //Feed comment and self comment into AI

        //Get AI result
        $polarity = "Positive";
        $score = "4.5";

        //Save polarity and score
        $db_manager = new db_evaluation();
        $sentimentid = $db_manager->save_sentiment_analysis($evaluationid, $polarity, $score);

        return $sentimentid;
    }
}