<?php

// Connect to the database
$usuario='pucho';
    $pass='78900';
$db = new PDO('mysql:host=localhost;dbname=cabal', $usuario, $pass);

// Get the beneficiaries from the database
$sql = 'SELECT * FROM beneficiario;';
$result = $db->query($sql);

// Create a CSV file
$fp = fopen('beneficiarios.csv', 'w');

// Write the header row to the CSV file
fputcsv($fp, array('Apellido', 'Nombre', 'Calle', 'Numero', 'Piso', 'Departamento', 'Localidad', 'Provincia', 'Sexo', 'Entidad Emisora', 'Fecha Proceso', 'Numero Cuenta', 'Numero Tarjeta', 'Codigo Postal', 'Telefono Caracteristica', 'Telefono Numero', 'Estado Civil', 'Fecha Nacimiento', 'Tipo Documento', 'Numero Documento', 'Provincia Documento', 'Cuit', 'Codigo Ingreso', 'Codigo Rechazo'));

// Write the beneficiary data to the CSV file
foreach ($result as $row) {
  fputcsv($fp, $row);
}

// Close the CSV file
fclose($fp);

?>
