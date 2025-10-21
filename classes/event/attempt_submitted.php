<?php
namespace mod_smartspe\event;

defined('MOODLE_INTERNAL') || die();

class attempt_submitted extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'u'; // update
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'smartspe_attempts';
    }

    public static function get_name() {
        return get_string('eventattemptsubmitted', 'mod_smartspe');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' submitted an attempt with id '{$this->objectid}' for SmartSPE instance '{$this->contextinstanceid}'.";
    }

    public function get_url() {
        return new \moodle_url('/mod/smartspe/view.php', ['id' => $this->contextinstanceid]);
    }
}
