<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tareas</title>

    <style>

        body{
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .contenedor{
            width: 500px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.2);
        }

        h1{
            text-align: center;
            color: #333;
        }

        label{
            font-weight: bold;
        }

        input, textarea{
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button{
            width: 100%;
            padding: 12px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover{
            background-color: #0056b3;
        }

    </style>

</head>

<body>

    <div class="contenedor">

        <h1>Gestión de Tareas</h1>

        <form action="guardar.php" method="POST">

            <label for="titulo">Título de la tarea</label>
            <input type="text" name="titulo" id="titulo" required>

            <label for="descripcion">Descripción</label>
            <textarea name="descripcion" id="descripcion" rows="5" required></textarea>

            <button type="submit">Guardar Tarea</button>

        </form>

    </div>

</body>

</html>