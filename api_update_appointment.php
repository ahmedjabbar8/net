<?php
include 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $date = $_POST['date'];
    $status = $_POST['status'];
    // department/doctor usually not changed simply here, but let's allow status and date

    $stmt = $conn->prepare("UPDATE appointments SET appointment_date = ?, status = ? WHERE appointment_id = ?");
    $stmt->bind_param("ssi", $date, $status, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>