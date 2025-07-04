<?php

$usuario='pucho';
    $pass='78900';
// Connect to the database
$db = new PDO('mysql:host=localhost;dbname=cabal', $usuario,$pass);

// Get the form data
$id = $_POST['id'];

// Delete the beneficiary from the database
$sql = "DELETE FROM beneficiario WHERE id=$id";
$db->query($sql);

// Redirect the user to the index page
header('Location: index.php');

?>
