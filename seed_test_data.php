<?php
include 'config.php';

// 1. Create Dummy Patient
$rand = rand(1000, 9999);
$name = "مريض تجريبي " . $rand;
$phone = "055" . $rand;

$stmt = $conn->prepare("INSERT INTO patients (full_name_ar, phone, gender, birth_date, file_number) VALUES (?, ?, 'male', '1990-01-01', ?)");
$stmt->bind_param("sss", $name, $phone, $rand);
$stmt->execute();
$pid = $conn->insert_id;

if (!$pid) {
    die("Error creating patient: " . $conn->error);
}
echo "Created Patient: $name (ID: $pid)<br>";

// 2. Create Appointment for Reception (Column 1)
$stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, created_at) VALUES (?, 1, CURDATE(), 'pending_triage', NOW())");
$stmt->bind_param("i", $pid);
$stmt->execute();
echo "Inserted Reception Appointment (ID: " . $conn->insert_id . ")<br>";

// 3. Create Patient for Doctor (Column 2)
$rand2 = rand(1000, 9999);
$name2 = "مريض عند الطبيب " . $rand2;
$stmt2 = $conn->prepare("INSERT INTO patients (full_name_ar, phone, gender, birth_date, file_number) VALUES (?, ?, 'male', '1990-01-01', ?)");
$stmt2->bind_param("sss", $name2, $phone, $rand2);
$stmt2->execute();
$pid2 = $conn->insert_id;

$stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, created_at) VALUES (?, 1, CURDATE(), 'waiting_doctor', NOW())");
$stmt->bind_param("i", $pid2);
$stmt->execute();
echo "Inserted Doctor Appointment (ID: " . $conn->insert_id . ") for $name2<br>";

// 4. Create Patient for Lab (Column 3)
$rand3 = rand(1000, 9999);
$name3 = "مريض في المختبر " . $rand3;
$stmt3 = $conn->prepare("INSERT INTO patients (full_name_ar, phone, gender, birth_date, file_number) VALUES (?, ?, 'male', '1990-01-01', ?)");
$stmt3->bind_param("sss", $name3, $phone, $rand3);
$stmt3->execute();
$pid3 = $conn->insert_id;

// Appointment
$stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, created_at) VALUES (?, 1, CURDATE(), 'waiting_doctor', NOW())");
$stmt->bind_param("i", $pid3);
$stmt->execute();
$aid3 = $conn->insert_id;

// Lab Request
$conn->query("INSERT INTO lab_requests (appointment_id, test_name, status, created_at) VALUES ($aid3, 'CBC', 'pending', NOW())");
echo "Inserted Lab Appointment (ID: $aid3) for $name3<br>";

echo "<hr><h1>DONE! Go check waiting_list.php now.</h1>";
?>