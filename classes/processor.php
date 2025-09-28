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