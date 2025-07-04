<?php
/*
  Funciona OK  
  $conn = new mysqli("localhost", "root", "", "cabal");
  
  if ($conn->connect_error) {
    die("ERROR: No se puede conectar al servidor: " . $conn->connect_error);
  } 

  echo 'Conectado a la base de datos.<br>';

  $result = $conn->query("SELECT * FROM altas");

  echo "Numero de resultados: $result->num_rows";

  $result->close();

  $conn->close(); */
  /* Conectar a una base de datos invocando al controlador */
//$hostname = 'mysql:cabal=php_conexion;host=localhost';
$usuario='pucho';
    $pass='78900';
$hostname='mysql:host=localhost;dbname=cabal';
$usuario = $usuario;
$contrasena = $pass;
require_once ('Funciones.php');

try {
    $conn = new PDO($hostname, $usuario, $contrasena);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
  catch(PDOException $err) {
	  // Imprime error de conexión
    echo "ERROR: No se pudo conectar a la base de datos: " . $err->getMessage();
  }
?>
