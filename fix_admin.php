<?php
require_once 'config/database.php';

// Generate password hash for 'admin123'
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Delete existing admin user
$stmt = $conn->prepare("DELETE FROM users WHERE username = 'admin'");
$stmt->execute();

// Insert new admin user with correct password hash
$stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
$username = 'admin';
$email = 'admin@mart.com';
$full_name = 'System Administrator';
$role = 'admin';
$stmt->bind_param("sssss", $username, $hash, $email, $full_name, $role);
$stmt->execute();

echo "Admin user has been reset. You can now login with:\n";
echo "Username: admin\n";
echo "Password: admin123\n";
?> 