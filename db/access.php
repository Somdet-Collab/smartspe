<?php
/**
 * Version information
 *
 * @package   mod_sentiment_analysis
 * @author Somdet Pidtanung
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'mod/sentiment_analysis:addinstance' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/course:manageactivities'
    ),

    'mod/sentiment_analysis:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'user' => CAP_ALLOW,
        )
    ),
);

?>