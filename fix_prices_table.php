<?php
include 'config.php';

// 1. Create table for dynamic prices
$conn->query("CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
)");

// 2. Insert default prices in Iraqi Dinar (IQD)
$defaults = [
    'price_consultation' => '25000',
    'price_lab_default' => '15000',
    'price_rad_default' => '30000',
    'price_rx_default' => '5000',
    'currency_label' => 'د.ع'
];

foreach ($defaults as $key => $val) {
    $conn->query("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('$key', '$val')");
}

echo "Prices and Currency initialized successfully.";
?>