<?php
include 'config.php';

// Advanced & Extended Lab Tests List
$advanced_lab_list = [
    // Microbiology & Cultures
    'Blood Culture & Sensitivity',
    'Pus Culture & Sensitivity',
    'Wound Swab Culture',
    'Throat Swab Culture',
    'Ear Swab Culture',
    'Eye Swab Culture',
    'High Vaginal Swab (HVS)',
    'Urethral Discharge Culture',
    'CSF Analysis (Cerebrospinal Fluid)',
    'Pleural Fluid Analysis',
    'Ascitic Fluid Analysis',
    'Synovial Fluid Analysis',
    'Fungal Culture',

    // Advanced Hormones & Endocrinology
    'PTH (Parathyroid Hormone)',
    'ACTH (Adrenocorticotropic Hormone)',
    'Growth Hormone (GH)',
    'Aldosterone',
    'Renin Activity',
    'IGF-1 (Insulin-like Growth Factor)',
    'C-Peptide',
    'Gastrin',
    'Calcitonin',
    'DHEA-S',
    'Androstenedione',
    'Anti-Mullerian Hormone (AMH)',

    // Specialized Immunology & Autoimmune
    'ANA (Antinuclear Antibody) Profile',
    'Anti-dsDNA',
    'Anti-CCP (Cyclic Citrullinated Peptide)',
    'ANCA (Antineutrophil Cytoplasmic Abs)',
    'Anti-Phospholipid Antibodies',
    'Anti-Thyroglobulin (Anti-Tg)',
    'Anti-TPO (Thyroid Peroxidase Ab)',
    'Lupus Anticoagulant',
    'Complement C3',
    'Complement C4',
    'Immunoglobulins (IgG, IgA, IgM)',

    // Infectious Diseases (PCR & Serology)
    'HCV RNA PCR (Viral Load)',
    'HBV DNA PCR (Viral Load)',
    'HIV-1 RNA PCR (Viral Load)',
    'Dengue NS1 Antigen',
    'Dengue IgG/IgM',
    'Malaria Antigen (Rapid)',
    'Malaria Parasite (Blood Film)',
    'TB PCR (GeneXpert)',
    'Mantoux Test (PPD)',
    'Ebstein Barr Virus (EBV) Abs',
    'Herpes Simplex Virus (HSV) 1 & 2',
    'Varicella Zoster IgG/IgM',
    'Chlamydia Trachomatis Ag/PCR',

    // Vitamins, Minerals & Trace Elements
    'Zinc (Serum)',
    'Copper (Serum)',
    'Lead (Blood Level)',
    'Lithium (Therapeutic Monitoring)',
    'Vitamin A (Retinol)',
    'Vitamin E (Tocopherol)',
    'Vitamin B6',
    'Folate (RBC)',

    // Toxicology & Drugs of Abuse (Urine)
    'Drug Screen 10-Panel (Urine)',
    'Drug Screen 5-Panel (Urine)',
    'Alcohol (Ethanol) Blood Level',

    // Allergy
    'Total IgE',
    'Food Allergy Panel',
    'Inhalant Allergy Panel',

    // Cardiac & Coagulation (Advanced)
    'Homocysteine',
    'Fibrinogen',
    'Protein C',
    'Protein S',
    'Antithrombin III',
    'NT-proBNP'
];

// Get default price
$res = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'price_lab_default'");
$default_price = 15000;
$complex_price = 45000; // Higher price for PCR/Cultures/Advanced

if ($res && $row = $res->fetch_assoc()) {
    $default_price = $row['setting_value'];
}

echo "Starting to add " . count($advanced_lab_list) . " advanced tests...\n";

$stmt = $conn->prepare("SELECT test_id FROM lab_tests WHERE test_name = ?");
$insert_stmt = $conn->prepare("INSERT INTO lab_tests (test_name, price) VALUES (?, ?)");

$added_count = 0;
$skipped_count = 0;

foreach ($advanced_lab_list as $test) {
    // Check if exists
    $stmt->bind_param("s", $test);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        $price = $default_price;

        // Price logic
        if (strpos($test, 'PCR') !== false || strpos($test, 'Culture') !== false || strpos($test, 'Panel') !== false || strpos($test, 'Screen') !== false) {
            $price = $complex_price;
        } elseif (strpos($test, 'Vitamin') !== false || strpos($test, 'Hormone') !== false) {
            $price = $default_price * 1.5;
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
echo "Added: $added_count new advanced tests.\n";
echo "Skipped: $skipped_count existing tests.\n";
?>