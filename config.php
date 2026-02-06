<?php
// HealthPro Ultra-Fast Optimized config
ob_start("ob_gzhandler"); // Enable Gzip compression
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Environment Detection
$is_custom_suite = file_exists(__DIR__ . '/_suite/server_dashboard.py');

$host = '127.0.0.1';
$user = 'root';
$pass = ''; // Default for portable MariaDB
$db = 'HospitalSystem';

// Enhanced Connection with Error Catching
try {
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        throw new Exception("Database Connection Error: " . $conn->connect_error);
    }
} catch (Exception $e) {
    // Show a professional error page if the server isn't running
    echo "<div dir='rtl'
    style='background:#f8f9fa; color:#333; padding:50px; text-align:center; font-family:Tahoma, Arial; height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;'>
    <div
        style='background:white; padding:40px; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,0.1); max-width:500px;'>
        <h1 style='color:#dc3545; font-size:48px; margin-bottom:10px;'>!</h1>
        <h2 style='margin-bottom:20px;'>مشكلة في الاتصال بقاعدة البيانات</h2>
        <p style='font-size:18px; line-height:1.6; color:#666;'>يرجى التأكد من تشغيل تطبيق <b>HealthPro Server</b> أولاً
            لتشغيل السيرفر.</p>
        <div
            style='margin-top:30px; padding:15px; background:#fff3cd; color:#856404; border-radius:8px; border:1px solid #ffeeba;'>
            خطأ: " . htmlspecialchars($e->getMessage()) . "
        </div>
    </div>
</div>";
    exit;
}

// Set charset to utf8mb4 for Arabic support
$conn->set_charset("utf8mb4");

// --- SYNCHRONIZE TIMEZONES ---
date_default_timezone_set('Asia/Baghdad');
$now = new DateTime();
$offset = $now->format('P');
$conn->query("SET time_zone='$offset'");

/*
// --- AUTO-MIGRATION & SCHEMA UPDATES ---
// Ensure the system core tables exist (Self-healing)
// We check if column exists first to avoid "Invalid default value" or syntax errors
$check_cols = $conn->query("SHOW COLUMNS FROM users LIKE 'current_task'");
if ($check_cols->num_rows == 0) {
$conn->query("ALTER TABLE users ADD COLUMN last_activity DATETIME NULL");
$conn->query("ALTER TABLE users ADD COLUMN current_task VARCHAR(255) DEFAULT 'متواجد'");
$conn->query("ALTER TABLE users ADD COLUMN active_patient_name VARCHAR(255) DEFAULT NULL");
}
*/

if (isset($_SESSION['user_id'])) {
    $uid = $conn->real_escape_string($_SESSION['user_id']);
    $conn->query("UPDATE users SET last_activity = NOW() WHERE user_id = '$uid'");
}

// Global Chat Cleanup (Delete messages older than 24 hours)
$check_msg_table = $conn->query("SHOW TABLES LIKE 'chat_messages'");
if ($check_msg_table->num_rows > 0) {
    $conn->query("DELETE FROM chat_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
} ?>