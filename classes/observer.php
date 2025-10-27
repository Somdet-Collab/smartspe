<?php
namespace mod_smartspe;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers for mod_smartspe
 */
class observer {

    /**
     * Handle attempt_start event.
     *
     * @param \mod_smartspe\event\attempt_start $event
     */
    public static function attempt_start(\mod_smartspe\event\attempt_start $event) 
    {
        // Example: log to Moodle debug
        debugging("Attempt started by user {$event->userid}, attempt id {$event->objectid}", DEBUG_DEVELOPER);
    }

    /**
     * Handle attempt_finish event.
     *
     * @param \mod_smartspe\event\attempt_finish $event
     */
    public static function attempt_finish(\mod_smartspe\event\attempt_finish $event)
    {
        debugging("Attempt finished by user {$event->userid}, attempt id {$event->objectid}", DEBUG_DEVELOPER);

        $notifier = new \mod_smartspe\handler\notification_handler();
        $notifier->noti_eval_submitted($event->userid);
    }

    /**
     * Handle download event.
     *
     * @param \mod_smartspe\event\file_download $event
     */
    public static function file_download(\mod_smartspe\event\file_download $event) 
    {
        debugging("Download triggered by user {$event->userid} for cmid {$event->contextinstanceid}", DEBUG_DEVELOPER);

        // Define default filename
        $filename = 'smartspe_report_' . time();

        //Trigger download
        $downloader = new \mod_smartspe\handler\download_handler();
        $downloader->download_file($filename, "csv", $event->other['course']);

    }

}
