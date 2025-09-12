<?php

require_once 'Student.php';
class Team
{
    private $teamID;
    private array $members;

    public function __construct(string $teamID) 
    {
        $this->teamID = $teamID;
        $this->members = [];
    }

    public function assignMember(Student $student) 
    {
        array_push($this->members, $student);
    }

    public function getMembers()
    {
        return $this->members;
    }

    public function getTeamID() 
    {
        return $this->teamID;
    }

}

?>