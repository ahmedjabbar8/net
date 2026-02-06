<?php
// HealthPro Super Seeder - Created by Antigravity
include 'config.php';

echo "<div dir='rtl' style='font-family:Tahoma; padding:20px;'>";
echo "<h2>🚀 جاري إنشاء وتجهيز حسابات النظام...</h2>";

// 1. Clear existing users (Optional - but recommended for fresh setup)
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("TRUNCATE TABLE users");
$conn->query("TRUNCATE TABLE departments");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// 2. Setup Departments
$departments = [
    ['معلومات الاستقبال', 'medical'],
    ['عيادة الطبيب العام', 'medical'],
    ['المختبر', 'medical'],
    ['الأشعة', 'medical'],
    ['الصيدلية', 'medical'],
    ['المحاسبة', 'administrative'],
    ['الإدارة العامة', 'administrative']
];

$dept_ids = [];
foreach ($departments as $d) {
    $stmt = $conn->prepare("INSERT INTO departments (department_name_ar, department_type) VALUES (?, ?)");
    $stmt->bind_param("ss", $d[0], $d[1]);
    $stmt->execute();
    $dept_ids[$d[0]] = $conn->insert_id;
    echo "✅ تم إنشاء قسم: " . $d[0] . "<br>";
}

// 3. User Templates (username, password, role, department_name, full_name)
// Password will be '123456' for all users to match the login.php comparison
$password = '123456';

$users = [
    ['admin', $password, 'admin', 'الإدارة العامة', 'المدير العام'],
    ['doctor', $password, 'doctor', 'عيادة الطبيب العام', 'د. محمد علي'],
    ['reception', $password, 'receptionist', 'معلومات الاستقبال', 'أحمد الاستقبال'],
    ['nurse', $password, 'nurse', 'عيادة الطبيب العام', 'ملاك التمريض'],
    ['lab', $password, 'lab_tech', 'المختبر', 'يوسف المختبر'],
    ['radio', $password, 'radiologist', 'الأشعة', 'سارة الأشعة'],
    ['pharmacy', $password, 'pharmacist', 'الصيدلية', 'علي الصيدلية'],
    ['accountant', $password, 'accountant', 'المحاسبة', 'كمال المحاسب']
];

foreach ($users as $u) {
    $dept_id = $dept_ids[$u[3]] ?? null;
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role, department_id, full_name_ar, email) VALUES (?, ?, ?, ?, ?, ?)");
    $email = $u[0] . "@healthpro.com";
    $stmt->bind_param("sssiss", $u[0], $u[1], $u[2], $dept_id, $u[4], $email);

    if ($stmt->execute()) {
        echo "👤 تم إنشاء مستخدم: <b>" . $u[0] . "</b> (كلمة السر: 123456 | الرتبة: " . $u[2] . ")<br>";
    } else {
        echo "❌ فشل إنشاء " . $u[0] . ": " . $conn->error . "<br>";
    }
}

echo "<hr><h3>🎉 تم تجهيز كافة الحسابات بنجاح!</h3>";
echo "<p>يمكنك الآن تسجيل الدخول باستخدام:</p>";
echo "<ul>
        <li>اسم المستخدم: <b>admin</b> أو <b>doctor</b> أو <b>reception</b></li>
        <li>كلمة المرور لجميع الحسابات: <b>123456</b></li>
      </ul>";
echo "<a href='login.php' style='padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px;'>الذهاب لصفحة تسجيل الدخول</a>";
echo "</div>";
?>