<?php
// config.php

// Paynow credentials
define('PAYNOW_ID', '20710');
define('PAYNOW_KEY', '3b3d75b7-8ad2-4a55-9dfb-34d691822b1a');

// URL to redirect to after payment
$return_url = "http://localhost/self_service_system/return.php?PHPSESSID=" . session_id();

// URL for result callback after payment
$result_url = 'http://localhost/self_service_system/result.php';

// You can later access these constants in your main script
?>
