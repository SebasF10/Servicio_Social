<?php
    require 'modelo/conexion.php';

    session_start();

    // Verificar si existe una sesión de administrador
    if(!isset($_SESSION['username']))
    {
        header("location: index.php");
        exit();
    }

    $nombre_usuario = $_SESSION['username'];
    
    // Obtener datos del administrador
    $query = "SELECT nombre, apellidos FROM administrador WHERE correo = '$nombre_usuario'";
    $resultado = mysqli_query($conexion, $query);
    $datos = mysqli_fetch_array($resultado);

    // Inicializar variables
    $mensaje = '';

    // Obtener lista de grados para el formulario
    $query_grados = "SELECT id_grado, nombre FROM grado ORDER BY nombre";
    $resultado_grados = mysqli_query($conexion, $query_grados);

    // Procesar formulario de agregar
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar'])) {
        $id_grupo = mysqli_real_escape_string($conexion, $_POST['id_grupo']);
        $nombre_grupo = mysqli_real_escape_string($conexion, $_POST['nombre_grupo']);
        $id_grado = mysqli_real_escape_string($conexion, $_POST['id_grado']);
        
        // Verificar si el grupo ya existe
        $verificar = "SELECT * FROM grupo WHERE id_grupo = '$id_grupo'";
        $resultado_verificar = mysqli_query($conexion, $verificar);
        
        if (mysqli_num_rows($resultado_verificar) > 0) {
            $mensaje = "El ID del grupo ya está registrado";
        } else {
            // Insertar el nuevo grupo con nombre
            $insertar = "INSERT INTO grupo (id_grupo, nombre, id_grado) VALUES ('$id_grupo', '$nombre_grupo', '$id_grado')";
            
            if (mysqli_query($conexion, $insertar)) {
                $mensaje = "Grupo agregado correctamente";
            } else {
                $mensaje = "Error al agregar grupo: " . mysqli_error($conexion);
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Grupo</title>
</head>
<body>
    <h1>Agregar Grupo</h1>
    <hr>
    <?php
        if(isset($datos['nombre']) && isset($datos['apellidos'])) {
            echo 'Administrador: ' . $datos['nombre'] . ' ' . $datos['apellidos'] . ' (' . $nombre_usuario . ')';
        } else {
            echo 'Usuario: ' . $nombre_usuario;
        }
    ?>
    <hr>

    <?php if(!empty($mensaje)): ?>
        <div>
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <h2>Agregar Nuevo Grupo</h2>
    <form method="POST" action="">
        <div>
            <label for="id_grupo">ID del Grupo:</label>
            <input type="text" id="id_grupo" name="id_grupo" required>
        </div>
        <div>
            <label for="nombre_grupo">Nombre del Grupo:</label>
            <input type="text" id="nombre_grupo" name="nombre_grupo" placeholder="pon el nombre aqui" required>
        </div>
        <div>
            <label for="id_grado">Grado:</label>
            <select id="id_grado" name="id_grado" required>
                <option value="">Seleccione un grado</option>
                <?php while($grado = mysqli_fetch_assoc($resultado_grados)): ?>
                    <option value="<?php echo $grado['id_grado']; ?>">
                        <?php echo $grado['nombre']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <button type="submit" name="agregar">Agregar Grupo</button>
        </div>
    </form>
    
    <hr>
    <a href="ver_grupos.php">Ver Lista de Grupos</a>
    <br>
    <a href="pagina_administrador.php">Volver al Panel de Administrador</a>
</body>
</html>