<?php
include 'config.php';
// Add photo column
$conn->query("ALTER TABLE patients ADD COLUMN IF NOT EXISTS photo VARCHAR(255) DEFAULT NULL");
// Add deleted_at for soft delete if needed, or just delete.
// Let's ensure the status enum/varchar can handle the return logic.
$conn->query("ALTER TABLE appointments MODIFY COLUMN status VARCHAR(50) DEFAULT 'scheduled'");
echo "Schema updated successfully.";
?>