<?php
session_start();
$_SESSION['user_id'] = 2; // Contractor
$_SESSION['role'] = 'contractor';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Mock getJsonInput() since we can't easily mock php://input
function getJsonInputMock() {
    return ['search_term' => '1234'];
}
require_once 'api/api_helper.php';

// Override getJsonInput
if (function_exists('runkit7_function_redefine')) {
    // Too complex
}
