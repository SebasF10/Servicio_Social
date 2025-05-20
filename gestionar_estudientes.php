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

    // Obtener lista de acudientes para el formulario
    $query_acudientes = "SELECT id_acudiente, nombre, apellidos FROM acudiente ORDER BY nombre, apellidos";
    $resultado_acudientes = mysqli_query($conexion, $query_acudientes);

    // Procesar formulario de agregar
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar'])) {
        $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
        $apellidos = mysqli_real_escape_string($conexion, $_POST['apellidos']);
        $doc_identidad = mysqli_real_escape_string($conexion, $_POST['doc_identidad']);
        $telefono = mysqli_real_escape_string($conexion, $_POST['telefono']);
        $correo = mysqli_real_escape_string($conexion, $_POST['correo']);
        $contrasena = mysqli_real_escape_string($conexion, $_POST['contrasena']);
        $id_acudiente = mysqli_real_escape_string($conexion, $_POST['id_acudiente']);
        
        // Verificar si el correo ya existe
        $verificar = "SELECT * FROM estudiante WHERE correo = '$correo'";
        $resultado_verificar = mysqli_query($conexion, $verificar);
        
        if (mysqli_num_rows($resultado_verificar) > 0) {
            $mensaje = "El correo electrónico ya está registrado";
        } else {
            // Insertamos el nuevo estudiante
            $insertar = "INSERT INTO estudiante (nombre, apellidos, doc_identidad, telefono, correo, contraseña, id_acudiente) 
                        VALUES ('$nombre', '$apellidos', '$doc_identidad', '$telefono', '$correo', '$contrasena', '$id_acudiente')";
            
            if (mysqli_query($conexion, $insertar)) {
                $mensaje = "Estudiante agregado correctamente";
            } else {
                $mensaje = "Error al agregar estudiante: " . mysqli_error($conexion);
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Estudiante</title>
</head>
<body>
    <h1>Agregar Estudiante</h1>
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

    <h2>Agregar Nuevo Estudiante</h2>
    <form method="POST" action="">
        <div>
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" required>
        </div>
        <div>
            <label for="apellidos">Apellidos:</label>
            <input type="text" id="apellidos" name="apellidos" required>
        </div>
        <div>
            <label for="doc_identidad">Documento de Identidad:</label>
            <input type="text" id="doc_identidad" name="doc_identidad" required>
        </div>
        <div>
            <label for="telefono">Teléfono:</label>
            <input type="text" id="telefono" name="telefono" required>
        </div>
        <div>
            <label for="correo">Correo Electrónico:</label>
            <input type="email" id="correo" name="correo" required>
        </div>
        <div>
            <label for="contrasena">Contraseña:</label>
            <input type="password" id="contrasena" name="contrasena" required>
        </div>
        <div>
            <label for="id_acudiente">Acudiente:</label>
            <select id="id_acudiente" name="id_acudiente" required>
                <option value="">Seleccione un acudiente</option>
                <?php while($acudiente = mysqli_fetch_assoc($resultado_acudientes)): ?>
                    <option value="<?php echo $acudiente['id_acudiente']; ?>">
                        <?php echo $acudiente['nombre'] . ' ' . $acudiente['apellidos']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <button type="submit" name="agregar">Agregar Estudiante</button>
        </div>
    </form>
    
    <hr>
    <a href="ver_estudiantes.php">Ver Lista de Estudiantes</a>
    <br>
    <a href="pagina_administrador.php">Volver al Panel de Administrador</a>
</body>
</html>