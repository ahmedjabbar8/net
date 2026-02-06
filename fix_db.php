<?php
include 'config.php';

echo "<h2>تحديث قاعدة البيانات (الإصدار المحسن)</h2>";

// Function to check if column exists
function columnExists($conn, $table, $column)
{
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

// 1. Add completed_at to appointments
if (!columnExists($conn, 'appointments', 'completed_at')) {
    if ($conn->query("ALTER TABLE appointments ADD COLUMN completed_at DATETIME")) {
        echo "<p style='color:green;'>✅ تم إضافة عمود وقت الإكمال (completed_at) بنجاح</p>";
    } else {
        echo "<p style='color:red;'>❌ خطأ في تحديث المواعيد: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:blue;'>ℹ️ عمود وقت الإكمال موجود مسبقاً</p>";
}

// 2. Add estimated_time_minutes to lab_requests
if (!columnExists($conn, 'lab_requests', 'estimated_time_minutes')) {
    if ($conn->query("ALTER TABLE lab_requests ADD COLUMN estimated_time_minutes INT DEFAULT 30")) {
        echo "<p style='color:green;'>✅ تم إضافة عمود وقت المختبر بنجاح</p>";
    }
}

// 3. Add estimated_time_minutes to radiology_requests
if (!columnExists($conn, 'radiology_requests', 'estimated_time_minutes')) {
    if ($conn->query("ALTER TABLE radiology_requests ADD COLUMN estimated_time_minutes INT DEFAULT 45")) {
        echo "<p style='color:green;'>✅ تم إضافة عمود وقت الأشعة بنجاح</p>";
    }
}

// 4. Ensure Referrals table
$conn->query("CREATE TABLE IF NOT EXISTS referrals (
    referral_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    patient_id INT,
    from_doctor_id INT,
    to_department_id INT,
    reason TEXT,
    priority ENUM('normal', 'urgent') DEFAULT 'normal',
    status VARCHAR(50) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id),
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (from_doctor_id) REFERENCES users(user_id),
    FOREIGN KEY (to_department_id) REFERENCES departments(department_id)
)");

echo "<hr><p><b>تم الانتهاء!</b> يرجى العودة لشاشة الطبيب وتجربة حفظ الزيارة.</p>";
echo "<a href='index.php' style='padding:10px 20px; background:#3498db; color:white; text-decoration:none; border-radius:5px;'>العودة للرئيسية</a>";
?>