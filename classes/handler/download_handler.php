<?php

namespace mod_smartspe\handler;
use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/phpspreadsheet/vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class download_handler
{
    /**
     * Download the report
     *
     * Called when teacher/Unit coordinator request download
     * 
     *@param $filename file name
     *@param $extension file extension
     * @return bool if download is successful
     */
    public function download_file($filename, $extension, $course, $details=false)
    {
        //Check the extension
        if ($extension == "csv" && $details)
            return $this->create_file_csv_details($filename.'.'.$extension, $course);
        else if ($extension == "xlsx" && !$details)
            return $this->create_file_xlsx_summary($filename.'.'.$extension, $course);
        else if ($extension == "pdf")
            return $this->create_file_pdf($filename.'.'.$extension);
        else
            throw new moodle_exception(("The file extension is not supported: {$extension}"));
    }

    /**
     * Create report for .csv
     *
     * Called when teacher/Unit coordinator request download for csv file
     * 
     *@param $filename file name
     * @return boolean if download is successful
     */
    private function create_file_csv_details($filename, $course)
    {
        global $DB;

        // Remove any output before sending CSV
        while (ob_get_level()) {
            ob_end_clean();
        }
        \core\session\manager::write_close();
        
        // Create temporary file in Moodle temp dir
        $tempdir = make_temp_directory('smartspe');
        $tempfile = $tempdir . '/' . $filename;

        // Create CSV in memory
        $fp = fopen($tempfile, 'w');
        if (!$fp) {
            throw new moodle_exception("Cannot open file stream for CSV");
        }

        $header = ["StudentID","Name", "Lastname","Memberid","Member_Name","Member_Lastname","Group","Polarity",
                    "Sentiment_Scores","Q1","Q2","Q3","Q4","Q5","Average","comment","self_comment"];

        fputcsv($fp, $header);

        $records = $DB->get_records('smartspe_evaluation', ['course' => $course]);
        foreach ($records as $record) {
            fputcsv($fp, $this->get_line_record_details($record));
        }

        fclose($fp);

        // Use Moodle’s send_file() to serve download safely
        send_file($tempfile, $filename, 0, 0, false, true, 'text/csv');

        // Stop Moodle rendering page
        exit;
    }

    private function create_file_xlsx_summary($filename, $course)
    {
        global $DB, $CFG;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $row = 1;

        // Loop through teams
        $teams = $DB->get_records('groups', ['courseid' => $course]);
        foreach ($teams as $team) {

            $members = $DB->get_records('groups_members', ['groupid' => $team->id]);
            if (!$members) {
                continue;
            }

            // --- Headers (same as CSV version) ---
            $eval_header = ["", "Student being evaluated", "", ""];
            $header = ["", "Assessment Criteria", "", ""];
            $criteria = ["1", "2", "3", "4", "5", "Average", ""];

            $criteria_header = [];
            $evaluatee_header = [];

            foreach ($members as $group_member) {
                $userid = $group_member->userid;
                $member = $DB->get_record('user', ['id' => $userid]);

                $criteria_header = array_merge($criteria_header, $criteria);
                $member_header = [$member->lastname . " " . $member->firstname, '', '', '', '', '', ''];
                $evaluatee_header = array_merge($evaluatee_header, $member_header);
            }

            $final_header = array_merge($header, $criteria_header);
            $final_eval_header = array_merge($eval_header, $evaluatee_header);

            // Write the two header rows
            $sheet->fromArray($final_eval_header, null, "A{$row}");
            $sheet->fromArray($final_header, null, "A" . ($row + 1));

            // Highlight and style header rows
            $headerRange = "A{$row}:" . $sheet->getHighestColumn() . ($row + 1);
            $sheet->getStyle($headerRange)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'D9E1F2']
                ],
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ]
            ]);

            $row += 3;

            // Sub-header for team member info
            $sheet->fromArray(["Team", "StudentID", "Surname", "Given Name"], null, "A{$row}");
            $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
            $row++;

            // --- Data rows ---
            foreach ($members as $member) {
                $userid = $member->userid;
                $records = $DB->get_records('smartspe_evaluation', ['evaluator' => $userid]);
                if (!$records) {
                    continue;
                }

                $user = $DB->get_record('user', ['id' => $userid]);
                $group_name = $team->name ?? '';

                $details = [$group_name, $userid, $user->lastname ?? '', $user->firstname ?? ''];
                $result_line = [];

                foreach ($records as $record) {
                    $result = $this->get_line_summary($record);
                    $result_line = array_merge($result_line, $result);
                }

                $sheet->fromArray(array_merge($details, $result_line), null, "A{$row}");
                $row++;
            }

            $row += 2; // Blank lines between teams
        }

        // Auto-size columns for clean layout
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save to temporary file
        $tempdir = make_temp_directory('smartspe');
        $tempfile = $tempdir . '/' . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempfile);

        // Send file to browser
        send_file($tempfile, $filename, 0, 0, false, true, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        exit;
    }

    private function create_file_pdf($filename)
    {
        global $DB;

        return true;
    }

    /**
     *Helper in splitting data into columns
     * 
     *@param $record record of evaluation
     * @return array of data
     */
    private function get_line_record_details($record)
    {
        global $DB;

        //User
        $userid = $record->evaluator; //Get evalutor id
        $user = $DB->get_record('user', ['id' => $userid]); //Get member name
        $name = $user->firstname ?? '';
        $lastname = $user->lastname ?? '';

        //Member
        $memberid = $record->evaluatee; //Get evalutee id
        $member = $DB->get_record('user', ['id' => $memberid]); //Get member name
        $member_name = $member->firstname ?? '';
        $member_lastname = $member->lastname ?? '';

        //Groups
        $group_member = $DB->get_record('groups_members', ['userid' => $userid]); //get teamid
        $group = $DB->get_record('groups', ['id' => $group_member->groupid]);
        $group_name = $group->name ?? '';

        //Get analysis result
        $result = $DB->get_record('smartspe_sentiment_analysis', ['evaluationid' => $record->id]);
        $polarity = $result->polarity ?? null;
        $sentiment_score = $result->sentimentscore ?? null;
        $q1 = $record->q1 ?? null;
        $q2 = $record->q2 ?? null;
        $q3 = $record->q3 ?? null;
        $q4 = $record->q4 ?? null;
        $q5 = $record->q5 ?? null;
        $average = isset($record->average) ? (float)$record->average : null;
        $comment = $record->comment ?? null;
        $self_comment = $record->self_comment ?? null;

        $line = [$userid,$name, $lastname,$memberid,$member_name, $member_lastname,$group_name,$polarity,
                $sentiment_score,$q1,$q2,$q3,$q4,$q5,$average,$comment,$self_comment];

        return $line;
    }

    private function get_line_summary($record)
    {
        $q1 = $record->q1 ?? null;
        $q2 = $record->q2 ?? null;
        $q3 = $record->q3 ?? null;
        $q4 = $record->q4 ?? null;
        $q5 = $record->q5 ?? null;
        $average = isset($record->average) ? (float)$record->average : null;

        $line = [$q1,$q2,$q3,$q4,$q5,$average, ""];

        return $line;
    }
}
