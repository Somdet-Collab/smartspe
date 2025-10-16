<?php

namespace mod_smartspe;

use cm_info;
use coding_exception;
use context;
use context_module;
use moodle_exception;
use moodle_url;
use question_bank;
use stdClass;

require_once($CFG->dirroot . '/mod/quiz/classes/quiz_settings.php');

class smartspe_quiz_setting extends \mod_quiz\quiz_settings
{
    /**
     * Constructor, assuming we already have the necessary data loaded.
     *
     * @param stdClass $quiz the row from the quiz table.
     * @param stdClass $cm the course_module object for this quiz.
     * @param stdClass $course the row from the course table for the course we belong to.
     * @param bool $getcontext intended for testing - stops the constructor getting the context.
     */
    public function __construct($quiz, $cm, $course)
    {
        parent::__construct($quiz, $cm, $course);
    }

    

    function smartspe_quiz_add_settings_form_fields($quizform, $mform) 
    {
        // Add a date/time selector for submission deadline.
        $mform->addElement('date_time_selector', 'submissiondate', 
                get_string('submissiondate', 'mod_smartspe'));
                
        $mform->setDefault('submissiondate', time() + 7*24*60*60); // default: one week from now
        
        $mform->setType('submissiondate', PARAM_INT);
    }

}