<?php
namespace mod_smartspe\output;

defined('MOODLE_INTERNAL') || die();

class quiz_page implements \renderable, \templatable {
    protected $instanceid;
    protected $member;
    protected $questions;
    protected $memberindex;
    protected $totalmembers;

    public function __construct($instanceid, $member, $questions, $memberindex, $totalmembers) {
        $this->instanceid = $instanceid;
        $this->member = $member;
        $this->questions = $questions;
        $this->memberindex = $memberindex;
        $this->totalmembers = $totalmembers;
    }

    public function export_for_template(\renderer_base $output) {
        $nextmemberindex = $this->memberindex + 1;

        $nexturl = new \moodle_url('/mod/smartspe/attempt.php', [
            'id' => $this->instanceid,
            'memberindex' => $nextmemberindex
        ]);

        $submiturl = new \moodle_url('/mod/smartspe/attempt.php', [
            'id' => $this->instanceid,
            'memberindex' => $this->memberindex,
            'action' => 'submit'
        ]);

        return [
            'membername' => fullname($this->member),
            'questions' => $this->questions,
            'islast' => $this->memberindex === ($this->totalmembers - 1),
            'nexturl' => $nexturl->out(false),
            'submiturl' => $submiturl->out(false),
        ];
    }
}

