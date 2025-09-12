<?php
class Student 
{
    private $name;
    private $studentID;

    public function __construct($name, $id) 
    {
        $this->name = $name;
        $this->studentID = $id;
    }

    public function getName() 
    {
        return $this->name;
    }

    public function getID() 
    {
        return $this->studentID;
    }

}
?>