<?php
// API Keys - Replace with your own keys
define('NASA_API_KEY', 'YOUR_NASA_API_KEY_HERE');
define('OPENWEATHER_API_KEY', 'YOUR_OPENWEATHER_API_KEY_HERE');
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');

// Timezone
date_default_timezone_set('Europe/Istanbul');

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Error Reporting (disable in production)
error_reporting(0);
ini_set('display_errors', 0);
?>
