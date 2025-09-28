<?php

namespace mod_smartspe;

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use mod_smartspe\db_evaluation as team_manager;
use mod_smartspe\db_evaluation as evaluation;


class processor
{
    
    public function upload_file($file)
    {
        //Verify extension
        $valid_ext = array("xls", "xlsx");

        // Proceed for upload
        $file_name = $file['myfile']['name'];
        $tmp_name = $file['myfile']['tmp'];

        //Get file extension
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (in_array($ext, $valid_ext))
        {

        }
        else 
        {
            
        }
    }

    public function read_file($file_name, $extension)
    {

    }

    public function write_file($filename, $content, $extension="csv")
    {
        
    }

    public function find_column($file, $column)
    {
        $index = 0;



        return $index;
    }

    public function save_answers($answers, $userid, $evaluateeid)
    {

    }

    
}