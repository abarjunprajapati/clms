<?php
/**
 * mark_verified.php
 * PIO marks application as verified
 * Transitions: pass_requested -> pass_approved
 */
require_once 'api_helper.php';
require_once 'workflow_action.php';
workflowUpdate('pass_approved');

