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

//Teacher must be logged in
require_login();

//Get the ID of the teacher
$userid = required_param('userid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$categoryid = required_param('categoryid', PARAM_INT);
$page = optional_param('page', -1, PARAM_INT);
$questionnaireid = optional_param('questionnaireid', -1, PARAM_INT);
$scoring_method = optional_param('scoringmethod', -1, PARAM_INT);

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

//Add new or existing links.

$PAGE->navbar->add($blockname);
$PAGE->navbar->add($header);

$PAGE->set_context($context);
$PAGE->set_title($blockname . ': '. $header);
$PAGE->set_heading($blockname . ': '.$header);
$PAGE->set_url('/blocks/risk_monitor/create_custom_rule.php?userid='.$userid.'&courseid='.$courseid);
$PAGE->set_pagetype($blockname);
$PAGE->set_pagelayout('standard');

if($page == -1 || $page == 1) {
   $questionnaire_form =  new individual_settings_form_create_questionnaire_general_page('create_custom_rule.php?userid='.$userid.'&courseid='.$courseid."&categoryid=".$categoryid);
}
else if($page == 2) {
    $questionnaire_form =  new individual_settings_form_create_questionnaire_question_page('create_custom_rule.php?userid='.$userid.'&courseid='.$courseid."&categoryid=".$categoryid."&scoringmethod=".$scoring_method."&questionnaireid=".$questionnaireid."&page=".$page, array('scoringmethod' => $scoring_method));
}
else if($page == 3) {
    $all_questions = $DB->get_records('block_risk_monitor_question', array('custruleid' => $questionnaireid));
    $min_total = $max_total = $total_questions = 0;
    foreach($all_questions as $question) {
        $total_questions++;
        $options = $DB->get_records('block_risk_monitor_option', array('questionid' => $question->id));
        $values = array();
        foreach($options as $option) {
            $values[] = $option->value;
        }
        $min_total += min($values);
        $max_total += max($values);
    }    
    $new_rule = new object();
    $new_rule->id = $questionnaireid;
    $new_rule->min_score = $min_total;
    $new_rule->max_score = $max_total;    
    $DB->update_record('block_risk_monitor_cust_rule', $new_rule);  
    $questionnaire_form =  new individual_settings_form_create_questionnaire_final_page('create_custom_rule.php?userid='.$userid.'&courseid='.$courseid."&categoryid=".$categoryid."&scoringmethod=".$scoring_method."&questionnaireid=".$questionnaireid."&page=".$page, array('minscore' => $min_total, 'maxscore' => $max_total, 'totalquestions' => $total_questions));
}
$heading = "New questionnaire";

 /*       $new_rule = new object();
        $new_rule->id = 4;
        $new_rule->min_score = 0;
        $new_rule->max_score = 30;
        $new_rule->low_mod_risk_cutoff = 22;
        $new_rule->mod_high_risk_cutoff = 11;
        $DB->update_record('block_risk_monitor_cust_rule', $new_rule);

if($num_questions !== -1) {
    $heading = "Questions";
}
else {
    $heading = "New custom rule";
}*/
//Get all the categories and courses.


if($questionnaire_form->is_cancelled()) {
    if($questionnaireid != -1) {
        if($DB->record_exists('block_risk_monitor_cust_rule', array('id' => $questionnaireid))) {
            $DB->delete_records('block_risk_monitor_cust_rule', array('id' => $questionnaireid));
        }
        
        if($questions = $DB->get_records('block_risk_monitor_question', array('custruleid' => $questionnaireid))) {
            foreach($questions as $question) {
                if($DB->record_exists('block_risk_monitor_option', array('questionid' => $question->id))) {
                    $DB->delete_records('block_risk_monitor_option', array('questionid' => $question->id));
                }
                $DB->delete_records('block_risk_monitor_question', array('custruleid' => $questionnaireid));
            }
        }
    }
    redirect(new moodle_url('edit_categories_rules.php', array('userid' => $USER->id, 'courseid' => $courseid))); 
}
else if ($fromform = $questionnaire_form->get_data()) {
    
    if($page == -1 || $page == 1) {
        $new_rule = new object();
        $new_rule->name = $fromform->rule_name_text;
        $new_rule->description = $fromform->rule_description_text;
        $new_rule->userid = $userid;        
        $new_rule->min_score = 0;
        $new_rule->max_score = 100;
        $new_rule->low_mod_risk_cutoff = MODERATE_RISK;
        $new_rule->mod_high_risk_cutoff = HIGH_RISK;       
        $new_rule->timestamp = time();
        $new_rule_id = $DB->insert_record('block_risk_monitor_cust_rule', $new_rule);       
        
        redirect(new moodle_url('create_custom_rule.php', array('userid' => $USER->id, 'courseid' => $courseid, 'page' => 2, 'categoryid' => $categoryid, 'questionnaireid' => $new_rule_id, 'scoringmethod' => $fromform->scoring_method))); 
    }
    else if($page == 2) {
            //Create the question
            $new_question = new object();
            $new_question->question = $fromform->question_text;
            $new_question->custruleid = $questionnaireid;
            $new_question_id = $DB->insert_record('block_risk_monitor_question', $new_question);

            //Create the options
            for($j=0; $j<5; $j++) {
                $text_identifier = 'option_text'.$j;
                $value_identifier = 'option_value'.$j;
                if($fromform->$text_identifier != "") {
                    $new_option1 = new object();
                    $new_option1->label = $fromform->$text_identifier;
                    $new_option1->value = $fromform->$value_identifier;
                    $new_option1->questionid = $new_question_id;
                    $DB->insert_record('block_risk_monitor_option', $new_option1);
                }
            }
            
            if(isset($fromform->submit_another)) {
                redirect(new moodle_url('create_custom_rule.php', array('userid' => $USER->id, 'courseid' => $courseid, 'page' => 2, 'categoryid' => $categoryid, 'questionnaireid' => $questionnaireid, 'scoringmethod' => $scoring_method)));            
            }
            else if(isset($fromform->submit_save) && $scoring_method == 1) {
                redirect(new moodle_url('create_custom_rule.php', array('userid' => $USER->id, 'courseid' => $courseid, 'page' => 3, 'categoryid' => $categoryid, 'questionnaireid' => $questionnaireid, 'scoringmethod' => $scoring_method)));                            
            }
            else if(isset($fromform->submit_save)) {
                $custom_rule = $DB->get_record('block_risk_monitor_cust_rule', array('id' => $questionnaireid));
                $total_rules = count(block_risk_monitor_get_rules(intval($categoryid)))+1;
                $weighting_default = 100/intval($total_rules);
                block_risk_monitor_adjust_weightings_rule_added($categoryid, (100-floatval($weighting_default)));
                
                $rule_inst = new object();
                $rule_inst->name = $custom_rule->name;
                $rule_inst->description = $custom_rule->description;
                $rule_inst->weighting = $weighting_default;        
                $rule_inst->timestamp = time();
                $rule_inst->categoryid = $categoryid;
                $rule_inst->ruletype = 2;
                $rule_inst->custruleid = $custom_rule->id;       
                $new_rule_id = $DB->insert_record('block_risk_monitor_rule_inst', $rule_inst);                   
                redirect(new moodle_url('edit_categories_rules.php', array('userid' => $USER->id, 'courseid' => $courseid)));                 
            }
    }
    else if($page == 3) {
        $new_rule = new object();
        $new_rule->id = $questionnaireid;
        $new_rule->low_mod_risk_cutoff = $fromform->medrangebegin;
        $new_rule->mod_high_risk_cutoff = $fromform->highrangebegin;
        $DB->update_record('block_risk_monitor_cust_rule', $new_rule);  
        $custom_rule = $DB->get_record('block_risk_monitor_cust_rule', array('id' => $questionnaireid));
        
        $total_rules = count(block_risk_monitor_get_rules(intval($categoryid)))+1;
        $weighting_default = 100/intval($total_rules);
        block_risk_monitor_adjust_weightings_rule_added($categoryid, (100-floatval($weighting_default)));
                
        $rule_inst = new object();
        $rule_inst->name = $custom_rule->name;
        $rule_inst->description = $custom_rule->description;
        $rule_inst->weighting = $weighting_default;        
        $rule_inst->timestamp = time();
        $rule_inst->categoryid = $categoryid;
        $rule_inst->ruletype = 2;
        $rule_inst->custruleid = $custom_rule->id;       
        $DB->insert_record('block_risk_monitor_rule_inst', $rule_inst);                           
        redirect(new moodle_url('edit_categories_rules.php', array('userid' => $USER->id, 'courseid' => $courseid)));      
    }
}


echo $OUTPUT->header();
echo $OUTPUT->heading($blockname);

echo block_risk_monitor_get_top_tabs('settings', $courseid);
echo $OUTPUT->heading($heading);

$questionnaire_form->display();

if($page == 2 || $page == 3) {
    //Display a preview.
    if($questions = $DB->get_records('block_risk_monitor_question', array('custruleid' => $questionnaireid))) {
        foreach($questions as $question) {
            echo $OUTPUT->box_start();
            echo $question->question."<br>";
            $options = $DB->get_records('block_risk_monitor_option', array('questionid' => $question->id));
            foreach($options as $option) {
                echo "<li>".$option->label."</li>";
            }
            echo $OUTPUT->box_end();
        }
    }
}
//echo $back_to_categories;
echo $OUTPUT->footer();