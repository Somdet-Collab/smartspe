<?php
namespace mod_smartspe\event;

defined('MOODLE_INTERNAL') || die();

class attempt_created extends \core\event\base
{

    protected function init()
    {
        $this->data['crud'] = 'c'; // create
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'smartspe_attempts';
    }

    public static function get_name()
    {
        return get_string('eventattemptcreated', 'mod_smartspe');
    }

    public function get_description()
    {
        return "The user with id '{$this->userid}' created an attempt with id '{$this->objectid}' for SmartSPE instance '{$this->contextinstanceid}'.";
    }

    public function get_url()
    {
        return new \moodle_url('/mod/smartspe/view.php', ['id' => $this->contextinstanceid]);
    }
}
