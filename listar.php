<?php

include("conexion.php");

$sql = "SELECT * FROM tareas";

$resultado = mysqli_query($conexion, $sql);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Lista de Tareas</title>

    <style>

        body{
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }

        h1{
            text-align: center;
        }

        table{
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        th, td{
            border: 1px solid #ccc;
            padding: 12px;
            text-align: center;
        }

        th{
            background-color: #007BFF;
            color: white;
        }

        .enlace-volver{
            display: inline-block;
            margin-bottom: 15px;
            padding: 10px 16px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .enlace-volver:hover{
            background-color: #0056b3;
        }

    </style>

</head>

<body>

    <h1>Lista de Tareas</h1>

    <a href="index.php" class="enlace-volver">&larr; Volver a registrar tarea</a>

    <table>

        <tr>
            <th>ID</th>
            <th>Título</th>
            <th>Descripción</th>
        </tr>

        <?php while($fila = mysqli_fetch_assoc($resultado)){ ?>

        <tr>
            <td><?php echo $fila['id']; ?></td>
            <td><?php echo $fila['titulo']; ?></td>
            <td><?php echo $fila['descripcion']; ?></td>
        </tr>

        <?php } ?>

    </table>

</body>

</html>