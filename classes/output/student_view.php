<?php
namespace mod_smartspe\output;

use renderable;
use templatable;
use renderer_base;
use mod_smartspe\smartspe_quiz_manager;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class student_view implements renderable, templatable {
    protected $quiz_manager;

    public function __construct(smartspe_quiz_manager $quiz_manager) {
        $this->quiz_manager = $quiz_manager;
    }

    public function export_for_template(renderer_base $output) {
        global $DB;

        $data = new stdClass();
        $smartspeid = $this->quiz_manager->get_smartspeid();

        // Get evaluation questions from backend
        // 1. Fetch the questionids string from the smartspe record
        $smartspe_record = $DB->get_record('smartspe', ['id' => $smartspeid], 'questionids');
        $questionids_string = $smartspe_record ? $smartspe_record->questionids : '';
        
        // 2. Convert the string to an array of integers (mandatory for SQL IN clause)
        $questionids_array = array_map('intval', array_filter(explode(',', $questionids_string)));
        
        // 3. Get evaluation questions from backend
        $data->questions = $this->quiz_manager->get_questions($questionids_array);        
        
        $data->activityname = "Smart Self & Peer Evaluation";
        $data->description = "Evaluate your groupmates based on contribution and teamwork.";

        // Get group members
        try {
            $data->members = $this->quiz_manager->get_members();
        } catch (\moodle_exception $e) {
            $data->members = [];
        }

        // Form URL (to submit evaluation)
        $data->formaction = new \moodle_url('/mod/smartspe/view.php', ['id' => $this->quiz_manager->get_cmid()]);

        return $data;
    }
}
        