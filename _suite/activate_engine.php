<?php
// HealthPro Performance & Database Activation Script
// This script runs on first boot to optimize the DB and ensure maximum speed.

require_once 'config.php';

header('Content-Type: application/json');

$response = ['status' => 'success', 'messages' => []];

// 1. Database Optimization
$tables = ['patients', 'appointments', 'users', 'consultations'];
foreach ($tables as $table) {
    if ($conn->query("OPTIMIZE TABLE $table")) {
        $response['messages'][] = "Optimized table: $table";
    }
}

// 2. Set high-performance flags for MariaDB Session
$conn->query("SET SESSION query_cache_type = 1");
$conn->query("SET SESSION innodb_flush_log_at_trx_commit = 2");

// 3. Verify Version
$res = $conn->query("SELECT VERSION() as ver");
$row = $res->fetch_assoc();
$response['messages'][] = "Engine active: MariaDB " . $row['ver'];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>