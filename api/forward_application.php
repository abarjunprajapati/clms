<?php
/**
 * forward_application.php
 * Contractor forwards application to welfare for verification
 * Legacy verification action. Canonical workflow keeps submitted until approval.
 */
require_once 'api_helper.php';
require_once 'workflow_action.php';
workflowUpdate('submitted');

