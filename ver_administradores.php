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
        // Eliminar administrador
        if (isset($_POST['eliminar'])) {
            $id_administrador = mysqli_real_escape_string($conexion, $_POST['id_administrador']);
            
            // No permitir que un administrador se elimine a sí mismo
            $query_verificar = "SELECT correo FROM administrador WHERE id_administrador = $id_administrador";
            $resultado_verificar = mysqli_query($conexion, $query_verificar);
            $admin_a_eliminar = mysqli_fetch_assoc($resultado_verificar);
            
            if ($admin_a_eliminar['correo'] == $nombre_usuario) {
                $mensaje = "No puedes eliminar tu propia cuenta";
            } else {
                $eliminar = "DELETE FROM administrador WHERE id_administrador = $id_administrador";
                
                if (mysqli_query($conexion, $eliminar)) {
                    $mensaje = "Administrador eliminado correctamente";
                } else {
                    $mensaje = "Error al eliminar administrador: " . mysqli_error($conexion);
                }
            }
        }
        
        // Actualizar administrador
        if (isset($_POST['actualizar'])) {
            $id_administrador = mysqli_real_escape_string($conexion, $_POST['id_administrador']);
            $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
            $apellidos = mysqli_real_escape_string($conexion, $_POST['apellidos']);
            $doc_identidad = mysqli_real_escape_string($conexion, $_POST['doc_identidad']);
            $correo = mysqli_real_escape_string($conexion, $_POST['correo']);
            $telefono = mysqli_real_escape_string($conexion, $_POST['telefono']);
            $contrasena = mysqli_real_escape_string($conexion, $_POST['contrasena']);
            
            // Verificar si el correo ya existe (pero no es el mismo administrador)
            $verificar = "SELECT * FROM administrador WHERE correo = '$correo' AND id_administrador != $id_administrador";
            $resultado_verificar = mysqli_query($conexion, $verificar);
            
            if (mysqli_num_rows($resultado_verificar) > 0) {
                $mensaje = "El correo electrónico ya está registrado con otro administrador";
            } else {
                $actualizar = "UPDATE administrador SET 
                              nombre = '$nombre', 
                              apellidos = '$apellidos', 
                              doc_identidad = '$doc_identidad', 
                              correo = '$correo', 
                              telefono = '$telefono'";
                
                // Solo actualiza la contraseña si se proporciona una nueva
                if (!empty($contrasena)) {
                    $actualizar .= ", contraseña = '$contrasena'";
                }
                
                $actualizar .= " WHERE id_administrador = $id_administrador";
                
                if (mysqli_query($conexion, $actualizar)) {
                    $mensaje = "Administrador actualizado correctamente";
                } else {
                    $mensaje = "Error al actualizar administrador: " . mysqli_error($conexion);
                }
            }
        }
    }

    // Obtener datos de administradores para la tabla (con búsqueda si aplica)
    $query_administradores = "SELECT * FROM administrador";
    
    // Añadir condición de búsqueda si existe
    if (!empty($busqueda)) {
        $query_administradores .= " WHERE nombre LIKE '%$busqueda%' OR apellidos LIKE '%$busqueda%'";
    }
    
    $query_administradores .= " ORDER BY nombre, apellidos";
    $resultado_administradores = mysqli_query($conexion, $query_administradores);
    
    // Debug: Comprobar cuántos administradores hay en la base de datos
    $query_total = "SELECT COUNT(*) as total FROM administrador";
    $resultado_total = mysqli_query($conexion, $query_total);
    $datos_total = mysqli_fetch_assoc($resultado_total);
    $total_administradores = $datos_total['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Administradores</title>
</head>
<body>
    <h1>Lista de Administradores</h1>
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
        <p>Total de administradores en la base de datos: <?php echo $total_administradores; ?></p>
    </div>

    <!-- Formulario de búsqueda -->
    <h2>Buscar Administradores</h2>
    <form method="GET" action="">
        <div>
            <label for="busqueda">Buscar por nombre:</label>
            <input type="text" id="busqueda" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
            <button type="submit" name="buscar">Buscar</button>
            <?php if (!empty($busqueda)): ?>
                <a href="ver_administradores.php">Limpiar búsqueda</a>
            <?php endif; ?>
        </div>
    </form>

    <h2>Lista de Administradores</h2>
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
            if (mysqli_num_rows($resultado_administradores) > 0) {
                while($administrador = mysqli_fetch_assoc($resultado_administradores)): 
            ?>
            <tr>
                <td><?php echo $administrador['id_administrador']; ?></td>
                <td><?php echo $administrador['nombre']; ?></td>
                <td><?php echo $administrador['apellidos']; ?></td>
                <td><?php echo $administrador['doc_identidad']; ?></td>
                <td><?php echo $administrador['correo']; ?></td>
                <td><?php echo $administrador['telefono']; ?></td>
                <td><?php echo $administrador['contraseña']; ?></td>
                <td>
                    <?php if($administrador['correo'] != $nombre_usuario): // No mostrar botón eliminar para el propio usuario ?>
                    <form method="POST" action="">
                        <input type="hidden" name="id_administrador" value="<?php echo $administrador['id_administrador']; ?>">
                        <button type="submit" name="eliminar">Eliminar</button>
                    </form>
                    <?php endif; ?>
                    
                    <button onclick="mostrarFormularioEdicion(<?php echo $administrador['id_administrador']; ?>)">Editar</button>
                    
                    <div id="editar-<?php echo $administrador['id_administrador']; ?>" style="display: none;">
                        <form method="POST" action="">
                            <input type="hidden" name="id_administrador" value="<?php echo $administrador['id_administrador']; ?>">
                            <div>
                                <label>Nombre:</label>
                                <input type="text" name="nombre" value="<?php echo $administrador['nombre']; ?>" required>
                            </div>
                            <div>
                                <label>Apellidos:</label>
                                <input type="text" name="apellidos" value="<?php echo $administrador['apellidos']; ?>" required>
                            </div>
                            <div>
                                <label>Documento de Identidad:</label>
                                <input type="text" name="doc_identidad" value="<?php echo $administrador['doc_identidad']; ?>" required>
                            </div>
                            <div>
                                <label>Correo:</label>
                                <input type="email" name="correo" value="<?php echo $administrador['correo']; ?>" required>
                            </div>
                            <div>
                                <label>Teléfono:</label>
                                <input type="text" name="telefono" value="<?php echo $administrador['telefono']; ?>" required>
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
                echo "<tr><td colspan='8'>No se encontraron administradores</td></tr>";
            }
            ?>
        </tbody>
    </table>
    
    <hr>
    <a href="gestionar_administradores.php">Agregar Nuevo Administrador</a>
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