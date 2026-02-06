<?php
include 'config.php';
// Add cancelled status if it's an enum, or just ensure status can take 'cancelled'
$conn->query("ALTER TABLE appointments MODIFY COLUMN status VARCHAR(50) DEFAULT 'scheduled'");
echo "Database updated.";
?>