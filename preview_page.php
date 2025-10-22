<?php
namespace mod_smartspe\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

class preview_page implements renderable, templatable 
{
    protected $answers;
    protected $members;

    public function __construct($answers, $members) {
        $this->answers = $answers;
        $this->members = $members;
    }

    public function export_for_template(renderer_base $output) {
        return [
            'answers' => $this->answers,
            'members' => $this->members
        ];
    }
}
