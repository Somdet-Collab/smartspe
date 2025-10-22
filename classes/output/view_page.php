<?php
namespace mod_smartspe\output;

use renderable;
use templatable;
use renderer_base;
use moodle_url;

class view_page implements renderable, templatable {
    protected $cm;
    protected $smartspe;
    protected $questionids;

    public function __construct($cm, $smartspe, $questionids) {
        $this->cm = $cm;
        $this->smartspe = $smartspe;
        $this->questionids = $questionids;
    }

    public function export_for_template(renderer_base $output) {
        return [
            'activityname' => $this->smartspe->name,
            'description' => format_text($this->smartspe->intro, $this->smartspe->introformat),
            'starturl' => (new moodle_url('/mod/smartspe/attempt.php', [
                'id' => $this->cm->id,
                'memberindex' => 0,
                'questionids' => explode(',', $this->questionids),
            ]))->out(false)
        ];
    }
}
