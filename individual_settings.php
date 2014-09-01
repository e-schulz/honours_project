<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

///REQUIRES AND ERROR MESSAGES

require_once("../../config.php");
require_once("locallib.php");
require_once("individual_settings_form.php");

global $DB;

//create some student data.
/*$data = new object();
$data->userid = 3;
$data->categoryid = 7;
$data->value = 40;
$data->timestamp = time();
$DB->insert_record('block_risk_monitor_cat_risk', $data);*/

//Teacher must be logged in
require_login();

//Get the ID of the teacher
$userid = required_param('userid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

//Error- there is no user associated with the passed param
if (!$getuser = $DB->get_record('user', array('id' => $userid))) {
    print_error('no_user', 'block_risk_monitor', '', $userid);
}

//Error - the user trying to access this instance is the wrong one
if (!($USER->id == $userid)) {
    print_error('wrong_user', 'block_risk_monitor', '', $userid);
}

$context = context_user::instance($userid);

//Set the page parameters
$blockname = get_string('pluginname', 'block_risk_monitor');
$header = get_string('settings', 'block_risk_monitor');

$PAGE->navbar->add($blockname);
$PAGE->navbar->add($header);

$PAGE->set_context($context);
$PAGE->set_title($blockname . ': '. $header);
$PAGE->set_heading($blockname . ': '.$header);
$PAGE->set_url('/blocks/risk_monitor/individual_settings.php?userid='.$userid."&courseid=".$courseid.'&courseid='.$courseid);
$PAGE->set_pagetype($blockname);
$PAGE->set_pagelayout('standard');

//$DB->delete_records('block_risk_monitor_cat_risk');
//block_risk_monitor_update_default_rules();
//$DB->delete_records('block_risk_monitor_rule_inst_type');
//block_risk_monitor_update_default_rules();

//Create the body
$body = '';

//$DB->delete_records('block_risk_monitor_answer');
//block_risk_monitor_clear_risks(time()-10000000);
//$body .= block_risk_monitor_calculate_risks();
    //Link to edit courses NOTE: THIS WILL NOW BE BROKEN.
    //$body .= html_writer::link (new moodle_url('edit_courses.php', array('userid' => $USER->id)), get_string('edit_courses','block_risk_monitor')).'<br><br>';
            
    //Description for add or delete courses
    //$body .= html_writer::tag('div', get_string('edit_courses_text','block_risk_monitor').'<br><br>');

    //Link to edit categories and rules
    $body .= html_writer::link (new moodle_url('edit_categories_rules.php', array('userid' => $USER->id, 'courseid' => $courseid)), get_string('edit_categories_rules','block_risk_monitor')).'<br><br>';
            
    //Description for add or delete
    $body .= html_writer::tag('div', get_string('edit_categories_rules_description','block_risk_monitor').'<br><br>');

    //Link to edit interventions
    $body .= html_writer::link (new moodle_url('view_interventions.php', array('userid' => $USER->id, 'courseid' => $courseid)), get_string('edit_interventions','block_risk_monitor')).'<br><br>';
            
    //Description for add or delete
    $body .= html_writer::tag('div', get_string('edit_interventions_description','block_risk_monitor').'<br><br>');
///RENDERING THE HTML

//Render the HTML
echo $OUTPUT->header();
echo $OUTPUT->heading($blockname);

//echo html_writer::start_tag('div', array('class' => 'no-overflow'));

//display the settings form
//echo block_risk_monitor_get_tabs_html($userid, true);
echo block_risk_monitor_get_top_tabs('settings', $courseid);

echo $body;

echo $OUTPUT->footer();