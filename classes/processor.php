<?php

namespace mod_smartspe;

require 'vendor/autoload.php';

use mod_smartspe\db_evaluation as team_manager;
use mod_smartspe\db_evaluation as evaluation;


class processor
{
    
    public function upload_file($file)
    {
        //Verify extension
        $valid_ext = array("xls", "xlsx");

        // Proceed for upload
        $file_name = $file['name'];
        $tmp_name = $file['tmp_name'];
        $upload_dir = "upload/";

        //Get file extension
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($ext, $valid_ext))
        {
            //Create new file
            $newfile = time()."-".basename($file_name);

            try
            {
                //Upload file
                move_uploaded_file($tmp_name, $upload_dir.$newfile);
                echo "<br> The file {$newfile} has been uploaded <br>";

                //Read data to file
                $this->read_file($upload_dir.$newfile);
            }
            catch (\Exception $e)
            {
                $err_msg = $e->getMessage();
            }
        }
        else 
        {
            $err_msg = "Invalid File Extension.";
        }
    }

    public function read_file($file_name)
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        $spreadsheet = $reader->load($file_name);
        $worksheets = $spreadsheet->getActiveSheet();
        $data = $worksheets->toArray();

        //Get index of each specify columns
        $col_given_name = $this->find_column($data[0], "Given Name");
        $col_project_title = $this->find_column($data[0], "PROJECT");
        $col_teamid = $this->find_column($data[0], "TEAM");

        //Save data to database
        $manager = new team_manager();
        foreach ($data as $row)
        {
            $name = $row[$col_given_name];
            $project = $row[$col_project_title];
            $teamid = $row[$col_teamid];

            
        }

    }

    public function write_file($filename, $content, $extension="csv")
    {
        
    }

    public function save_answers($answers, $userid, $evaluateeid)
    {

    }

    public function find_column($row, $column_name)
    {
        $index = 0;

        //Loop the header
        //Key is index
        foreach($row as $key => $column)
        {
            if ($column_name == $column)
            {
                $index = $key;
                break;
            }
            else
            {
                return $index = -1;
            }
        }
        return $index;
    }
}