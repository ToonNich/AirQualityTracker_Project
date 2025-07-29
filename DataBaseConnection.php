<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "air"; // Use your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error); // Log error to server log
    die("Connection failed, please try again later.");
}
?>
