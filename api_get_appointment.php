<?php
include 'config.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM appointments WHERE appointment_id = $id");

if ($result && $row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'data' => $row]);
} else {
    echo json_encode(['success' => false]);
}
?>