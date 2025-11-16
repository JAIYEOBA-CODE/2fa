<?php
// utils/generate_seed_hashes.php
require_once __DIR__ . '/../app/config.php';
$admin_pass = 'Admin@123';
$test_pass = 'Test@12345';
echo "Replace placeholders in sql/mfa_schema.sql with the following password hashes:\n\n";
echo "Admin hash:\n" . password_hash($admin_pass, PASSWORD_DEFAULT) . "\n\n";
echo "Test user hash:\n" . password_hash($test_pass, PASSWORD_DEFAULT) . "\n\n";
echo "Note: After import, change seeded passwords immediately.\n";