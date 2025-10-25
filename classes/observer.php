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
    public static function attempt_start(\mod_smartspe\event\attempt_start $event) {
        // Example: log to Moodle debug
        debugging("Attempt started by user {$event->userid}, attempt id {$event->objectid}", DEBUG_DEVELOPER);
    }

    /**
     * Handle attempt_finish event.
     *
     * @param \mod_smartspe\event\attempt_finish $event
     */
    public static function attempt_finish(\mod_smartspe\event\attempt_finish $event) {
        debugging("Attempt finished by user {$event->userid}, attempt id {$event->objectid}", DEBUG_DEVELOPER);
    }

    /**
     * Handle download event.
     *
     * @param \mod_smartspe\event\download $event
     */
    public static function download(\mod_smartspe\event\download $event) {
        debugging("Download triggered by user {$event->userid} for cmid {$event->contextinstanceid}", DEBUG_DEVELOPER);
    }

    /**
     * Handle evaluation_after_duedate event.
     *
     * @param \mod_smartspe\event\after_duedate $event
     */
    public static function after_duedate(\mod_smartspe\event\after_duedate $event) {
        debugging("Evaluation submitted after due date by user {$event->userid}, evaluation id {$event->objectid}", DEBUG_DEVELOPER);
    }
}
