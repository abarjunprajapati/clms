<?php
/**
 * approve_application.php
 * Welfare authority approves application
 * Transitions: submitted -> approved
 */
require_once 'api_helper.php';
require_once 'workflow_action.php';
workflowUpdate('approved');

