<?php
include 'config.php';

// Sync DB defaults with IQD prices
$conn->query("ALTER TABLE lab_requests MODIFY COLUMN price FLOAT DEFAULT 15000.0");
$conn->query("ALTER TABLE radiology_requests MODIFY COLUMN price FLOAT DEFAULT 30000.0");
$conn->query("ALTER TABLE prescriptions MODIFY COLUMN price FLOAT DEFAULT 5000.0");

echo "DB Defaults sync complete.";
?>