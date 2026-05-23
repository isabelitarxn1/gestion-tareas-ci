<?php

include("conexion.php");

// Recibir datos del formulario
$titulo = $_POST['titulo'];
$descripcion = $_POST['descripcion'];

// Consulta SQL
$sql = "INSERT INTO tareas (titulo, descripcion)
VALUES ('$titulo', '$descripcion')";

// Ejecutar consulta
$resultado = mysqli_query($conexion, $sql);

// Verificar
if ($resultado) {
    echo "Tarea guardada correctamente";
} else {
    echo "Error al guardar: " . mysqli_error($conexion);
}

?>