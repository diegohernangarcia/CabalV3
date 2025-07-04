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

$id = $_POST['id'];
$idBeneficiario = $_POST['idBeneficiario'];
$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$numeroCuenta = $_POST['numeroCuenta'];
$importeCompra = $_POST['importeCompra'];
$importeCajero = $_POST['importeCajero'];
$numeroDocumento = $_POST['numeroDocumento'];
$fechaActual = $_POST['fechaActual'];
$horaActual = $_POST['horaActual'];

// Preparar y ejecutar la consulta
$stmt = $conn->prepare("INSERT INTO otra_tabla (id, id_beneficiario, Nombre, Apellido, NumeroCuenta, ImporteCompra, ImporteCajero, NumeroDocumento, FechaActual, HoraActual) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iissssddss", $id, $idBeneficiario, $nombre, $apellido, $numeroCuenta, $importeCompra, $importeCajero, $numeroDocumento, $fechaActual, $horaActual);

$response = array();

if ($stmt->execute()) {
    $response['success'] = true;
} else {
    $response['success'] = false;
}

echo json_encode($response);

$stmt->close();
$conn->close();
?>
