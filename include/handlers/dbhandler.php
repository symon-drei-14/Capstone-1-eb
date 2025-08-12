<?php
$host = "localhost";
$db_name = "capstonedb"; 
$username = "root";
$password = ""; 

$conn = new mysqli($host, $username, $password,$db_name);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit();
}
?>