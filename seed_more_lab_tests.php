<?php
include 'config.php';

// Comprehensive list of Lab Tests and Profiles
$comprehensive_lab_list = [
    // Hematology
    'CBC (Complete Blood Count)',
    'ESR (Erythrocyte Sedimentation Rate)',
    'Blood Group & Rh',
    'PT (Prothrombin Time)',
    'PTT (Partial Thromboplastin Time)',
    'INR',
    'Reticulocyte Count',
    'Bleeding Time',
    'Clotting Time',
    'Peripheral Blood Smear',

    // Biochemistry
    'FBS (Fasting Blood Sugar)',
    'RBS (Random Blood Sugar)',
    'HbA1c (Glycosylated Hemoglobin)',
    'Urea',
    'Creatinine',
    'Uric Acid',
    'Total Protein',
    'Albumin',
    'Globulin',
    'Bilirubin (Total)',
    'Bilirubin (Direct)',
    'SGOT (AST)',
    'SGPT (ALT)',
    'ALP (Alkaline Phosphatase)',
    'GGT',
    'Amylase',
    'Lipase',
    'LDH',
    'CPK',
    'Calcium (Total)',
    'Calcium (Ionized)',
    'Phosphorus',
    'Magnesium',
    'Sodium (Na)',
    'Potassium (K)',
    'Chloride (Cl)',
    'Iron (Serum)',
    'TIBC',
    'Ferritin',

    // Profiles / Panels
    'Lipid Profile',
    'Liver Function Test (LFT)',
    'Renal Function Test (RFT)',
    'Thyroid Profile (T3, T4, TSH)',
    'Iron Profile',
    'Electrolyte Panel',
    'Bone Profile',
    'Cardiac Profile',
    'Coagulation Profile',
    'Diabetes Profile',
    'Anemia Profile',
    'Arthritis Profile',

    // Hormones & Vitamins
    'TSH',
    'T3',
    'T4',
    'Free T3',
    'Free T4',
    'Vitamin D3',
    'Vitamin B12',
    'Folic Acid',
    'Prolactin',
    'FSH',
    'LH',
    'Testosterone (Total)',
    'Testosterone (Free)',
    'Progesterone',
    'Estradiol (E2)',
    'Cortisol',
    'Insulin',
    'BHCG (Pregnancy Test)',

    // Serology & Immunology
    'CRP (C-Reactive Protein)',
    'CRP Quantitative',
    'RF (Rheumatoid Factor)',
    'ASO (Antistreptolysin O)',
    'Widal Test',
    'H. Pylori (Blood)',
    'H. Pylori (Stool)',
    'H. Pylori (Breath)',
    'HBsAg (Hepatitis B Surface Antigen)',
    'HCV Ab (Hepatitis C Antibody)',
    'HIV Combo',
    'VDRL / RPR',
    'Brucella Agglutination',
    'Toxoplasma IgG/IgM',
    'Rubella IgG/IgM',
    'CMV IgG/IgM',
    'D-Dimer',
    'Troponin I',
    'Troponin T',

    // Tumor Markers
    'PSA (Total)',
    'PSA (Free)',
    'CEA',
    'CA-125',
    'CA 15-3',
    'CA 19-9',
    'Alpha Fetoprotein (AFP)',

    // Clinical Pathology (Urine/Stool/Fluids)
    'Urine R/E (General Urine Exam)',
    'Stool R/E (General Stool Exam)',
    'Stool Occult Blood',
    'Semen Analysis',
    'Sputum Analysis',
    'Swab Culture & Sensitivity',
    'Urine Culture & Sensitivity'
];

// Get default price
$res = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'price_lab_default'");
$default_price = 15000;
if ($res && $row = $res->fetch_assoc()) {
    $default_price = $row['setting_value'];
}

echo "Starting to add " . count($comprehensive_lab_list) . " tests...\n";

$stmt = $conn->prepare("SELECT test_id FROM lab_tests WHERE test_name = ?");
$insert_stmt = $conn->prepare("INSERT INTO lab_tests (test_name, price) VALUES (?, ?)");

$added_count = 0;
$skipped_count = 0;

foreach ($comprehensive_lab_list as $test) {
    // Check if exists
    $stmt->bind_param("s", $test);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        // Does not exist, insert
        $price = $default_price;

        // Custom pricing logic for profiles (example)
        if (strpos($test, 'Profile') !== false || strpos($test, 'Test (LFT)') !== false || strpos($test, 'Test (RFT)') !== false) {
            $price = $default_price * 2; // Make profiles slightly more expensive by default
        }

        $insert_stmt->bind_param("sd", $test, $price);
        if ($insert_stmt->execute()) {
            $added_count++;
        }
    } else {
        $skipped_count++;
    }
}

echo "Done!\n";
echo "Added: $added_count new tests.\n";
echo "Skipped: $skipped_count existing tests.\n";
?>