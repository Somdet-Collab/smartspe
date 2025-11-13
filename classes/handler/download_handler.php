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
    public function download_file($filename, $extension, $course, $details = false)
    {
        //Check the extension
        if ($extension == "csv" && $details)
            return $this->create_file_csv_details($filename . '.' . $extension, $course);
        else if ($extension == "csv" && !$details)
            return $this->create_file_csv_sentiment($filename . '.' . $extension, $course);
        else if ($extension == "xlsx" && !$details)
            return $this->create_file_xlsx_summary($filename . '.' . $extension, $course);
        else if ($extension == "pdf")
            return $this->create_file_pdf($filename . '.' . $extension);
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

        $header = [
            "StudentID",
            "Name",
            "Lastname",
            "Memberid",
            "Member_Name",
            "Member_Lastname",
            "Group",
            "Polarity",
            "Sentiment_Scores",
            "Q1",
            "Q2",
            "Q3",
            "Q4",
            "Q5",
            "Average",
            "comment",
            "self_comment"
        ];

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

    /**
     * Create report for .csv
     *
     * Called when teacher/Unit coordinator request download for csv file
     * 
     *@param $filename file name
     * @return boolean if download is successful
     */
    private function create_file_xlsx_summary($filename, $course)
    {
        global $DB;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $row = 1;

        /* --- ADD TITLE (wide merge to be safe) --- */
        $title = "Self and Peer Assessment: Student Ratings";
        // Merge a wide range so title span always covers headers that come later
        $sheet->mergeCells("A{$row}:ZZ{$row}");
        $sheet->setCellValue("A{$row}", $title);
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
        $row += 2; // leave space after title

        // Get all teams in this course
        $teams = $DB->get_records('groups', ['courseid' => $course]);
        foreach ($teams as $team) {

            $members = $DB->get_records('groups_members', ['groupid' => $team->id]);
            if (!$members) {
                continue;
            }

            // Filter out invalid members (integrity check)
            $valid_members = [];
            foreach ($members as $m) {
                $user = $DB->get_record('user', ['id' => $m->userid]);
                if ($user) {
                    $valid_members[$m->userid] = $m;
                }
            }
            if (empty($valid_members)) {
                continue;
            }

            // --- Header setup ---
            $eval_header = ["", "Student being evaluated", "", ""];
            $header = ["", "Assessment Criteria", "", ""];
            $criteria = ["1", "2", "3", "4", "5", "Average", ""];

            $criteria_header = [];
            $evaluatee_header = [];

            // Build a deterministic evaluatee order (important for column alignment)
            $evaluatee_order = [];
            foreach ($valid_members as $group_member) {
                $userid = $group_member->userid;
                $member = $DB->get_record('user', ['id' => $userid]);
                if (!$member)
                    continue;

                $evaluatee_order[] = $userid;

                $criteria_header = array_merge($criteria_header, $criteria);
                $member_header = [
                    $member->lastname . " " . $member->firstname,
                    '',
                    '',
                    '',
                    '',
                    '',
                    ''
                ];
                $evaluatee_header = array_merge($evaluatee_header, $member_header);
            }

            // If no valid evaluatee headers (safety)
            if (empty($evaluatee_order)) {
                continue;
            }

            $final_header = array_merge($header, $criteria_header);
            $final_eval_header = array_merge($eval_header, $evaluatee_header);

            // Write headers
            $sheet->fromArray($final_eval_header, null, "A{$row}");
            $sheet->fromArray($final_header, null, "A" . ($row + 1));

            // Determine last column for this team (details columns = 4, each evaluatee block = 7 columns)
            $detailsCount = 4;
            $lastColIndex = $detailsCount + (count($evaluatee_order) * 7);
            $lastCol = $sheet->getCellByColumnAndRow($lastColIndex, $row)->getColumn();

            // Style header (limit to team columns only)
            $headerRange = "A{$row}:{$lastCol}" . ($row + 1);
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
                     'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                 ]
             ]);

             $row += 3;

            // Subheader for evaluator info
            $sheet->fromArray(["Team", "StudentID", "Surname", "Given Name"], null, "A{$row}");
            $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
            $row++;

            // --- Prepare structure for vertical averaging ---
            $vertical_sums = [];
            $vertical_counts = [];

            // --- Data rows for evaluators ---
            foreach ($valid_members as $member) {
                $userid = $member->userid;
                $records = $DB->get_records('smartspe_evaluation', ['evaluator' => $userid]);
                if (!$records) {
                    // still write the evaluator details row? you previously skipped - keep same behaviour
                    continue;
                }

                $user = $DB->get_record('user', ['id' => $userid]);
                $group_name = $team->name ?? '';

                $details = [$group_name, $userid, $user->lastname ?? '', $user->firstname ?? ''];
                $result_line = [];

                // Build a map from evaluatee => record so we can output cells in the header order
                $records_map = [];
                foreach ($records as $r) {
                    $records_map[$r->evaluatee] = $r;
                }

                // For each evaluatee in the header order, either place their result or blanks
                foreach ($evaluatee_order as $eval_index => $evaluatee_userid) {
                    if (isset($records_map[$evaluatee_userid])) {
                        $record = $records_map[$evaluatee_userid];
                        $result = $this->get_line_summary($record); // returns [Q1..Q5, avg]
                        $result_line = array_merge($result_line, $result);

                        // Track vertical sums (use same index scheme as get_line_summary returns)
                        foreach ($result as $index => $val) {
                            if (!is_numeric($val)) {
                                continue;
                            }
                            $vertical_sums[$evaluatee_userid][$index] = ($vertical_sums[$evaluatee_userid][$index] ?? 0) + $val;
                            $vertical_counts[$evaluatee_userid][$index] = ($vertical_counts[$evaluatee_userid][$index] ?? 0) + 1;
                        }
                    } else {
                        // No record for this evaluatee from this evaluator -> insert 7 blanks (5Q + avg + trailing)
                        $result_line = array_merge($result_line, array_fill(0, 7, ''));
                    }
                }

                // Write the evaluator row
                $sheet->fromArray(array_merge($details, $result_line), null, "A{$row}");

                /* --- HIGHLIGHT SELF-EVALUATION BLOCK (correctly aligned) --- */
                // Details columns = 4 (A..D), each evaluatee block = 7 columns
                $detailsCount = 4;
                foreach ($evaluatee_order as $blockIndex => $evaluatee_userid) {
                    // If this block is a self-evaluation (evaluator == evaluatee) and there is actually a record
                    if ($userid == $evaluatee_userid && isset($records_map[$evaluatee_userid])) {
                        $startColIndex = $detailsCount + ($blockIndex * 7) + 1; // 1-based column index
                        $endColIndex = $startColIndex + 6;
                        $startCol = $sheet->getCellByColumnAndRow($startColIndex, $row)->getColumn();
                        $endCol = $sheet->getCellByColumnAndRow($endColIndex, $row)->getColumn();
                        $selfRange = "{$startCol}{$row}:{$endCol}{$row}";

                        $sheet->getStyle($selfRange)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'color' => ['rgb' => 'C6E0B4'] // light green
                            ]
                        ]);
                        // only one self-eval block per row, so can break if you want:
                        break;
                    }
                }

                $row++;
            }

            // --- Add Average Row ---
            $avg_details = ["", "", "", "Average"];
            $avg_line = [];

            foreach ($evaluatee_order as $evaluatee) {
                $answers = [];

                if (isset($vertical_sums[$evaluatee])) {
                    foreach ($vertical_sums[$evaluatee] as $index => $sum) {
                        $count = $vertical_counts[$evaluatee][$index] ?? 0;
                        $avg = $count ? round($sum / $count, 2) : '';
                        $answers[] = $avg;
                    }
                }

                // Ensure always 6 columns (5Q + avg)
                $answers = array_pad($answers, 6, '');
                // Add trailing empty to match header layout
                $answers[] = '';

                $avg_line = array_merge($avg_line, $answers);
            }

            // Write the averages row
            $sheet->fromArray(array_merge($avg_details, $avg_line), null, "A{$row}");
            // Style averages only across the team columns calculated earlier
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                 'fill' => [
                     'fillType' => Fill::FILL_SOLID,
                     'color' => ['rgb' => 'FFF2CC']
                 ],
                 'font' => ['bold' => true],
                 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                 'borders' => [
                     'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                 ]
             ]);

            $row += 3; // Leave space between teams
        }

        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save to temporary file
        $tempdir = make_temp_directory('smartspe');
        $tempfile = $tempdir . '/' . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempfile);

        // Send file to browser
        send_file(
            $tempfile,
            $filename,
            0,
            0,
            false,
            true,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
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
        if ($group_member = $DB->get_record('groups_members', ['userid' => $userid])) //get teamid
        {
            if ($group = $DB->get_record('groups', ['id' => $group_member->groupid]))
                $group_name = $group->name;
            else
                $group_name = '';
        } else {
            $group_name = '';
        }

        //Get analysis result
        $result = $DB->get_record('feedback_ai_results', ['evaluatorID' => $userid, 'evaluateeID' => $memberid]);
        $polarity = $result->predicted_label ?? null;
        $sentiment_score = $result->text_score ?? null;
        $q1 = $record->q1 ?? null;
        $q2 = $record->q2 ?? null;
        $q3 = $record->q3 ?? null;
        $q4 = $record->q4 ?? null;
        $q5 = $record->q5 ?? null;
        $average = isset($record->average) ? (float) $record->average : null;
        $comment = $record->comment ?? null;
        $self_comment = $record->self_comment ?? null;

        $line = [
            $userid,
            $name,
            $lastname,
            $memberid,
            $member_name,
            $member_lastname,
            $group_name,
            $polarity,
            $sentiment_score,
            $q1,
            $q2,
            $q3,
            $q4,
            $q5,
            $average,
            $comment,
            $self_comment
        ];

        return $line;
    }

    private function create_file_csv_sentiment($filename, $course)
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

        $header = ["Evaluator ID", "Evaluator Name", "Evaluatee ID", "Evaluatee Name", "Group", "Evaluation Type", 
                    "Feedback_Text", "Toxicity_score", "Toxicity_label", "text_score", "predicted_label"];

        fputcsv($fp, $header);

        $records = $DB->get_records('smartspe_evaluation', ['course' => $course]);
        foreach ($records as $record) {
            $line = $this->get_line_record_sentiment($record);
            if (empty($line))
                continue;
            else
                fputcsv($fp, $line);
        }

        fclose($fp);

        // Use Moodle’s send_file() to serve download safely
        send_file($tempfile, $filename, 0, 0, false, true, 'text/csv');

        // Stop Moodle rendering page
        exit;
    }

    private function get_line_record_sentiment($record)
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
        if ($group_member = $DB->get_record('groups_members', ['userid' => $userid])) //get teamid
        {
            if ($group = $DB->get_record('groups', ['id' => $group_member->groupid]))
                $group_name = $group->name;
            else
                $group_name = '';
        } else {
            $group_name = '';
        }

        //Get analysis result
        $result = $DB->get_record('feedback_ai_results', ['evaluatorID' => $userid, 'evaluateeID' => $memberid]);

        if (!$result)
            return [];

        $eval_type = $result->evaluation_type;
        $eval_feedback = $result->feedback_text;
        $eval_toxicity_score = $result->toxicity_score;
        $eval_toxicity_label = $result->toxicity_label;
        $eval_text_score = $result->text_score;
        $eval_predicted = $result->predicted_label;

        $line = [
            $userid,
            $name,
            $memberid,
            $member_name,
            $group_name,
            $eval_type,
            $eval_feedback,
            $eval_toxicity_score
            ,
            $eval_toxicity_label,
            $eval_text_score,
            $eval_predicted
        ];

        return $line;
    }

    private function get_line_summary($record)
    {
        $q1 = $record->q1 ?? null;
        $q2 = $record->q2 ?? null;
        $q3 = $record->q3 ?? null;
        $q4 = $record->q4 ?? null;
        $q5 = $record->q5 ?? null;
        $average = isset($record->average) ? (float) $record->average : null;

        $line = [$q1, $q2, $q3, $q4, $q5, $average, ""];

        return $line;
    }
}
