<?php
include 'config.php';

// SELF-HEALING: Add is_free column
$conn->query("ALTER TABLE appointments ADD COLUMN IF NOT EXISTS is_free INT DEFAULT 0");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$barcode = $_GET['barcode'] ?? '';
if (empty($barcode)) {
    echo json_encode(['success' => false, 'message' => 'الباركود فارغ']);
    exit();
}

// 1. FAST FIND: Find Patient
$p_sql = "SELECT patient_id, full_name_ar FROM patients WHERE file_number = ? OR national_id = ? LIMIT 1";
$p_stmt = $conn->prepare($p_sql);
$p_stmt->bind_param("ss", $barcode, $barcode);
$p_stmt->execute();
$patient = $p_stmt->get_result()->fetch_assoc();

if (!$patient) {
    echo json_encode(['success' => false, 'message' => 'لم يتم العثور على مريض']);
    exit();
}

$pid = $patient['patient_id'];

// 2. SMART LOGIC: Get last appointment details
$last = $conn->query("SELECT department_id, doctor_id, appointment_date FROM appointments WHERE patient_id = $pid ORDER BY appointment_id DESC LIMIT 1")->fetch_assoc();

$dept_id = $last['department_id'] ?? 1;
$doctor_id = $last['doctor_id'] ?? 1;
$last_date = $last['appointment_date'] ?? '2000-01-01';

// 3. FREE REVIEW LOGIC (7 Days)
$is_free = 0;
$diff = (strtotime(date('Y-m-d')) - strtotime($last_date)) / (60 * 60 * 24);
if ($diff <= 7 && $diff >= 0) {
    $is_free = 1;
}

// 4. INSTANT BOOKING (No blocking, even if booked today)
$sql = "INSERT INTO appointments (patient_id, doctor_id, department_id, appointment_date, status, is_free) 
        VALUES (?, ?, ?, CURDATE(), 'scheduled', ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $pid, $doctor_id, $dept_id, $is_free);

if ($stmt->execute()) {
    $msg = "تم حجز موعد لـ " . $patient['full_name_ar'];
    if ($is_free)
        $msg .= " (مراجعة مجانية)";

    echo json_encode([
        'success' => true,
        'message' => $msg,
        'is_free' => $is_free
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'فشل الحجز']);
}
