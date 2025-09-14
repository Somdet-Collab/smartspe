<?php

class Evaluation
{
    private $scores;
    private $comment;

    public function __construct()
    {
        $this->scores = [];
        $this->comment = "";
    }
 
    //Prompt student id to identify student
    //And pass the the team that student belong to
    public function evaluate($studentid, $team)
    {
        
    }
    public function addScore(int $score)
    {
        array_push($this->scores, $score);
    }

    public function addComment(string $comment)
    {
        $this->comment = $comment;
    }

    public function getScores()
    {
        return $this->scores;
    }

    public function getComment()
    {
        return $this->comment;
    }
}