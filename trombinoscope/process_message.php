<?php
// process_message.php

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$type = $data['type'] ?? 'info';
$message = $data['message'] ?? 'Message par defaut';

$type = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

$allowed_types = ['success', 'error', 'info', 'warning'];
if (!in_array($type, $allowed_types, true)) {
    $type = 'info';
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'type' => $type,
    'message' => $message
]);
exit;
