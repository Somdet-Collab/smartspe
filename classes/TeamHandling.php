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
                    break;
                }
            }
        }

        if($found_team === null)
            {
                echo "{$studentID} is not found in any group. <br>";
            }

        return $found_team;
    }

    public function removeTeam($teamID)
    {
        $found_team = false;

        foreach ($this->teams as $key => $team)
        {
            if ($team->getTeamID() == $teamID)
            {
                unset($this->teams[$key]);   // remove by index
                $this->teams = array_values($this->teams); // reindex
                echo "This {$teamID} member is already removed <br><br>";
                $found_team = true;
                break;
            }
        }

        if(!$found_team)
            {
                echo "{$teamID} is not found in the system. <br>";
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