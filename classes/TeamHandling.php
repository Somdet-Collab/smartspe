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
        $this->teams[$team->getTeamID()] = $team;
    }

    public function findStudent(string $studentID)
    {
        $found_team = null;

        foreach ($this->teams as $team)
        {
            foreach ($team->getMembers() as $member)
            {
                if ($member->getId() == $studentID)
                {
                    $found_team = $team;
                }
            }
        }

        if($found_team === null)
            {
                echo "{$studentID} is not found in any group. <br>";
            }

        return $found_team;
    }

    public function getTeams()
    {
        return $this->teams;
    }

    public function exists($teamid)
    {
        return isset($this->teams[$teamid]);
    }

    public function getTeam($teamid)
    {
        return $this->teams[$teamid];
    }
}
?>