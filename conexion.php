<?php

$host = "db";
$usuario = "root";
$password = "root";
$basedatos = "gestion_tareas_ci";

$conexion = mysqli_connect($host, $usuario, $password, $basedatos);

// Verificar conexión
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

?>