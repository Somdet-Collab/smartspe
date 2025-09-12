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
        <input type="submit" name="submit">
    </form>
</body>
</html>

<?php

require_once 'Student.php';
require_once 'Team.php';
require_once 'TeamHandling.php';

$id = $_POST["stdID"];
$name = $_POST["name"];
$group = $_POST["group"];

$student = new Student($name, $id);

// //Team 1
// $student1 = new Student("James", 3547777);
// $student2 = new Student("Jack", 356666);
// $student3 = new Student("Lim Beng", 354898);
// $student4 = new Student("Lack Knowledge", 334420);

// //Team 2
// $student5 = new Student("KiKi Wang", 3548987);
// $student6 = new Student("Menga", 3547720);

// //Team 3
// $student7 = new Student("Luca set", 350047);
// $student8 = new Student("Lackin", 354889);
// $student9 = new Student("Mistery James", 300922);

// $teams = new TeamHandling();

// $team1 = new Team("FT01");
// $team2 = new Team("FT02");
// $team3 = new Team("FT03");

// $team1->assignMember($student1);
// $team1->assignMember($student2);
// $team1->assignMember($student3);
// $team1->assignMember($student4);

// $team2->assignMember($student5);
// $team2->assignMember($student6);

// $team3->assignMember($student7);
// $team3->assignMember($student8);
// $team3->assignMember($student9);

$teams = new TeamHandling();



$counter = 0;

// foreach ($team1->getMembers() as $member)
// {
//     $counter++;
//     echo "Student {$counter}'s name is {$member->getName()}. <br>";
// }

if (isset($_POST['submit'])) 
{
    echo "Student id is {$student->getID()} <br>";
    echo "Student Name is {$student->getName()} <br>";
}

?>
