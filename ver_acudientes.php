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
    $busqueda = '';

    // Procesar formulario de búsqueda
    if(isset($_GET['buscar'])) {
        $busqueda = mysqli_real_escape_string($conexion, $_GET['busqueda']);
    }

    // Procesar formularios de edición y eliminación
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Eliminar acudiente
        if (isset($_POST['eliminar'])) {
            $id_acudiente = mysqli_real_escape_string($conexion, $_POST['id_acudiente']);
            
            // Comprobar si tiene estudiantes asociados
            $verificar = "SELECT * FROM estudiante WHERE id_acudiente = $id_acudiente";
            $resultado_verificar = mysqli_query($conexion, $verificar);
            
            if (mysqli_num_rows($resultado_verificar) > 0) {
                $mensaje = "No se puede eliminar este acudiente porque tiene estudiantes asociados";
            } else {
                $eliminar = "DELETE FROM acudiente WHERE id_acudiente = $id_acudiente";
                
                if (mysqli_query($conexion, $eliminar)) {
                    $mensaje = "Acudiente eliminado correctamente";
                } else {
                    $mensaje = "Error al eliminar acudiente: " . mysqli_error($conexion);
                }
            }
        }
        
        // Actualizar acudiente
        if (isset($_POST['actualizar'])) {
            $id_acudiente = mysqli_real_escape_string($conexion, $_POST['id_acudiente']);
            $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
            $apellidos = mysqli_real_escape_string($conexion, $_POST['apellidos']);
            $cedula = mysqli_real_escape_string($conexion, $_POST['cedula']);
            $correo = mysqli_real_escape_string($conexion, $_POST['correo']);
            $telefono = mysqli_real_escape_string($conexion, $_POST['telefono']);
            $contrasena = mysqli_real_escape_string($conexion, $_POST['contrasena']);
            
            // Verificar si el correo ya existe (pero no es el mismo acudiente)
            $verificar = "SELECT * FROM acudiente WHERE correo = '$correo' AND id_acudiente != $id_acudiente";
            $resultado_verificar = mysqli_query($conexion, $verificar);
            
            if (mysqli_num_rows($resultado_verificar) > 0) {
                $mensaje = "El correo electrónico ya está registrado con otro acudiente";
            } else {
                $actualizar = "UPDATE acudiente SET 
                              nombre = '$nombre', 
                              apellidos = '$apellidos', 
                              doc_identidad = '$cedula', 
                              correo = '$correo', 
                              telefono = '$telefono'";
                
                // Solo actualiza la contraseña si se proporciona una nueva
                if (!empty($contrasena)) {
                    $actualizar .= ", contraseña = '$contrasena'";
                }
                
                $actualizar .= " WHERE id_acudiente = $id_acudiente";
                
                if (mysqli_query($conexion, $actualizar)) {
                    $mensaje = "Acudiente actualizado correctamente";
                } else {
                    $mensaje = "Error al actualizar acudiente: " . mysqli_error($conexion);
                }
            }
        }
    }

    // Obtener datos de acudientes para la tabla (con búsqueda si aplica)
    $query_acudientes = "SELECT * FROM acudiente";
    
    // Añadir condición de búsqueda si existe
    if (!empty($busqueda)) {
        $query_acudientes .= " WHERE nombre LIKE '%$busqueda%' OR apellidos LIKE '%$busqueda%'";
    }
    
    $query_acudientes .= " ORDER BY nombre, apellidos";
    $resultado_acudientes = mysqli_query($conexion, $query_acudientes);
    
    // Debug: Comprobar cuántos acudientes hay en la base de datos
    $query_total = "SELECT COUNT(*) as total FROM acudiente";
    $resultado_total = mysqli_query($conexion, $query_total);
    $datos_total = mysqli_fetch_assoc($resultado_total);
    $total_acudientes = $datos_total['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Acudientes</title>
</head>
<body>
    <h1>Lista de Acudientes</h1>
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
    
    <!-- Información de debug -->
    <div>
        <p>Total de acudientes en la base de datos: <?php echo $total_acudientes; ?></p>
    </div>

    <!-- Formulario de búsqueda -->
    <h2>Buscar Acudientes</h2>
    <form method="GET" action="">
        <div>
            <label for="busqueda">Buscar por nombre:</label>
            <input type="text" id="busqueda" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
            <button type="submit" name="buscar">Buscar</button>
            <?php if (!empty($busqueda)): ?>
                <a href="ver_acudientes.php">Limpiar búsqueda</a>
            <?php endif; ?>
        </div>
    </form>

    <h2>Lista de Acudientes</h2>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>Documento ID</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Contraseña</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Verificar si hay resultados
            if (mysqli_num_rows($resultado_acudientes) > 0) {
                while($acudiente = mysqli_fetch_assoc($resultado_acudientes)): 
            ?>
            <tr>
                <td><?php echo $acudiente['id_acudiente']; ?></td>
                <td><?php echo $acudiente['nombre']; ?></td>
                <td><?php echo $acudiente['apellidos']; ?></td>
                <td><?php echo $acudiente['doc_identidad']; ?></td>
                <td><?php echo $acudiente['correo']; ?></td>
                <td><?php echo $acudiente['telefono']; ?></td>
                <td><?php echo $acudiente['contraseña']; ?></td>
                <td>
                    <form method="POST" action="">
                        <input type="hidden" name="id_acudiente" value="<?php echo $acudiente['id_acudiente']; ?>">
                        <button type="submit" name="eliminar">Eliminar</button>
                    </form>
                    
                    <button onclick="mostrarFormularioEdicion(<?php echo $acudiente['id_acudiente']; ?>)">Editar</button>
                    
                    <div id="editar-<?php echo $acudiente['id_acudiente']; ?>" style="display: none;">
                        <form method="POST" action="">
                            <input type="hidden" name="id_acudiente" value="<?php echo $acudiente['id_acudiente']; ?>">
                            <div>
                                <label>Nombre:</label>
                                <input type="text" name="nombre" value="<?php echo $acudiente['nombre']; ?>" required>
                            </div>
                            <div>
                                <label>Apellidos:</label>
                                <input type="text" name="apellidos" value="<?php echo $acudiente['apellidos']; ?>" required>
                            </div>
                            <div>
                                <label>Documento de Identidad:</label>
                                <input type="text" name="cedula" value="<?php echo $acudiente['doc_identidad']; ?>" required>
                            </div>
                            <div>
                                <label>Correo:</label>
                                <input type="email" name="correo" value="<?php echo $acudiente['correo']; ?>" required>
                            </div>
                            <div>
                                <label>Teléfono:</label>
                                <input type="text" name="telefono" value="<?php echo $acudiente['telefono']; ?>" required>
                            </div>
                            <div>
                                <label>Nueva contraseña (dejar en blanco para mantener la actual):</label>
                                <input type="text" name="contrasena">
                            </div>
                            <div>
                                <button type="submit" name="actualizar">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </td>
            </tr>
            <?php 
                endwhile; 
            } else {
                echo "<tr><td colspan='8'>No se encontraron acudientes</td></tr>";
            }
            ?>
        </tbody>
    </table>
    
    <hr>
    <a href="gestionar_acudientes.php">Agregar Nuevo Acudiente</a>
    <br>
    <a href="pagina_administrador.php">Volver al Panel de Administrador</a>

    <script>
        function mostrarFormularioEdicion(id) {
            var formulario = document.getElementById('editar-' + id);
            if (formulario.style.display === 'none') {
                formulario.style.display = 'block';
            } else {
                formulario.style.display = 'none';
            }
        }
    </script>
</body>
</html>