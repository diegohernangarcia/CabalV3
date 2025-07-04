<?php
$usuario='pucho';
$pass='78900';
// Connect to the database

$db = new PDO('mysql:host=localhost;dbname=cabal', $usuario, $pass);

// Get the form data
$apellido = $_POST['Apellido'];
$nombre = $_POST['Nombre'];

// Insert the new beneficiary into the database
$sql = "INSERT INTO beneficiario (Apellido, Nombre) VALUES ('$apellido', '$nombre')";
$db->query($sql);

// Redirect the user to the index page
header('Location: index.php');

?>
