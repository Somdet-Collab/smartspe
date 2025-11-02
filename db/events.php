<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\mod_smartspe\event\attempt_start',
        'callback'    => 'mod_smartspe\observer::attempt_start',
        'internal'    => false,
        'priority'    => 9999,
    ],
    [
        'eventname'   => '\mod_smartspe\event\attempt_finish',
        'callback'    => 'mod_smartspe\observer::attempt_finish',
        'internal'    => false,
        'priority'    => 9999,
    ],
    [
        'eventname'   => '\mod_smartspe\event\file_download',
        'callback'    => 'mod_smartspe\observer::file_download',
        'internal'    => false,
        'priority'    => 9999,
    ],
    [
        'eventname'   => '\mod_smartspe\event\evaluation_after_duedate',
        'callback'    => 'mod_smartspe\observer::after_duedate',
        'internal'    => false,
        'priority'    => 9999,
    ],
];
