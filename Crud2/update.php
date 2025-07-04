<?php

$usuario='pucho';
    $pass='78900';
// Connect to the database
$db = new PDO('mysql:host=localhost;dbname=cabal', $usuario,$pass);

// Get the form data
$id = $_POST['id'];
$apellido = $_POST['Apellido'];
$nombre = $_POST['Nombre'];

// Update the beneficiary in the database
$sql = "UPDATE beneficiario SET Apellido='$apellido', Nombre='$nombre' WHERE id=$id";
$db->query($sql);

// Redirect the user to the index page
header('Location: index.php');

?>
