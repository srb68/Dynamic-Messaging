<?php
require_once 'config.php';

header('Content-Type: application/json');

$response = array('success' => false, 'message' => 'Unknown error');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if ($username == '' || $password == '') {
        $response['message'] = 'Username and password are required';
    } else {
        $conn = getDBConnection();
        
        if ($action == 'register') {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed_password);
            
            if ($stmt->execute()) {
                $_SESSION['username'] = $username;
                $response['success'] = true;
                $response['message'] = 'Registration successful';
            } else {
                $response['message'] = 'Username already exists';
            }
            $stmt->close();
            
        } else if ($action == 'login') {
            $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            
            $stored_password = '';
            $stmt->bind_result($stored_password);
            $stmt->fetch();
            
            if ($stored_password != '') {
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
        }
        
        $conn->close();
    }
}

echo json_encode($response);
?>