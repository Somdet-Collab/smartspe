<?php

use mod_smartspe\classes\event\duration_controller;
use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Form for creating or editing a SmartSpe activity
 *
 * @package    mod_smartspe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_smartspe_mod_form extends moodleform_mod
{

    public function definition() 
    {
        global $CFG, $COURSE;

        $mform = $this->_form;

        // Activity name.
        $mform->addElement('text', 'name', get_string('smartspe_name', 'mod_smartspe'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addHelpButton('name', 'smartspe_name', 'mod_smartspe');

        // Intro / description.
        $this->standard_intro_elements(get_string('smartspe_intro', 'mod_smartspe'));

        // Teacher choose questionbank name
        $mform->addElement('text', 'questionbankname', get_string('questionbankname', 'mod_smartspe'));
        $mform->setType('questionbankname', PARAM_TEXT);
        $mform->addRule('questionbankname', null, 'required', null, 'client');

        // --- Submission period section ---
        $mform->addElement('header', 'timinghdr', get_string('submissionperiod', 'mod_smartspe'));

        // Start date.
        $mform->addElement('date_time_selector', 'startdate', get_string('submissionstart', 'mod_smartspe'), ['optional' => false]);
        $mform->setDefault('startdate', time());

        // End date (deadline).
        $mform->addElement('date_time_selector', 'enddate', get_string('submissionend', 'mod_smartspe'), ['optional' => false]);
        $mform->setDefault('enddate', time() + 7 * 24 * 60 * 60); // default 1 week later

        // Standard course module elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Extra validation to ensure start date < end date.
     */
    public function validation($data, $files) 
    {
        $errors = parent::validation($data, $files);

        // Check using your duration_controller.
        try 
        {
            $duration = new duration_controller
            (
                $data['startdate'],
                $data['enddate']
            );
        } 
        catch (moodle_exception $e) 
        {
            $errors['startdate'] = $e->getMessage();
            $errors['enddate'] = $e->getMessage();
        }

        return $errors;
    }
}
