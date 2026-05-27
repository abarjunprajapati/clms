<?php
/**
 * reject_application.php
 * Rejects application at any stage
 * Transitions: any → rejected
 */
require_once 'api_helper.php';
require_once 'workflow_action.php';
workflowUpdate('rejected');

