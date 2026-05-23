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

    </style>

</head>

<body>

    <h1>Lista de Tareas</h1>

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