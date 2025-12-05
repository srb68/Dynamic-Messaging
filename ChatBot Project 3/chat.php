<?php
require_once 'config.php';

header('Content-Type: application/json');

$response = array();

if (!isset($_SESSION['username'])) {
    $response['success'] = false;
    $response['message'] = 'Not logged in';
    echo json_encode($response);
    exit;
}

$current_user = $_SESSION['username'];
$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action == 'send') {
        $receiver = isset($_POST['receiver']) ? trim($_POST['receiver']) : '';
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        
        if ($receiver == '' || $message == '') {
            $response['success'] = false;
            $response['message'] = 'Receiver and message are required';
        } else {
            $stmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
            $stmt->bind_param("s", $receiver);
            $stmt->execute();
            
            $found_username = '';
            $stmt->bind_result($found_username);
            $stmt->fetch();
            $stmt->close();
            
            if ($found_username == '') {
                $response['success'] = false;
                $response['message'] = 'Receiver does not exist';
            } else {
                $stmt = $conn->prepare("INSERT INTO messages (sender_username, receiver_username, message) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $current_user, $receiver, $message);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Message sent';
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Failed to send message';
                }
                $stmt->close();
            }
        }
    }
    
} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action == 'fetch') {
        $chat_with = isset($_GET['chat_with']) ? trim($_GET['chat_with']) : '';
        
        if ($chat_with == '') {
            $response['success'] = false;
            $response['message'] = 'Chat partner not specified';
        } else {
            $stmt = $conn->prepare("
                SELECT sender_username, receiver_username, message, created_at 
                FROM messages 
                WHERE sender_username = ? AND receiver_username = ?
                ORDER BY created_at ASC
            ");
            $stmt->bind_param("ss", $chat_with, $current_user);
            $stmt->execute();
            
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

$conn->close();
echo json_encode($response);
?>