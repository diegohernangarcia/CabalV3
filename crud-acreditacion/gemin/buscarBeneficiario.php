<?php
// Datos de conexión a la base de datos (mismos que en procesar.php y script.js)
$dbHost = "localhost";
$dbUsername = "tu_usuario";
$dbPassword = "tu_contraseña";
$dbName = "tu_base_de_datos";

// Conectar a la base de datos
$conn = mysqli_connect($dbHost, $dbUsername, $dbPassword, $dbName);

// Comprobar la conexión
if (!$conn) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}

// Recibir CUIT del parámetro GET
$cuit = $_GET["cuit"];

// Preparar la consulta SQL
$sql = "SELECT nombre FROM beneficiario WHERE CUIT = '$cuit'";

// Ejecutar la consulta SQL
$result = mysqli_query($conn, $sql);

// Procesar el resultado
if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);

    // Extraer solo el nombre
    $nombre = $row["nombre"];

    // Crear un objeto con el nombre
    $beneficiario = [
        "nombre" => $nombre
    ];

    // Convertir el objeto a JSON y enviarlo como respuesta
    echo json_encode($beneficiario);
} else {
    // Enviar un mensaje indicando que no se encontró el beneficiario
    echo json_encode(["beneficiario" => null]);
}

// Cerrar la conexión a la base de datos
mysqli_close($conn);
?>