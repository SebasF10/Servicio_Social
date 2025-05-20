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

    // Obtener lista de sedes para el formulario
    $query_sedes = "SELECT id_sede, nombre_sede FROM sede ORDER BY nombre_sede";
    $resultado_sedes = mysqli_query($conexion, $query_sedes);

    // Procesar formulario de agregar
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar'])) {
        $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
        $apellidos = mysqli_real_escape_string($conexion, $_POST['apellidos']);
        $doc_identidad = mysqli_real_escape_string($conexion, $_POST['doc_identidad']);
        $telefono = mysqli_real_escape_string($conexion, $_POST['telefono']);
        $correo = mysqli_real_escape_string($conexion, $_POST['correo']);
        $contrasena = mysqli_real_escape_string($conexion, $_POST['contrasena']);
        $id_sede = mysqli_real_escape_string($conexion, $_POST['id_sede']);
        $dependencia = mysqli_real_escape_string($conexion, $_POST['dependencia']);
        
        // Verificar si el correo ya existe
        $verificar = "SELECT * FROM supervisor WHERE correo = '$correo'";
        $resultado_verificar = mysqli_query($conexion, $verificar);
        
        if (mysqli_num_rows($resultado_verificar) > 0) {
            $mensaje = "El correo electrónico ya está registrado";
        } else {
            // Insertamos el nuevo supervisor
            $insertar = "INSERT INTO supervisor (nombre, apellidos, doc_identidad, telefono, correo, contraseña, id_sede, dependencia) 
                        VALUES ('$nombre', '$apellidos', '$doc_identidad', '$telefono', '$correo', '$contrasena', '$id_sede', '$dependencia')";
            
            if (mysqli_query($conexion, $insertar)) {
                $mensaje = "Supervisor agregado correctamente";
            } else {
                $mensaje = "Error al agregar supervisor: " . mysqli_error($conexion);
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Supervisor</title>
</head>
<body>
    <h1>Agregar Supervisor</h1>
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

    <h2>Agregar Nuevo Supervisor</h2>
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
            <label for="id_sede">Sede:</label>
            <select id="id_sede" name="id_sede" required>
                <option value="">Seleccione una sede</option>
                <?php while($sede = mysqli_fetch_assoc($resultado_sedes)): ?>
                    <option value="<?php echo $sede['id_sede']; ?>">
                        <?php echo $sede['nombre_sede']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label for="dependencia">Dependencia:</label>
            <input type="text" id="dependencia" name="dependencia" required>
        </div>
        <div>
            <button type="submit" name="agregar">Agregar Supervisor</button>
        </div>
    </form>
    
    <hr>
    <a href="ver_supervisores.php">Ver Lista de Supervisores</a>
    <br>
    <a href="pagina_administrador.php">Volver al Panel de Administrador</a>
</body>
</html>