<?php

$servidor = "localhost";
$usuario = "root";
$password = "";
$baseDatos = "sublimacionv3";

$conn = new mysqli($servidor, $usuario, $password, $baseDatos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>
