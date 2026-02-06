<?php
include 'config.php';

$defaults = [
    'price_consultation' => '25000',
    'price_lab_default' => '15000',
    'price_rad_default' => '30000',
    'price_rx_default' => '5000'
];

foreach ($defaults as $key => $val) {
    // Only update if current value is empty or 0 to avoid resetting user changes, 
    // but here we know they are empty from debug.
    $conn->query("UPDATE system_settings SET setting_value = '$val' WHERE setting_key = '$key' AND (setting_value = '' OR setting_value IS NULL)");
}

echo "Cleaned up empty prices.";
?>