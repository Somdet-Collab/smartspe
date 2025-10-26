<?php
namespace mod_smartspe\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a user downloads results or files.
 *
 * @package    mod_smartspe
 */
class file_download extends \core\event\base 
{

    protected function init() 
    {
        $this->data['objecttable'] = null;
        $this->data['crud'] = 'r'; // read
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name() 
    {
        return get_string('event_download', 'mod_smartspe');
    }

    public function get_description() 
    {
        return "The user with id '{$this->userid}' downloaded a file or report related to the smartspe activity (cmid: {$this->contextinstanceid}).";
    }

    public function get_url() 
    {
        return new \moodle_url('#');
    }
}
