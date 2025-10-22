<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_smartspe\smartspe_quiz_manager;

global $DB, $USER;

// Parameters
$id = required_param('id', PARAM_INT); // Course module ID

// Setup course + context
$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = \context_module::instance($cm->id);
$smartspe = $DB->get_record('smartspe', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$mform = new mod_smartspe_mod_form($smartspe->id, $sectionid, $cm, $course); 
$data = $mform->get_data();

//Get question ids teacher has selected
$questionids = $data->questionids;

// Page setup
$PAGE->set_url('/mod/smartspe/view.php', ['id' => $id]);
$PAGE->set_title(format_string($smartspe->name));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

// Display introduction and start button
echo $OUTPUT->header();

echo $OUTPUT->box_start('generalbox center');
echo html_writer::tag('h2', format_string($smartspe->name));
echo format_text($smartspe->intro, $smartspe->introformat);

$starturl = new moodle_url('/mod/smartspe/attempt.php', [
    'id' => $id,
    'memberindex' => 0, // Start with first member
    'questionids' => $questionids
]);

echo html_writer::start_tag('div', ['class' => 'center p-3']);
echo html_writer::link($starturl, get_string('startattempt', 'mod_smartspe'), [
    'class' => 'btn btn-primary'
]);
echo html_writer::end_tag('div');
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
