<?php
$serverName = "localhost"; 
$connectionOptions = [
    "Database" => "SMARTEDU",
    "Uid" => "",
    "PWD" => "",
    "CharacterSet" => "UTF-8"
];

// Conexión SQL Server 
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("❌ Error de conexión:\n" . print_r(sqlsrv_errors(), true));
}

// 🔹 Agregamos conexión PDO compatible con SQL Server (para el algoritmo)
try {
    $pdo = new PDO("sqlsrv:Server=$serverName;Database=SMARTEDU", $connectionOptions["Uid"], $connectionOptions["PWD"]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error al conectar con PDO: " . $e->getMessage());
}
