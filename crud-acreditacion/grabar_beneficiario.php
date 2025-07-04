<?php
$id=0;
// grabar_beneficiario.php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_beneficiario = $_POST['id_beneficiario'];
    $Apellido = $_POST['Apellido'];
    $Nombre = $_POST['Nombre'];
    $NumeroCuenta = $_POST['NumeroCuenta'];
    $ImporteCompra = $_POST['ImporteCompra'];
    $ImporteCajero = $_POST['ImporteCajero'];
    $DNI = $_POST['DNI'];
    $Fecha = $_POST['Fecha'];
    $Hora = $_POST['Hora'];

    $conn = new mysqli('localhost', 'pucho', '78900', 'cabal');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "INSERT INTO acreditarmanual (id,id_beneficiario, Apellido, Nombre, NumeroCuenta, ImporteCompra, ImporteCajero, DNI, Fecha, Hora) 
            VALUES ('$id','$id_beneficiario', '$Apellido', '$Nombre', '$NumeroCuenta', '$ImporteCompra', '$ImporteCajero', '$DNI', '$Fecha', '$Hora')";
    $id++;
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }

    $conn->close();
}
?>
