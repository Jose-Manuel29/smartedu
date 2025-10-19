<?php
header("Content-Type: text/plain; charset=utf-8");

//como se conecta a ka bd
$serverName = "localhost"; 
$connectionOptions = [
    "Database" => "SMARTEDU",
    "Uid" => "",
    "PWD" => "",
    "CharacterSet" => "UTF-8"
];


// Conexión
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

$input = file_get_contents("php://input");    // el json q le llega de la extraccion desde el index 
$data = json_decode($input, true);

if (!$data || !isset($data["horarios"])) {
    die("Datos inválidos recibidos.");
}
//inserta todos los datos extraidos 
$sql = "INSERT INTO horarios (NRC, Clave, Materia, Secc, Dias, Hora, Profesor, Salon)  
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"; 
$stmt = sqlsrv_prepare($conn, $sql);
//inializa la insercion 
$insertados = 0;
//recorre todos los datos y para cada h se crea un arreglo nuevvo oparams si se devuelve algo se inrementa++    
foreach ($data["horarios"] as $h) {
    $params = [
        $h["NRC"],
        $h["Clave"],
        $h["Materia"],
        $h["Secc"],
        $h["Días"],
        $h["Hora"],
        $h["Profesor"],
        $h["Salón"]
    ];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt) $insertados++;
}

sqlsrv_close($conn);

echo "Se guardaron {$insertados} horarios correctamente en la base de datos.";
?>
