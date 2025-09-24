<?php

use mod\smartspe\classes\Team as Team;

    session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="index.php" method="post">
        <label>Student ID:</label>
        <input type="number" name="stdID"><br>
        <label>Student Name:</label>
        <input type="text" name="name"><br>
        <label>Group ID:</label>
        <input type="text" name="group"><br>
        <input type="submit" name="submit"><br><br>
        <label>Display all Teams: </label>
        <input type="submit" value="Display Teams" name="allteam"><br><br>
        <label>TeamID: </label>
        <input type="text" name="teamid"><br>
        <input type="submit" value="Display members" name="team"><br><br>
        <label>Delete student id:</label>
        <input type="number" name="deleteid"><br>
        <input type="submit" value="Delete" name="delete"><br><br>
        <label>Delete Team id:</label>
        <input type="text" name="deleteteam"><br>
        <input type="submit" value="Delete" name="delteam"><br><br>
    </form>
</body>
</html>

<?php

$id = $_POST["stdID"] ?? null;
$name = $_POST["name"] ?? null;
$group = $_POST["group"] ?? null;

//Is used to find team
$teamID = $_POST["teamid"] ?? null;

if (!isset($_SESSION['teams'])) 
{
    $_SESSION['teams'] = new TeamHandling();
}

//Team array
$teams = $_SESSION['teams'];

$student = null;

if (isset($_POST["submit"])) 
{

    if ($id != null && $name != null && $group != null)
    {
        if(!$teams->exists($group))
        {
            $teams->addTeam(new Team($group));
        }

        // Create new student object 
        $student = new Student($name, $id);

        $team = $teams->getTeam($group);
        $team->assignMember($student);
        $teams->addTeam($team);
    }
    else
    {
        echo "Please enter all details!! <br><br>";
    }
}

if (isset($_POST["team"]))
{
    if ($teams->exists($teamID) && $teamID)
    {
        $temp_team = $teams->getTeam($teamID);

        foreach($temp_team->getMembers() as $std)
        {
            echo "Member id is {$std->getID()} <br>";
            echo "Member Name is {$std->getName()} <br><br>";
        }
    }
    elseif (!$teams->exists($teamID) && $teamID)
    {
        echo "Team {$teamID} does not exist <br>";
    }
    else
    {
        echo "You haven't entered any team id <br>";
    }

}

if (isset($_POST["delete"]))
{
    $delId = $_POST["deleteid"] ?? null;

    if ($delId)
    {
        $temp_team = $teams->findStudent($delId);

        if (!empty($temp_team))
        {
            $temp_team->removeMember($delId);
            $teams->addTeam($temp_team); // save bac
        }
    }
    else
    {
        echo "You didn't enter id to be deleted <br><br>";
    }
}

if (isset($_POST["allteam"]))
{
    $allteams = $teams->getTeams();
    $count = 0;

    if (!empty($allteams))
    {
        foreach($allteams as $team)
        {
            $count++;
            echo "{$count}. TeamID: {$team->getTeamID()} <br><br>";
        }
    }
    else
    {
        echo "There is no team entered yet!! <br>";
    }
}

if (isset($_POST["delteam"]))
{
    $delId = $_POST["deleteteam"] ?? null;

    if ($delId)
    {
        if ($teams->removeTeam($delId))
        {
            echo "Team {$delId} is deleted from the system <br><br>";
        }
    }
    else
    {
        echo "You didn't enter id to be deleted <br><br>";
    }
}

if (isset($_POST['submit'])) 
{
    if($student)
    {
        echo "Student id is {$student->getID()} <br>";
        echo "Student Name is {$student->getName()} <br>";
    }
}

?>
