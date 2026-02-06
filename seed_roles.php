<?php
include 'config.php';

// Correct column names: username, password_hash, email, full_name_ar, role
$roles = [
    ['receptionist', 'recep', 'recep123', 'recep@hospital.com', 'موظف الاستقبال'],
    ['accountant', 'acc', 'acc123', 'acc@hospital.com', 'المحاسب'],
    ['nurse', 'nurse1', 'nurse123', 'nurse1@hospital.com', 'ممرض الفرز'],
    ['doctor', 'dr_ali', 'doctor123', 'ali@hospital.com', 'د. علي الجراح'],
    ['lab_tech', 'lab1', 'lab123', 'lab1@hospital.com', 'فني المختبر'],
    ['radiologist', 'rad1', 'rad123', 'rad1@hospital.com', 'فني الأشعة']
];

foreach ($roles as $r) {
    $u = $r[1];
    $p = $r[2]; // Using plaintext for now as the existing login system likely expects it or handles hashing
    $e = $r[3];
    $f = $r[4];
    $rl = $r[0];

    $check = $conn->query("SELECT user_id FROM users WHERE username = '$u'");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO users (username, password_hash, email, full_name_ar, role) VALUES ('$u', '$p', '$e', '$f', '$rl')");
    }
}

echo "Roles seeded successfully. <br>";
echo "Radiologist: rad1 / rad123 <br>";
?>