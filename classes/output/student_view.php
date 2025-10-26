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
        $data = new stdClass();

        // Get evaluation questions from backend
        $data->questions = $this->quiz_manager->get_questions(['context' => 'student']);
        $data->activityname = "Smart Self & Peer Evaluation";
        $data->description = "Evaluate your groupmates based on contribution and teamwork.";

        // Get group members
        try {
            $data->members = $this->quiz_manager->get_members();
        } catch (\moodle_exception $e) {
            $data->members = [];
        }

        // Form URL (to submit evaluation)
        $data->formaction = new \moodle_url('/mod/smartspe/view.php', ['id' => $this->quiz_manager->get_smartspeid()]);

        return $data;
    }
}
        