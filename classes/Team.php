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

    public function setName(string $id)
    {
        $this->teamID = $id;
    }

    public function assignMember(Student $student) 
    {
        array_push($this->members, $student);
    }

    public function removeMember($id)
    {
        $found = false;

        foreach($this->members as $key => $member)
        {
            if ($member->getID() == $id)
            {
                unset($this->members[$key]);   // remove by index
                $this->members = array_values($this->members); // reindex
                echo "This {$id} member is already removed <br><br>";
                $found = true;
                break;
            }
        }

        //If member not found
        if(!$found)
        {
            echo "This {$id} student is not found in the group <br><br>";
        }
    }

    public function getMemberSize()
    {
        return count($this->members);
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