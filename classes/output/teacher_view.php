<?php
namespace mod_smartspe\output;

use renderable;
use templatable;
use renderer_base;
use stdClass;

class teacher_view implements renderable, templatable {
    protected $quiz_manager;

    public function __construct($quiz_manager) {
        $this->quiz_manager = $quiz_manager;
    }

    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->activityname = "Smart Self & Peer Evaluation (Instructor)";
        $data->description = "Monitor peer evaluations and export reports.";
        $data->reports = []; // later, fetch from DB
        return $data;
    }
}
