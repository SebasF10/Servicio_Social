<?php
    require 'modelo/conexion.php';

    session_start();

    if(isset($_SESSION['username']))
    {
        $nombre_usuario = $_SESSION['username'];
        
        // Obtener datos del administrador
        $query = "SELECT nombre, apellidos FROM administrador WHERE correo = '$nombre_usuario'";
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
    <title>Panel Administrador</title>
</head>
<body>
    <h1>Panel de Administrador</h1>
    <hr>
    <?php
        if(isset($datos['nombre']) && isset($datos['apellidos'])) {
            echo 'Bienvenido/a: ' . $datos['nombre'] . ' ' . $datos['apellidos'] . ' (' . $nombre_usuario . ')';
        } else {
            echo 'Usuario: ' . $nombre_usuario;
        }
    ?>
    <hr>
    <h2>Opciones de Administrador</h2>
    <ul>
        <li><a href="gestionar_estudientes.php">Gestionar Estudiantes</a></li>
        <li><a href="gestionar_acudientes.php">Gestionar Acudientes</a></li>
        <li><a href="gestionar_administradores.php">Gestionar Administradores</a></li>
        <li><a href="gestionar_supervisores.php">Gestionar Supervisores</a></li>
        <li><a href="gestionar_grupos.php">Gestionar_grupos</a></li>
        <li><a href="#">Soporte PDF</a></li>
    </ul>
    <hr>
    <a href="modelo/cerrar_sesion.php">Cerrar Sesión</a>
</body>
</html>