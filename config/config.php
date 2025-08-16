<?php

// Database configuration for your local environment
$host = 'localhost'; 
$dbname = 'health_sys'; // The name of the database you created
$username = 'root'; // Your database username
$password = ''; 

// Attempt to connect to MySQL using PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // You can add a success message for testing purposes, but remove it in production
    //echo "Connected successfully";
} catch(PDOException $e) {
// If the connection fails, terminate the script and display the error
    die("ERROR: Could not connect. " . $e->getMessage());
}

// SMTP configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'notehub11@gmail.com');
define('SMTP_PASSWORD', 'eere knum lpxq wzli');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

// Gemini API Key
define('GEMINI_API_KEY', 'AIzaSyAENpIPg_f8VjR9UHjG-wPvOEjSzqtIeKI');

?>
