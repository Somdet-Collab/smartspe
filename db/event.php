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
        'eventname'   => '\mod_smartspe\event\download',
        'callback'    => 'mod_smartspe\observer::download',
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
