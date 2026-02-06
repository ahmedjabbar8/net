<?php
include 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($id > 0) {
    if ($action == 'trigger') {
        // Doctor triggers the call
        $conn->query("UPDATE appointments SET call_status = 1 WHERE appointment_id = $id");
        echo json_encode(['success' => true]);
    } elseif ($action == 'complete') {
        // Monitor completes the call after speaking
        $conn->query("UPDATE appointments SET call_status = 2 WHERE appointment_id = $id");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
}
?>