<?php
session_start();

$host = 'sql.njit.edu'; 
$dbname = 'srb68';    
$db_username = 'srb68'; 
$db_password = 'SBsql@2025';

function getDBConnection() {
    global $host, $dbname, $db_username, $db_password;
    
    try {
        $conn = new mysqli($host, $db_username, $db_password, $dbname);
        
        if ($conn->connect_error) {
            die('Connection failed: ' . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        
        return $conn;
        
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}
?>