<?php
include 'config.php';

// 1. Create table for Lab Tests

$sql1 = "CREATE TABLE IF NOT EXISTS lab_tests (
    test_id INT AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(100) NOT NULL UNIQUE,
    price FLOAT DEFAULT 15000,
    min_value FLOAT DEFAULT NULL,
    max_value FLOAT DEFAULT NULL,
    unit VARCHAR(32) DEFAULT NULL,
    gender ENUM('male','female','both') DEFAULT 'both',
    reference_range VARCHAR(128) DEFAULT NULL,
    profile_components TEXT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE
)";

if ($conn->query($sql1) === TRUE) {
    echo "Table lab_tests created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// 2. Populate with existing hardcoded data if empty
$existing = $conn->query("SELECT count(*) FROM lab_tests")->fetch_row()[0];
if ($existing == 0) {
    $lab_list = ['CBC', 'FBS', 'HBA1C', 'Urea', 'Creatinine', 'SGOT', 'SGPT', 'Lipid Profile', 'TSH', 'Vitamin D', 'CRP', 'Total Protein', 'Albumin', 'Bilirubin', 'Urine R/E', 'Stool R/E', 'Widal Test', 'H. Pylori', 'PSA', 'Troponin'];

    // Get default price
    $res = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'price_lab_default'");
    $default_price = 15000;
    if ($res && $row = $res->fetch_assoc()) {
        $default_price = $row['setting_value'];
    }

    $stmt = $conn->prepare("INSERT INTO lab_tests (test_name, price) VALUES (?, ?)");
    foreach ($lab_list as $test) {
        $stmt->bind_param("sd", $test, $default_price);
        $stmt->execute();
    }
    echo "Populated lab_tests with " . count($lab_list) . " items.\n";
} else {
    echo "Table lab_tests already has data.\n";
}
?>