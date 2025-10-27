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
        
        // detect if questions already exist for smartSPE
        $contextid = $this->quiz_manager->get_context()->id;
        
        require_once($CFG->libdir . '/questionlib.php');
        require_once($CFG->dirroot . '/question/editlib.php');

        // Load question categories in this context
        $categories = \question_categorylist($contextid);

        // Get all questions in those categories
        if (!empty($categories)) {
            list($catidsql, $params) = $DB->get_in_or_equal($categories);
            $questions = $DB->get_records_select('question', "categoryid $catidsql", $params, '', 'id, name, questiontext');
        } else {
            $questions = [];
        }

        //  check if any question IDs are already linked to this SmartSPE activity
        // since we store them as a comma-separated list in the 'questionids' field
        $smartspeid = $this->quiz_manager->get_smartspeid();
        $record = $DB->get_record('smartspe', ['id' => $smartspeid], 'questionids');
        $hasquestions = !empty($record->questionids);
                
        // generate button dynamically based on existance of questions
        if ($hasquestions) {
            $data->actionbutton = [
                'name' => 'Preview Quiz',
                'url' => new \moodle_url('/mod/smartspe/preview.php', ['id' => $this->quiz_manager->get_cmid()]),
                'icon' => 'fa-eye'
            ];
        } else {
            $data->actionbutton = [
                'name' => 'Create First Question',
                'url' => new \moodle_url('/mod/smartspe/question/edit.php', ['cmid' => $this->quiz_manager->get_cmid(), 'courseid' => $this->quiz_manager->get_courseid()]),
                'icon' => 'fa-plus-circle'
            ];
        }

        //$data->questions = array_values($questions);  // printing questions for debugging.

        return $data;
    }
}
