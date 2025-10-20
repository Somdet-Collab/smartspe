<?php
require('../../config.php');
require_login();

$PAGE->set_url(new moodle_url('/mod/smartspe/view.php'));
$PAGE->set_title('Sentiment Analysis Test');
$PAGE->set_heading('Sentiment Analysis Test');

$current = $DB->get_record('smartspe', ['id' => $instanceid]);
$section = required_param('section', PARAM_INT);
$context = \context_system::instance();
$cm = get_coursemodule_from_instance('smartspe', $instanceid, $courseid);
$course = $DB->get_record('course', ['id' => $courseid]);

global $DB, $OUTPUT;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teamcode = required_param('teamcode', PARAM_ALPHANUMEXT);
    $teamname = required_param('teamname', PARAM_TEXT);
    $courseid = required_param('courseid', PARAM_INT);

    $record = new stdClass();
    $record->teamcode = $teamcode;
    $record->name = $teamname;
    $record->course = $courseid;

    $DB->insert_record('smartspe_team', $record);

    echo $OUTPUT->notification("Team <strong>{$teamname}</strong> added!", 'notifysuccess');
}

// Output page header
echo $OUTPUT->header();
?>

<h3>Add Team</h3>
<form method="post">
    <label>Team Code:</label><br>
    <input type="text" name="teamcode" required><br><br>

    <label>Team Name:</label><br>
    <input type="text" name="teamname" required><br><br>

    <label>Course ID:</label><br>
    <input type="number" name="courseid" required><br><br>

    <input type="submit" value="Add Team">
</form>

<hr>
<h3>Existing Teams</h3>
<?php
$teams = $DB->get_records('smartspe_team');

if ($teams) {
    echo "<ul>";
    foreach ($teams as $team) {
        echo "<li>[{$team->teamcode}] {$team->name} (Course: {$team->course})</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No teams yet.</p>";
}

echo $OUTPUT->footer();
