<?php
    require "conexion.php";

    // iniciar sesion para guardar los datos del usuario
    session_start();

    $usuario = $_POST['email'];
    $contraseña = $_POST['contraseña'];

    $query_1 = "SELECT correo, COUNT(*) AS contar FROM acudiente WHERE correo = '$usuario' AND contraseña = '$contraseña'";

    $consulta = mysqli_query($conexion, $query_1) or trigger_error("Error en la consulta MYSQL: " + mysqli_error($conexion));

    $resultado = mysqli_fetch_array($consulta);

    if($resultado['contar'] > 0)
    {
        $_SESSION['username'] = $usuario;
        //redirigir el usuario a su pagina
        header("location: ../pagina_padre.php");

        /*echo "El usuario existe en la BD <br>";
        echo $resultado ['email'];*/
    }
    else
    {
        echo "El usuario no existe, o hay un error en el nombre de usuario o la contraseña";
    }
?>
