<?php
    require 'modelo/conexion.php';

    session_start();

    if(isset($_SESSION['username']))
    {
        $nombre_usuario = $_SESSION['username'];
        
        // Obtener datos del estudiante
        $query = "SELECT nombre, apellidos FROM estudiante WHERE correo = '$nombre_usuario'";
        $resultado = mysqli_query($conexion, $query);
        $datos = mysqli_fetch_array($resultado);
    }
    else
    {
        // Si no hay sesión, redirigir al index
        header("location: index.php");
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Estudiante</title>
</head>
<body>
    <h1>Portal de Estudiante</h1>
    <hr>
    <?php
        if(isset($datos['nombre']) && isset($datos['apellidos'])) {
            echo 'Bienvenido/a: ' . $datos['nombre'] . ' ' . $datos['apellidos'] . ' (' . $nombre_usuario . ')';
        } else {
            echo 'Usuario: ' . $nombre_usuario;
        }
    ?>
    <hr>
    <h2>Mis Actividades</h2>
    <ul>
        <li><a href="#">Ver Mis Horas de Servicio</a></li>
        <li><a href="#">Enviar Solicitudes de Servicio</a></li>
        <li><a href="#">Consultar Estado de Solicitudes</a></li>
    </ul>
    <hr>
    <a href="modelo/cerrar_sesion.php">Cerrar Sesión</a>
</body>
</html>