<?php
include 'config.php';
// Remove session check temporarily to test if it's the issue, but better keep it and ensure it's started
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$q = $_GET['q'] ?? '';
$q_param = "%$q%";

// Broad search across multiple fields
$sql = "SELECT p.patient_id, p.full_name_ar, p.file_number, p.national_id 
        FROM patients p 
        WHERE (p.full_name_ar LIKE ? OR p.file_number LIKE ? OR p.national_id LIKE ?)
        AND NOT EXISTS (
            SELECT 1 FROM appointments a 
            WHERE a.patient_id = p.patient_id 
            AND a.appointment_date = CURDATE() 
            AND a.status NOT IN ('completed', 'cancelled')
        )
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $q_param, $q_param, $q_param);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = $row;
}

header('Content-Type: application/json');
echo json_encode($suggestions);
?>