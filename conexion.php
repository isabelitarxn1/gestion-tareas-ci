<?php
// Conexión a la base de datos
// Las variables de entorno se usan cuando está en Docker,
// con fallback a valores por defecto para desarrollo local

$host     = getenv('DB_HOST')     ?: 'db';
$usuario  = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'root';
$base     = getenv('DB_NAME')     ?: 'gestion_tareas_ci';

$conn = new mysqli($host, $usuario, $password, $base);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8");

$conexion = $conn;
?>