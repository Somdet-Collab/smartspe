<?php
namespace mod_smartspe\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

class quiz_page implements renderable, templatable
{
    protected $member;
    protected $questions;
    protected $memberindex;
    protected $totalmembers;
    protected $smartspeid;

    public function __construct($smartspeid, $member, $questions, $memberindex, $totalmembers)
    {
        $this->smartspeid = $smartspeid;
        $this->member = $member;
        $this->questions = $questions;
        $this->memberindex = $memberindex;
        $this->totalmembers = $totalmembers;
    }

    public function export_for_template(renderer_base $output)
    {
        $questions = [];
        foreach ($this->questions as $slot => $q) {
            $questions[] = [
                'slot' => $slot,
                'text' => $q['text'],
                'answer' => $q['current_answer'] ?? ''
            ];
        }

        $islast = $this->memberindex === $this->totalmembers - 1;

        return [
            'smartspeid' => $this->smartspeid,
            'membername' => $this->member->name,
            'memberindex' => $this->memberindex,
            'questions' => $questions,
            'islast' => $islast
        ];
    }
}
