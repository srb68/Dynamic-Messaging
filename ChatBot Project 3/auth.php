<?php
// Suppress all errors - only output JSON
error_reporting(0);
ini_set('display_errors', 0);

// Clean any previous output
if (ob_get_level()) ob_clean();
ob_start();

// Start session
session_start();

// Database configuration - directly in this file
$host = 'sql.njit.edu'; 
$dbname = 'srb68';    
$db_username = 'srb68'; 
$db_password = 'SBsql@2025';

// Set JSON header
header('Content-Type: application/json');

$response = array('success' => false, 'message' => 'Invalid request');

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        // Validate input
        if (empty($username) || empty($password)) {
            $response['message'] = 'Username and password are required';
        } else if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $response['message'] = 'Username must be 3-20 alphanumeric characters';
        } else {
            // Create database connection
            $conn = new mysqli($host, $db_username, $db_password, $dbname);
            
            if ($conn->connect_error) {
                $response['message'] = 'Database connection failed';
            } else {
                $conn->set_charset("utf8mb4");
                
                // REGISTER ACTION
                if ($action == 'register') {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("ss", $username, $hashed_password);
                        
                        if ($stmt->execute()) {
                            $_SESSION['username'] = $username;
                            $response['success'] = true;
                            $response['message'] = 'Registration successful';
                        } else {
                            $response['message'] = 'Username already exists';
                        }
                        $stmt->close();
                    } else {
                        $response['message'] = 'Database error';
                    }
                }
                // LOGIN ACTION
                else if ($action == 'login') {
                    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
                    if ($stmt) {
                        $stmt->bind_param("s", $username);
                        $stmt->execute();
                        
                        $stored_password = '';
                        $stmt->bind_result($stored_password);
                        $stmt->fetch();
                        
                        if (!empty($stored_password)) {
                            if (password_verify($password, $stored_password)) {
                                $_SESSION['username'] = $username;
                                $response['success'] = true;
                                $response['message'] = 'Login successful';
                            } else {
                                $response['message'] = 'Invalid password';
                            }
                        } else {
                            $response['message'] = 'User not found';
                        }
                        $stmt->close();
                    } else {
                        $response['message'] = 'Database error';
                    }
                }
                
                $conn->close();
            }
        }
    }
} catch (Exception $e) {
    $response['message'] = 'Server error';
}

// Clear buffer and output JSON
ob_end_clean();
echo json_encode($response);
exit;
?>