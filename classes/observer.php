<?php

//Auto loaded classs to declare event that can be triggered
//If the user triggers the event, it should perform something

namespace mod_smartspe;

use mod_smartspe\handler\notification_handler;

defined('MOODLE_INTERNAL') || die();

class observer
{

    public static function on_attempt_submitted($event)
    {
        $userid = $userid = $event->userid;

        // Send email
        $notification = new notification_handler();
        $notification->noti_eval_submitted($userid);
    }

    public static function on_evaluation_finished($event)
    {

        
    }
}
