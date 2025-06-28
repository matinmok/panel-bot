<?php
// Start the session
session_start();

// Set the content type to JSON
header('Content-Type: application/json');

// Function to log user access
function logUserAccess($username) {
    $logFile = 'user_access_log.txt'; // File to store logs
    $timestamp = date("Y-m-d H:i:s"); // Get current timestamp
    $logEntry = "$timestamp - User: $username\n"; // Create log entry

    // Append the log entry to the file
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Check if the user is logged in
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username']; // Get the logged-in username
    logUserAccess($username); // Log the access
    echo json_encode(['status' => 'success', 'message' => 'User access logged successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
}
?>
