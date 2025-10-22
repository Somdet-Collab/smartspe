<?php

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->dirroot . '/mod/smartspe/mod_form.php');

use mod_smartspe\output\view_page;

global $DB, $USER;

// Params
$id = required_param('id', PARAM_INT);

// Setup course + context
$cm = get_coursemodule_from_id('smartspe', $id, 0, false, MUST_EXIST);
$sectionid = $cm->section;
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = \context_module::instance($cm->id);
$smartspe = $DB->get_record('smartspe', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

// Get teacher form data
$mform = new mod_smartspe_mod_form($smartspe->id, $sectionid, $cm, $course); 
$data = $mform->get_data();
$questionids = $data->questionids;

if ($questionids)
{
    if (is_string($questionids))
        $qids = explode(',', $questionids);
    elseif(is_array($questionids))
        $qids = $questionids;
}
else
{
    $qids = [];
    echo "Error: No questionids returned!!<br>"; 
}

// Page setup
$PAGE->set_url('/mod/smartspe/view.php', ['id' => $id]);
$PAGE->set_title(format_string($smartspe->name));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

// Use renderable and Mustache
$page = new view_page($cm, $smartspe, $qids);
echo $OUTPUT->header();
echo $OUTPUT->render($page);
echo $OUTPUT->footer();
?>