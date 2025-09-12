<?php

require_once 'Student.php';
require_once 'Team.php';

class TeamHandling
{
    private $teams;

    public function __construct()
    {
        $this->teams = [];
    }

    public function addTeam(Team $team)
    {
        array_push($this->teams, $team);
    }

    public function getTeams(Student $student)
    {
        $found_team = null;

        foreach ($this->teams as $team)
        {
            if (in_array($student, $team->getMembers()))
            {
                $found_team = $team;
            }
        }

        if(empty($found_team))
            {
                echo "{$student->getName()}({$student->getId()} is not found in any group. <br>";
            }

        return $found_team;
    }
}
?>