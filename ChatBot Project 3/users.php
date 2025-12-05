<?php
require_once 'config.php';
header('Content-Type: application/json');

$response = array();
$conn = getDBConnection();

$stmt = $conn->prepare("SELECT username FROM users ORDER BY username ASC");
$stmt->execute();

$username = '';
$stmt->bind_result($username);

$users = array();
while ($stmt->fetch()) {
    $users[] = array('username' => $username);
}

$response['success'] = true;
$response['users'] = $users;

$stmt->close();
$conn->close();

echo json_encode($response);
?>