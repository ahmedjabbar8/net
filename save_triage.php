<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("Access Denied: Not logged in");
}

// Redirect GET requests back to triage
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: triage");
    exit();
}

if (isset($_SESSION['user_id'])) {
    // Clear active patient
    $conn->query("UPDATE users SET current_task = NULL, active_patient_name = NULL WHERE user_id = " .
        $_SESSION['user_id']);
}

$appt_id = $_POST['appt_id'] ?? '';
if (empty($appt_id)) {
    die("<h1>خطأ نظام: معرف الموعد مفقود</h1><br> يرجى تحديث الصفحة والمحاولة مرة أخرى. اذا استمرت المشكلة، تواصل مع الدعم
الفني.<br>Debug: POST[appt_id] is empty.");
}

$weight = $conn->real_escape_string($_POST['weight'] ?? '');
$height = $conn->real_escape_string($_POST['height'] ?? '');
$temp = $conn->real_escape_string($_POST['temp'] ?? '');
$bp = $conn->real_escape_string($_POST['bp'] ?? '');
$pulse = $conn->real_escape_string($_POST['pulse'] ?? '');
$oxygen = $conn->real_escape_string($_POST['oxygen'] ?? '');
$notes = $conn->real_escape_string($_POST['notes'] ?? '');
$is_urgent = isset($_POST['is_urgent']) ? 1 : 0;

// Ensure triage table has necessary columns (Redundant safeguard)
$conn->query("ALTER TABLE triage ADD COLUMN IF NOT EXISTS oxygen VARCHAR(20)");
$conn->query("ALTER TABLE triage MODIFY COLUMN height VARCHAR(20)");
$conn->query("ALTER TABLE triage MODIFY COLUMN weight VARCHAR(20)");
$conn->query("ALTER TABLE triage MODIFY COLUMN temperature VARCHAR(20)");
$conn->query("ALTER TABLE triage MODIFY COLUMN pulse VARCHAR(20)");
$conn->query("ALTER TABLE triage ADD COLUMN IF NOT EXISTS nurse_notes TEXT");

// Helper to handle empty numeric inputs
function sqlVal($conn, $val)
{
    return ($val === '' || $val === null) ? "NULL" : "'" . $conn->real_escape_string($val) . "'";
}

$insert_sql = "INSERT INTO triage (appointment_id, weight, height, temperature, blood_pressure, pulse, oxygen, nurse_notes) 
              VALUES ($appt_id, 
              " . sqlVal($conn, $_POST['weight'] ?? '') . ", 
              " . sqlVal($conn, $_POST['height'] ?? '') . ", 
              " . sqlVal($conn, $_POST['temp'] ?? '') . ", 
              " . sqlVal($conn, $_POST['bp'] ?? '') . ", 
              " . sqlVal($conn, $_POST['pulse'] ?? '') . ", 
              " . sqlVal($conn, $_POST['oxygen'] ?? '') . ", 
              " . sqlVal($conn, $_POST['notes'] ?? '') . ")";

if (!$conn->query($insert_sql)) {
    die("<h1>خطأ في قاعدة البيانات (Insert Triage)</h1><br>" . $conn->error . "<br><br>SQL: $insert_sql");
}

$update_sql = "UPDATE appointments SET status = 'waiting_doctor', is_urgent = $is_urgent WHERE appointment_id =
$appt_id";
if (!$conn->query($update_sql)) {
    die("<h1>خطأ في قاعدة البيانات (Update Status)</h1><br>" . $conn->error);
}

$_SESSION['msg'] = "تم تسجيل البيانات وتحويل المريض للطبيب بنجاح";
$_SESSION['msg_type'] = "success";
header("Location: triage");
exit();
?>