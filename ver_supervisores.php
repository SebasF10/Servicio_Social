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
        // Eliminar supervisor
        if (isset($_POST['eliminar'])) {
            $id_supervisor = mysqli_real_escape_string($conexion, $_POST['id_supervisor']);
            
            // Comprobar si tiene registros asociados en otras tablas
            $verificar = "SELECT * FROM apoyo WHERE id_supervisor = $id_supervisor";
            $resultado_verificar = mysqli_query($conexion, $verificar);
            
            if (mysqli_num_rows($resultado_verificar) > 0) {
                $mensaje = "No se puede eliminar este supervisor porque tiene registros asociados";
            } else {
                $eliminar = "DELETE FROM supervisor WHERE id_supervisor = $id_supervisor";
                
                if (mysqli_query($conexion, $eliminar)) {
                    $mensaje = "Supervisor eliminado correctamente";
                } else {
                    $mensaje = "Error al eliminar supervisor: " . mysqli_error($conexion);
                }
            }
        }
        
        // Actualizar supervisor
        if (isset($_POST['actualizar'])) {
            $id_supervisor = mysqli_real_escape_string($conexion, $_POST['id_supervisor']);
            $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
            $apellidos = mysqli_real_escape_string($conexion, $_POST['apellidos']);
            $doc_identidad = mysqli_real_escape_string($conexion, $_POST['doc_identidad']);
            $telefono = mysqli_real_escape_string($conexion, $_POST['telefono']);
            $correo = mysqli_real_escape_string($conexion, $_POST['correo']);
            $contrasena = mysqli_real_escape_string($conexion, $_POST['contrasena']);
            $id_sede = mysqli_real_escape_string($conexion, $_POST['id_sede']);
            $dependencia = mysqli_real_escape_string($conexion, $_POST['dependencia']);
            
            // Verificar si el correo ya existe (pero no es el mismo supervisor)
            $verificar = "SELECT * FROM supervisor WHERE correo = '$correo' AND id_supervisor != $id_supervisor";
            $resultado_verificar = mysqli_query($conexion, $verificar);
            
            if (mysqli_num_rows($resultado_verificar) > 0) {
                $mensaje = "El correo electrónico ya está registrado con otro supervisor";
            } else {
                $actualizar = "UPDATE supervisor SET 
                              nombre = '$nombre', 
                              apellidos = '$apellidos', 
                              doc_identidad = '$doc_identidad', 
                              telefono = '$telefono',
                              correo = '$correo',
                              id_sede = $id_sede,
                              dependencia = '$dependencia'";
                
                // Solo actualiza la contraseña si se proporciona una nueva
                if (!empty($contrasena)) {
                    $actualizar .= ", contraseña = '$contrasena'";
                }
                
                $actualizar .= " WHERE id_supervisor = $id_supervisor";
                
                if (mysqli_query($conexion, $actualizar)) {
                    $mensaje = "Supervisor actualizado correctamente";
                } else {
                    $mensaje = "Error al actualizar supervisor: " . mysqli_error($conexion);
                }
            }
        }
    }

    // Obtener datos de supervisores para la tabla (con búsqueda si aplica)
    $query_supervisores = "SELECT s.*, sd.nombre_sede 
                         FROM supervisor s 
                         LEFT JOIN sede sd ON s.id_sede = sd.id_sede";
    
    // Añadir condición de búsqueda si existe
    if (!empty($busqueda)) {
        $query_supervisores .= " WHERE s.nombre LIKE '%$busqueda%' OR s.apellidos LIKE '%$busqueda%'";
    }
    
    $query_supervisores .= " ORDER BY s.nombre, s.apellidos";
    $resultado_supervisores = mysqli_query($conexion, $query_supervisores);
    
    // Obtener lista de sedes para el formulario de edición
    $query_sedes = "SELECT id_sede, nombre_sede FROM sede ORDER BY nombre_sede";
    $resultado_sedes = mysqli_query($conexion, $query_sedes);
    
    // Crear un array para usar en los formularios de edición
    $sedes = array();
    while ($sede = mysqli_fetch_assoc($resultado_sedes)) {
        $sedes[] = $sede;
    }
    
    // Debug: Comprobar cuántos supervisores hay en la base de datos
    $query_total = "SELECT COUNT(*) as total FROM supervisor";
    $resultado_total = mysqli_query($conexion, $query_total);
    $datos_total = mysqli_fetch_assoc($resultado_total);
    $total_supervisores = $datos_total['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Supervisores</title>
</head>
<body>
    <h1>Lista de Supervisores</h1>
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
        <p>Total de supervisores en la base de datos: <?php echo $total_supervisores; ?></p>
    </div>

    <!-- Formulario de búsqueda -->
    <h2>Buscar Supervisores</h2>
    <form method="GET" action="">
        <div>
            <label for="busqueda">Buscar por nombre:</label>
            <input type="text" id="busqueda" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
            <button type="submit" name="buscar">Buscar</button>
            <?php if (!empty($busqueda)): ?>
                <a href="ver_supervisores.php">Limpiar búsqueda</a>
            <?php endif; ?>
        </div>
    </form>

    <h2>Lista de Supervisores</h2>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>Documento ID</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Sede</th>
                <th>Dependencia</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Verificar si hay resultados
            if (mysqli_num_rows($resultado_supervisores) > 0) {
                while($supervisor = mysqli_fetch_assoc($resultado_supervisores)): 
            ?>
            <tr>
                <td><?php echo $supervisor['id_supervisor']; ?></td>
                <td><?php echo $supervisor['nombre']; ?></td>
                <td><?php echo $supervisor['apellidos']; ?></td>
                <td><?php echo $supervisor['doc_identidad']; ?></td>
                <td><?php echo $supervisor['telefono']; ?></td>
                <td><?php echo $supervisor['correo']; ?></td>
                <td>
                    <?php 
                    if($supervisor['id_sede']) {
                        echo $supervisor['nombre_sede'];
                    } else {
                        echo "Sin sede asignada";
                    }
                    ?>
                </td>
                <td><?php echo $supervisor['dependencia']; ?></td>
                <td>
                    <form method="POST" action="">
                        <input type="hidden" name="id_supervisor" value="<?php echo $supervisor['id_supervisor']; ?>">
                        <button type="submit" name="eliminar">Eliminar</button>
                    </form>
                    
                    <button onclick="mostrarFormularioEdicion(<?php echo $supervisor['id_supervisor']; ?>)">Editar</button>
                    
                    <div id="editar-<?php echo $supervisor['id_supervisor']; ?>" style="display: none;">
                        <form method="POST" action="">
                            <input type="hidden" name="id_supervisor" value="<?php echo $supervisor['id_supervisor']; ?>">
                            <div>
                                <label>Nombre:</label>
                                <input type="text" name="nombre" value="<?php echo $supervisor['nombre']; ?>" required>
                            </div>
                            <div>
                                <label>Apellidos:</label>
                                <input type="text" name="apellidos" value="<?php echo $supervisor['apellidos']; ?>" required>
                            </div>
                            <div>
                                <label>Documento de Identidad:</label>
                                <input type="text" name="doc_identidad" value="<?php echo $supervisor['doc_identidad']; ?>" required>
                            </div>
                            <div>
                                <label>Teléfono:</label>
                                <input type="text" name="telefono" value="<?php echo $supervisor['telefono']; ?>" required>
                            </div>
                            <div>
                                <label>Correo:</label>
                                <input type="email" name="correo" value="<?php echo $supervisor['correo']; ?>" required>
                            </div>
                            <div>
                                <label>Nueva contraseña (dejar en blanco para mantener la actual):</label>
                                <input type="text" name="contrasena">
                            </div>
                            <div>
                                <label>Sede:</label>
                                <select name="id_sede" required>
                                    <option value="">Seleccione una sede</option>
                                    <?php foreach($sedes as $sede): ?>
                                        <option value="<?php echo $sede['id_sede']; ?>" <?php echo ($supervisor['id_sede'] == $sede['id_sede']) ? 'selected' : ''; ?>>
                                            <?php echo $sede['nombre_sede']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label>Dependencia:</label>
                                <input type="text" name="dependencia" value="<?php echo $supervisor['dependencia']; ?>" required>
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
                echo "<tr><td colspan='9'>No se encontraron supervisores</td></tr>";
            }
            ?>
        </tbody>
    </table>
    
    <hr>
    <a href="gestionar_supervisores.php">Agregar Nuevo Supervisor</a>
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