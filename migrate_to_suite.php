<?php
// HealthPro Database Migration Helper
// Run this file while XAMPP is active to export your data for the new suite.

require_once 'config.php';

$backup_file = '_suite/migration_backup_' . date('Y-m-d_H-i-s') . '.sql';

// Check if mysqldump is available (usually in xampp/mysql/bin)
// We try to use the system shell to create a full backup
$mysql_bin = "C:\\xampp\\mysql\\bin\\mysqldump.exe"; // Common XAMPP path

if (!file_exists($mysql_bin)) {
    die("Error: Could not find mysqldump.exe at $mysql_bin. Please verify XAMPP path.");
}

$command = "$mysql_bin --user=$user --password=$pass $db > $backup_file";

system($command, $output);

if ($output === 0) {
    echo "<h1>Migration Success | نجاح التصدير</h1>";
    echo "<p>Your data has been exported to: <b>$backup_file</b></p>";
    echo "<p>You can now use this file to import into your new HealthPro Server Suite.</p>";
} else {
    echo "<h1>Migration Failed | فشل التصدير</h1>";
    echo "<p>Error code: $output</p>";
}
?>