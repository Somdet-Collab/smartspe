<?php
require_once(__DIR__ . '/../../config.php');
require_login();

global $DB, $USER;

$PAGE->set_url(new moodle_url('/mod/folder/test_db_crud.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title("Test Plugin Database CRUD");
echo $OUTPUT->header();

// Get parameters
$action   = optional_param('action', '', PARAM_ALPHA);
$table    = optional_param('table', '', PARAM_ALPHA);
$id       = optional_param('id', 0, PARAM_INT);

// Common fields
$params = [
    'teamcode' => optional_param('teamcode', '', PARAM_TEXT),
    'name'     => optional_param('name', '', PARAM_TEXT),
    'course'   => optional_param('course', 0, PARAM_INT),
    'student'  => optional_param('student', 0, PARAM_INT),
    'evaluator'=> optional_param('evaluator', 0, PARAM_INT),
    'evaluatee'=> optional_param('evaluatee', 0, PARAM_INT),
    'q1'       => optional_param('q1', 0, PARAM_FLOAT),
    'q2'       => optional_param('q2', 0, PARAM_FLOAT),
    'q3'       => optional_param('q3', 0, PARAM_FLOAT),
    'q4'       => optional_param('q4', 0, PARAM_FLOAT),
    'q5'       => optional_param('q5', 0, PARAM_FLOAT),
    'comment'  => optional_param('comment', '', PARAM_TEXT),
];

// ---------------- Handle Actions ---------------- //
if ($action === 'delete' && $table && $id) {
    $DB->delete_records($table, ['id'=>$id]);
    redirect($PAGE->url);
}

if (($action === 'insert' || $action === 'update') && $table) {
    $record = new stdClass();
    if ($action === 'update') $record->id = $id;

    switch($table) {
        case 'team':
            $record->teamcode = $params['teamcode'];
            $record->name = $params['name'];
            $record->course = $params['course'];
            break;
        case 'team_member':
            $record->teamid = $params['course']; // Using course param as teamid for simplicity
            $record->studentID = $params['student'];
            break;
        case 'evaluation':
            $record->course = $params['course'];
            $record->evaluator = $params['evaluator'];
            $record->evaluatee = $params['evaluatee'];
            $record->q1 = $params['q1'];
            $record->q2 = $params['q2'];
            $record->q3 = $params['q3'];
            $record->q4 = $params['q4'];
            $record->q5 = $params['q5'];
            $record->comment = $params['comment'];
            break;
        case 'sentiment_analysis':
            $record->evaluationid = $params['evaluatee']; // reuse param
            $record->sentimentscore = $params['q1'];
            $record->polarity = $params['comment'];
            break;
    }

    if ($action === 'insert') {
        $DB->insert_record($table, $record);
    } else {
        $DB->update_record($table, $record);
    }
    redirect($PAGE->url);
}

// ---------------- Display Tables ---------------- //
$tables = ['team','team_member','evaluation','sentiment_analysis'];

foreach ($tables as $tbl) {
    echo html_writer::tag('h3', ucfirst($tbl));
    $records = $DB->get_records($tbl);

    $table = new html_table();
    $table->head = array_merge(array_keys((array)reset($records)), ['Actions']);

    foreach ($records as $rec) {
        $row = array_values((array)$rec);

        // Action buttons
        $row[] = html_writer::link(
            new moodle_url($PAGE->url, ['action'=>'delete','table'=>$tbl,'id'=>$rec->id]),
            'Delete', ['class'=>'btn btn-danger']
        ) . ' ' . html_writer::link(
            new moodle_url($PAGE->url, ['action'=>'edit','table'=>$tbl,'id'=>$rec->id]),
            'Edit', ['class'=>'btn btn-warning']
        );

        $table->data[] = $row;
    }

    echo html_writer::table($table);

    // If editing
    $editing = false;
    $editrecord = null;
    if ($action === 'edit' && $table === $tbl && $id) {
        $editrecord = $DB->get_record($tbl, ['id'=>$id]);
        $editing = true;
    }

    // Insert / Edit Form
    echo html_writer::start_tag('form', ['method'=>'post']);
    echo html_writer::empty_tag('input', ['type'=>'hidden','name'=>'table','value'=>$tbl]);
    echo html_writer::empty_tag('input', ['type'=>'hidden','name'=>'action','value'=>$editing ? 'update' : 'insert']);
    if ($editing) {
        echo html_writer::empty_tag('input',['type'=>'hidden','name'=>'id','value'=>$editrecord->id]);
    }

    // Fields per table
    switch ($tbl) {
        case 'team':
            echo 'Team Code: ' . html_writer::empty_tag('input',['type'=>'text','name'=>'teamcode','value'=>($editing?$editrecord->teamcode:'')]).' ';
            echo 'Name: ' . html_writer::empty_tag('input',['type'=>'text','name'=>'name','value'=>($editing?$editrecord->name:'')]).' ';
            echo 'Course ID: ' . html_writer::empty_tag('input',['type'=>'number','name'=>'course','value'=>($editing?$editrecord->course:'')]).' ';
            break;
        case 'team_member':
            echo 'Team ID: ' . html_writer::empty_tag('input',['type'=>'number','name'=>'course','value'=>($editing?$editrecord->teamid:'')]).' ';
            echo 'Student ID: ' . html_writer::empty_tag('input',['type'=>'number','name'=>'student','value'=>($editing?$editrecord->studentID:'')]).' ';
            break;
        case 'evaluation':
            echo 'Course ID: ' . html_writer::empty_tag('input',['type'=>'number','name'=>'course','value'=>($editing?$editrecord->course:'')]).' ';
            echo 'Evaluator ID: ' . html_writer::empty_tag('input',['type'=>'number','name'=>'evaluator','value'=>($editing?$editrecord->evaluator:'')]).' ';
            echo 'Evaluatee ID: ' . html_writer::empty_tag('input',['type'=>'number','name'=>'evaluatee','value'=>($editing?$editrecord->evaluatee:'')]).' ';
            echo 'Q1-Q5: ' 
                . html_writer::empty_tag('input',['type'=>'number','name'=>'q1','min'=>0,'max'=>5,'value'=>($editing?$editrecord->q1:'')]).' '
                . html_writer::empty_tag('input',['type'=>'number','name'=>'q2','min'=>0,'max'=>5,'value'=>($editing?$editrecord->q2:'')]).' '
                . html_writer::empty_tag('input',['type'=>'number','name'=>'q3','min'=>0,'max'=>5,'value'=>($editing?$editrecord->q3:'')]).' '
                . html_writer::empty_tag('input',['type'=>'number','name'=>'q4','min'=>0,'max'=>5,'value'=>($editing?$editrecord->q4:'')]).' '
                . html_writer::empty_tag('input',['type'=>'number','name'=>'q5','min'=>0,'max'=>5,'value'=>($editing?$editrecord->q5:'')]).' ';
            echo 'Comment: ' . html_writer::empty_tag('input',['type'=>'text','name'=>'comment','value'=>($editing?$editrecord->comment:'')]).' ';
            break;
        case 'sentiment_analysis':
            echo 'Evaluation ID: ' . html_writer::empty_tag('input',['type'=>'number','name'=>'evaluatee','value'=>($editing?$editrecord->evaluationid:'')]).' ';
            echo 'Score: ' . html_writer::empty_tag('input',['type'=>'number','name'=>'q1','step'=>'0.01','value'=>($editing?$editrecord->sentimentscore:'')]).' ';
            echo 'Polarity: ' . html_writer::empty_tag('input',['type'=>'text','name'=>'comment','value'=>($editing?$editrecord->polarity:'')]).' ';
            break;
    }

    echo html_writer::empty_tag('input',['type'=>'submit','value'=>($editing?'Update':'Insert'),'class'=>'btn btn-primary']);
    echo html_writer::end_tag('form');
}

echo $OUTPUT->footer();
