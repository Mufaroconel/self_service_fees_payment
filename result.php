<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session if needed
session_start();

// Check if Paynow sent a poll URL
if (!isset($_POST['pollurl'])) {
    echo "Poll URL not received from Paynow.";
    exit();
}

$pollUrl = $_POST['pollurl'];

// Poll Paynow for the result
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $pollUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);

// Parse response string into an array
parse_str($response, $result);

// Print out nicely
echo "<h2>🎉 Paynow Payment Result</h2>";
echo "<pre>" . print_r($result, true) . "</pre>";
?>
