<?php
include 'config.php';
echo "<h1>Debug Pharmacy</h1>";

$settings_res = $conn->query("SELECT * FROM system_settings");
if (!$settings_res) {
    echo "<br>System Settings Query Failed: " . $conn->error;
} else {
    echo "<br>System Settings Rows: " . $settings_res->num_rows;
}

$sql = "SELECT pr.*, p.full_name_ar as p_name, p.file_number, u.full_name_ar as doc_name 
        FROM prescriptions pr 
        JOIN patients p ON pr.patient_id = p.patient_id 
        JOIN users u ON pr.doctor_id = u.user_id 
        WHERE pr.status IN ('pending', 'pending_payment') 
        AND DATE(pr.created_at) = CURDATE()
        ORDER BY pr.created_at ASC";

$res = $conn->query($sql);
if (!$res) {
    echo "Query Failed: " . $conn->error;
} else {
    echo "Query Success. Rows: " . $res->num_rows;
}
?>