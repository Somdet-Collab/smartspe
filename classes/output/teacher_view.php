<?php
namespace mod_smartspe\output;

use renderable;
use templatable;
use renderer_base;
use stdClass;

class teacher_view implements renderable, templatable 
{
    protected $quiz_manager;

    public function __construct($quiz_manager) 
    {
        $this->quiz_manager = $quiz_manager;
    }

    public function export_for_template(renderer_base $output) 
    {
        global $DB, $CFG;

        $data = new stdClass();
        $data->activityname = "Smart Self & Peer Evaluation Page for Lecturers";
        $data->description = "Manage questions and monitor self and peer evaluations";
        
        // Get course ID directly from the course module
        $cmid = $this->quiz_manager->get_cmid();
        $cm = get_coursemodule_from_id('smartspe', $cmid, 0, false, MUST_EXIST);
        $courseid = $cm->course;
                
        // 1. Button to access question bank (always visible)
        $data->buttons[] = [
            'name' => 'Manage Questions',
            'url' => (new \moodle_url('/mod/smartspe/question_selection.php', [
                'cmid' => $cmid, 
                'courseid' => $courseid            
            ]))->out(false),
            'icon' => 'fa-list'
        ];

        // 2. Preview Quiz button
        $data->buttons[] = [
            'name' => 'Preview Evaluation',
            'url' => (new \moodle_url('/mod/smartspe/teacher_preview.php', ['id' => $this->quiz_manager->get_cmid()]))->out(false),
            'icon' => 'fa-eye'
        ];

        // 3. Reports button
        $data->buttons[] = [
            'name' => 'View Reports',
            'url' => (new \moodle_url('/mod/smartspe/reports.php', ['id' => $this->quiz_manager->get_cmid()]))->out(false),
            'icon' => 'fa-bar-chart'
        ];

        // 4. Link to question bank (for creating new questions)
        $data->buttons[] = [
            'name' => 'Question Bank',
            'url' => (new \moodle_url('/question/edit.php', [
                'cmid' => $this->quiz_manager->get_cmid(), 
                'courseid' => $this->quiz_manager->get_context()->get_course_context()->instanceid            
            ]))->out(false),
            'icon' => 'fa-database'
        ];

        return $data;
    }
}
