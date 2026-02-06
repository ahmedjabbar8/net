<?php
ob_start(); // Start buffering to catch any stray includes/warnings
include 'config.php';

// Check for completed_at column to avoid Errors
$check = $conn->query("SHOW COLUMNS FROM appointments LIKE 'completed_at'");
$has_completed_at = ($check && $check->num_rows > 0);

// Helper function to get diff in minutes
function getWaitTime($start)
{
    if (!$start)
        return 0;
    $s = new DateTime($start);
    $now = new DateTime();
    $diff = $s->diff($now);
    return ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
}

// AUTO-SEEDER: If database is empty, fill it with demo data
$check_count = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE()");
$row_count = $check_count->fetch_assoc()['count'];

if ($row_count == 0) {
    // 1. Reception Patient
    $conn->query("INSERT INTO patients (full_name_ar, phone, gender, file_number) VALUES ('مريض استقبال تجريبي', '0500000', 'male', '1001') ON DUPLICATE KEY UPDATE full_name_ar=full_name_ar");
    $pid1 = $conn->insert_id ?: $conn->query("SELECT patient_id FROM patients WHERE file_number='1001'")->fetch_assoc()['patient_id'];
    $conn->query("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, created_at) VALUES ($pid1, 1, CURDATE(), 'pending_triage', NOW())");

    // 2. Doctor Patient
    $conn->query("INSERT INTO patients (full_name_ar, phone, gender, file_number) VALUES ('مريض عيادة تجريبي', '0500001', 'female', '1002') ON DUPLICATE KEY UPDATE full_name_ar=full_name_ar");
    $pid2 = $conn->insert_id ?: $conn->query("SELECT patient_id FROM patients WHERE file_number='1002'")->fetch_assoc()['patient_id'];
    $conn->query("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, created_at) VALUES ($pid2, 1, CURDATE(), 'waiting_doctor', NOW())");

    // 3. Lab Patient
    $conn->query("INSERT INTO patients (full_name_ar, phone, gender, file_number) VALUES ('مريض مختبر تجريبي', '0500002', 'male', '1003') ON DUPLICATE KEY UPDATE full_name_ar=full_name_ar");
    $pid3 = $conn->insert_id ?: $conn->query("SELECT patient_id FROM patients WHERE file_number='1003'")->fetch_assoc()['patient_id'];
    $conn->query("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, created_at) VALUES ($pid3, 1, CURDATE(), 'waiting_doctor', NOW())");
    $aid3 = $conn->insert_id;
    $conn->query("INSERT INTO lab_requests (appointment_id, test_name, status) VALUES ($aid3, 'Test', 'pending')");
}


// 1. Column 1: Reception & Triage
$reception_sql = "SELECT a.created_at, p.full_name_ar as p_name, a.status 
                  FROM appointments a 
                  JOIN patients p ON a.patient_id = p.patient_id 
                  WHERE a.status IN ('scheduled', 'pending_triage', 'Scheduled', 'Pending_Triage') 
                  AND DATE(a.appointment_date) = CURDATE()
                  ORDER BY a.created_at ASC";
$reception_res = $conn->query($reception_sql);
$reception_list = [];
while ($r = $reception_res->fetch_assoc()) {
    $reception_list[] = [
        'name' => $r['p_name'],
        'wait' => getWaitTime($r['created_at']),
        'entrance' => date('h:i A', strtotime($r['created_at'])),
        'sub_status' => ($r['status'] == 'scheduled' ? 'حسابات' : 'فحص أولي')
    ];
}

// 2. Column 2: Doctor Queue
$doctor_sql = "SELECT a.created_at, a.appointment_id, a.is_urgent, p.full_name_ar as p_name, u.full_name_ar as doc_name
               FROM appointments a 
               JOIN patients p ON a.patient_id = p.patient_id 
               LEFT JOIN users u ON a.doctor_id = u.user_id 
               WHERE a.status = 'waiting_doctor' 
               AND DATE(a.appointment_date) = CURDATE()
               ORDER BY a.is_urgent DESC, a.created_at ASC";
$doctor_res = $conn->query($doctor_sql);
$doctor_list = [];
while ($r = $doctor_res->fetch_assoc()) {
    $aid = $r['appointment_id'];

    // Check results status
    $has_results = $conn->query("SELECT (SELECT COUNT(*) FROM lab_requests WHERE appointment_id = $aid AND status = 'completed') + (SELECT COUNT(*) FROM radiology_requests WHERE appointment_id = $aid AND status = 'completed') as total")->fetch_assoc()['total'];

    // Check strict pending logic
    $pend_res = $conn->query("SELECT 
        (SELECT COUNT(*) FROM lab_requests WHERE appointment_id = $aid AND status IN ('pending', 'pending_payment')) +
        (SELECT COUNT(*) FROM radiology_requests WHERE appointment_id = $aid AND status IN ('pending', 'pending_payment')) +
        (SELECT COUNT(*) FROM prescriptions WHERE appointment_id = $aid AND status IN ('pending', 'pending_payment')) as total")->fetch_assoc()['total'];

    // If has pending medical requests, SKIP from Doctor Queue (Wait for them to return)
    if ($pend_res > 0)
        continue;

    $doctor_list[] = [
        'patient' => $r['p_name'],
        'doctor' => $r['doc_name'] ?? 'عام',
        'wait' => getWaitTime($r['created_at']),
        'entrance' => date('h:i A', strtotime($r['created_at'])),
        'is_ready' => ($has_results > 0),
        'is_urgent' => (intval($r['is_urgent']) === 1)
    ];
}

// 3. Column 3: Medical / Examinations
$exams_sql = "
    SELECT p.full_name_ar as p_name, a.appointment_id, a.created_at,
    (SELECT COUNT(*) FROM lab_requests WHERE appointment_id = a.appointment_id AND status IN ('pending', 'pending_payment')) as pend_labs,
    (SELECT COUNT(*) FROM radiology_requests WHERE appointment_id = a.appointment_id AND status IN ('pending', 'pending_payment')) as pend_rads,
    (SELECT COUNT(*) FROM prescriptions WHERE appointment_id = a.appointment_id AND status IN ('pending', 'pending_payment')) as pend_pharma
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.patient_id 
    WHERE a.status != 'completed' AND a.status != 'cancelled'
    AND DATE(a.appointment_date) = CURDATE()
    HAVING (pend_labs > 0 OR pend_rads > 0 OR pend_pharma > 0)
    ORDER BY a.created_at ASC
";
$exams_res = $conn->query($exams_sql);
$exams_list = [];
while ($ex = $exams_res->fetch_assoc()) {
    $exams_list[] = [
        'patient' => $ex['p_name'],
        'entrance' => date('h:i A', strtotime($ex['created_at'])),
        'status_msg' => 'قيد الانتظار',
        'has_lab' => ($ex['pend_labs'] > 0),
        'has_rad' => ($ex['pend_rads'] > 0),
        'has_pharma' => ($ex['pend_pharma'] > 0)
    ];
}

// Clear any previous output (warnings/HTML) that might corrupt JSON
ob_end_clean();

header('Content-Type: application/json');
echo json_encode([
    'reception' => $reception_list,
    'doctor' => $doctor_list,
    'medical' => $exams_list
], JSON_PARTIAL_OUTPUT_ON_ERROR);
?>