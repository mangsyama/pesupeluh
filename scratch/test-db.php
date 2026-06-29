<?php
$serverName = "172.13.0.2,1433\\SQLEXPRESS"; // hostname from user
$connectionInfo = array(
    "Database" => "db_pesupeluh",
    "UID" => "ci_user",
    "PWD" => 'Sanata123!',
    "Encrypt" => false,
    "TrustServerCertificate" => true
);

echo "Testing connection to SQL Server...\n";
try {
    // 1. Test using raw PDO
    $dsn = "sqlsrv:Server=172.13.0.2,1433\\SQLEXPRESS;Database=db_pesupeluh;Encrypt=false;TrustServerCertificate=true";
    $conn = new PDO($dsn, "ci_user", "Sanata123!");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "PDO Connection Successful!\n";
    
    // Test a simple query
    $stmt = $conn->query("SELECT @@VERSION");
    $version = $stmt->fetchColumn();
    echo "SQL Server Version: " . $version . "\n";
} catch (Exception $e) {
    echo "Connection failed!\nError: " . $e->getMessage() . "\n";
}
