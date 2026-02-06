<?php
include 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $aid = intval($_POST['id']);

    // Logic: Cancel appointment if it is NOT 'completed'
    // We update status to 'cancelled'
    $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ? AND status != 'completed'");
    $stmt->bind_param("i", $aid);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        // Either it doesn't exist, is already completed, or already cancelled
        echo json_encode(['success' => false, 'message' => 'Could not cancel appointment (might be completed or invalid)']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
}
?>