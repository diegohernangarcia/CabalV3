<?php
$servername = "localhost";
$username = "pucho";
$password = "78900";
$dbname = "cabal";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$cuit = $_POST['cuit'];

// Preparar y ejecutar la consulta

$stmt = $conn->prepare("SELECT id, Apellido,Nombre,NumeroCuenta,NumeroDocumento FROM beneficiario WHERE Cuit = ?");
$stmt->bind_param("s", $cuit);
$stmt->execute();
$result = $stmt->get_result();

$response = array();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $response['success'] = true;
    $response['id'] = $row['id'];
    $response['id_beneficiario'] = $row['id'];
    $response['nombre'] = $row['Nombre'];
    $response['apellido'] = $row['Apellido'];
    $response['numeroCuenta'] = $row['NumeroCuenta'];
    // $response['importeCajero'] = $row['ImporteCajero'];
    $response['numeroDocumento'] = $row['NumeroDocumento'];
} else {
    $response['success'] = false;
}

echo json_encode($response);

$stmt->close();
$conn->close();
?>
