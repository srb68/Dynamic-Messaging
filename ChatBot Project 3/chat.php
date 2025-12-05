<?php
// Suppress all errors - only output JSON
error_reporting(0);
ini_set('display_errors', 0);

// Clean output buffer
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Start session FIRST
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
$host = 'sql.njit.edu'; 
$dbname = 'srb68';    
$db_username = 'srb68'; 
$db_password = 'SBsql@2025';

header('Content-Type: application/json');

$response = array('success' => false, 'message' => 'Invalid request');

try {
    // Check if user is logged in
    if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
        $response['message'] = 'Not logged in';
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    $current_user = $_SESSION['username'];
    
    // Create database connection
    $conn = new mysqli($host, $db_username, $db_password, $dbname);
    
    if ($conn->connect_error) {
        $response['message'] = 'Database connection failed';
        ob_end_clean();
        echo json_encode($response);
        exit;
    }
    
    $conn->set_charset("utf8mb4");

    // HANDLE POST REQUEST (Send Message)
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        if ($action == 'send') {
            $receiver = isset($_POST['receiver']) ? trim($_POST['receiver']) : '';
            $message = isset($_POST['message']) ? trim($_POST['message']) : '';
            
            if (empty($receiver)) {
                $response['message'] = 'Please specify a receiver';
            } else if (empty($message)) {
                $response['message'] = 'Message cannot be empty';
            } else if ($receiver == $current_user) {
                $response['message'] = 'Cannot send to yourself';
            } else {
                // Check if receiver exists
                $stmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $receiver);
                    $stmt->execute();
                    
                    $found_username = '';
                    $stmt->bind_result($found_username);
                    $stmt->fetch();
                    $stmt->close();
                    
                    if (empty($found_username)) {
                        $response['message'] = 'User does not exist';
                    } else {
                        // Insert message
                        $stmt = $conn->prepare("INSERT INTO messages (sender_username, receiver_username, message) VALUES (?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param("sss", $current_user, $receiver, $message);
                            
                            if ($stmt->execute()) {
                                $response['success'] = true;
                                $response['message'] = 'Message sent';
                            } else {
                                $response['message'] = 'Failed to send message';
                            }
                            $stmt->close();
                        }
                    }
                }
            }
        }
    }
    // HANDLE GET REQUEST (Fetch Messages) - FIXED TO USE bind_result()
    else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        if ($action == 'fetch') {
            $chat_with = isset($_GET['chat_with']) ? trim($_GET['chat_with']) : '';
            
            if (empty($chat_with)) {
                $response['message'] = 'Chat partner not specified';
            } else {
                $stmt = $conn->prepare("
                    SELECT sender_username, receiver_username, message, created_at 
                    FROM messages 
                    WHERE (sender_username = ? AND receiver_username = ?) 
                       OR (sender_username = ? AND receiver_username = ?)
                    ORDER BY created_at ASC
                ");
                
                if ($stmt) {
                    $stmt->bind_param("ssss", $current_user, $chat_with, $chat_with, $current_user);
                    $stmt->execute();
                    
                    // Use bind_result instead of get_result
                    $sender = '';
                    $receiver = '';
                    $msg = '';
                    $created = '';
                    $stmt->bind_result($sender, $receiver, $msg, $created);
                    
                    $messages = array();
                    while ($stmt->fetch()) {
                        $messages[] = array(
                            'sender_username' => $sender,
                            'receiver_username' => $receiver,
                            'message' => $msg,
                            'created_at' => $created
                        );
                    }
                    
                    $response['success'] = true;
                    $response['messages'] = $messages;
                    $stmt->close();
                }
            }
        }
    }

    $conn->close();
    
} catch (Exception $e) {
    $response['message'] = 'Server error';
}

ob_end_clean();
echo json_encode($response);
exit;
?>