<?php
$usuario='pucho';
$pass='78900';
// Configuración de la conexión a la base de datos
$host = 'localhost';
$user =$usuario;
$password = $pass;
$database = 'cabal';

// Conexión a la base de datos
$conexion = new mysqli($host, $user, $password, $database);

// Comprobar si hay errores en la conexión
if ($conexion->connect_error) {
  die('Error de conexión a la base de datos: ' . $conexion->connect_error);
}

// Configuración de la codificación de caracteres
$conexion->set_charset('utf8');
?>
