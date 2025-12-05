<?php
require_once 'config.php';
header('Content-Type: application/json');

$response = array();

if (isset($_SESSION['username'])) {
    $response['success'] = true;
    $response['logged_in'] = true;
    $response['username'] = $_SESSION['username'];
} else {
    $response['success'] = true;
    $response['logged_in'] = false;
}

echo json_encode($response);
?>
