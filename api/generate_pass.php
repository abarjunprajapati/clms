<?php
/**
 * generate_pass.php
 * Final pass generation (called after ACC final approval)
 * Transitions: final_approved → pass_generated
 */
require_once 'api_helper.php';
require_once 'workflow_action.php';
workflowUpdate('permanent_pass_generated');

