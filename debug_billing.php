<?php
include 'config.php';
echo "<h1>Debug Info</h1>";

echo "<h2>Appointments</h2>";
$res = $conn->query("SELECT appointment_id, patient_id, status, appointment_date FROM appointments");
while ($row = $res->fetch_assoc()) {
    print_r($row);
    echo "<br>";
}

echo "<h2>Lab Requests</h2>";
$res = $conn->query("SELECT request_id, appointment_id, status FROM lab_requests");
while ($row = $res->fetch_assoc()) {
    print_r($row);
    echo "<br>";
}

echo "<h2>Invoices</h2>";
$res = $conn->query("SELECT * FROM invoices");
while ($row = $res->fetch_assoc()) {
    print_r($row);
    echo "<br>";
}
echo "<h2>Patients</h2>";
$res = $conn->query("SELECT * FROM patients");
while ($row = $res->fetch_assoc()) {
    print_r($row);
    echo "<br>";
}
echo "<h2>Main Query Test</h2>";
$sql_patients = "
    SELECT DISTINCT p.patient_id, p.full_name_ar, p.file_number, p.category, a.appointment_id, a.is_free 
    FROM patients p 
    JOIN appointments a ON p.patient_id = a.patient_id
    LEFT JOIN lab_requests l ON a.appointment_id = l.appointment_id AND l.status = 'pending_payment'
    LEFT JOIN radiology_requests r ON a.appointment_id = r.appointment_id AND r.status = 'pending_payment'
    LEFT JOIN prescriptions pr ON a.appointment_id = pr.appointment_id AND pr.status = 'pending_payment'
    WHERE (a.status = 'scheduled')
       OR (l.status = 'pending_payment')
       OR (r.status = 'pending_payment')
       OR (pr.status = 'pending_payment')
    ORDER BY a.appointment_date DESC
";
$res = $conn->query($sql_patients);
echo "Rows found: " . $res->num_rows . "<br>";
if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error;
}
?>