<?php
require_once 'config/db_connect.php';

// Create the correct hash for 'admin123'
$correct_hash = password_hash('admin123', PASSWORD_DEFAULT);

// Get the current hash from database
$sql = "SELECT password FROM users WHERE email = 'admin@system.com'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

echo "Correct Hash: " . $correct_hash . "\n";
echo "DB Hash: " . ($row['password'] ?? 'Not found') . "\n";

// Verify if current password would work
if ($row && password_verify('admin123', $row['password'])) {
    echo "Password verification would succeed!";
} else {
    echo "Password verification would fail!";
}

// Update the password to ensure it's correct
$sql = "UPDATE users SET password = ? WHERE email = 'admin@system.com'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $correct_hash);
if (mysqli_stmt_execute($stmt)) {
    echo "\nPassword updated successfully!";
}
?>
