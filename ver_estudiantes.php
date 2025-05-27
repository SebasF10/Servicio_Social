
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
        // Eliminar estudiante
        if (isset($_POST['eliminar'])) {
            $id_estudiante = mysqli_real_escape_string($conexion, $_POST['id_estudiante']);
            
            // Comprobar si tiene registros asociados en otras tablas
            $verificar1 = "SELECT * FROM estudiante_apoyo WHERE id_estudiante = $id_estudiante";
            $resultado_verificar1 = mysqli_query($conexion, $verificar1);
            
            $verificar2 = "SELECT * FROM grupo_estudiante WHERE id_estudiante = $id_estudiante";
            $resultado_verificar2 = mysqli_query($conexion, $verificar2);
            
            if (mysqli_num_rows($resultado_verificar1) > 0 || mysqli_num_rows($resultado_verificar2) > 0) {
                $mensaje = "No se puede eliminar este estudiante porque tiene registros asociados";
            } else {
                $eliminar = "DELETE FROM estudiante WHERE id_estudiante = $id_estudiante";
                
                if (mysqli_query($conexion, $eliminar)) {
                    $mensaje = "Estudiante eliminado correctamente";
                } else {
                    $mensaje = "Error al eliminar estudiante: " . mysqli_error($conexion);
                }
            }
        }
        
        // Actualizar estudiante
        if (isset($_POST['actualizar'])) {
            $id_estudiante = mysqli_real_escape_string($conexion, $_POST['id_estudiante']);
            $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
            $apellidos = mysqli_real_escape_string($conexion, $_POST['apellidos']);
            $doc_identidad = mysqli_real_escape_string($conexion, $_POST['doc_identidad']);
            $telefono = mysqli_real_escape_string($conexion, $_POST['telefono']);
            $correo = mysqli_real_escape_string($conexion, $_POST['correo']);
            $contrasena = mysqli_real_escape_string($conexion, $_POST['contrasena']);
            $id_acudiente = mysqli_real_escape_string($conexion, $_POST['id_acudiente']);
            $id_grupo = mysqli_real_escape_string($conexion, $_POST['id_grupo']);
            
            // Verificar si el correo ya existe (pero no es el mismo estudiante)
            $verificar = "SELECT * FROM estudiante WHERE correo = '$correo' AND id_estudiante != $id_estudiante";
            $resultado_verificar = mysqli_query($conexion, $verificar);
            
            if (mysqli_num_rows($resultado_verificar) > 0) {
                $mensaje = "El correo electrónico ya está registrado con otro estudiante";
            } else {
                $actualizar = "UPDATE estudiante SET 
                              nombre = '$nombre', 
                              apellidos = '$apellidos', 
                              doc_identidad = '$doc_identidad', 
                              telefono = '$telefono',
                              correo = '$correo',
                              id_acudiente = $id_acudiente";
                
                // Solo actualiza la contraseña si se proporciona una nueva
                if (!empty($contrasena)) {
                    $actualizar .= ", contraseña = '$contrasena'";
                }
                
                $actualizar .= " WHERE id_estudiante = $id_estudiante";
                
                if (mysqli_query($conexion, $actualizar)) {
                    // Actualizar la asignación de grupo
                    // Primero eliminar la asignación actual
                    $eliminar_grupo = "DELETE FROM grupo_estudiante WHERE id_estudiante = $id_estudiante";
                    mysqli_query($conexion, $eliminar_grupo);
                    
                    // Si se seleccionó un grupo, crear la nueva relación
                    if (!empty($id_grupo)) {
                        $ano_actual = date('Y');
                        $insertar_grupo = "INSERT INTO grupo_estudiante (id_grupo, ano, id_estudiante) 
                                         VALUES ('$id_grupo', '$ano_actual', '$id_estudiante')";
                        mysqli_query($conexion, $insertar_grupo);
                    }
                    
                    $mensaje = "Estudiante actualizado correctamente";
                } else {
                    $mensaje = "Error al actualizar estudiante: " . mysqli_error($conexion);
                }
            }
        }
    }

    // Obtener datos de estudiantes para la tabla (con búsqueda si aplica)
    $query_estudiantes = "SELECT e.*, 
                                 a.nombre as nombre_acudiente, 
                                 a.apellidos as apellidos_acudiente,
                                 g.id_grupo,
                                 g.nombre as nombre_grupo,
                                 gr.nombre as nombre_grado
                         FROM estudiante e 
                         LEFT JOIN acudiente a ON e.id_acudiente = a.id_acudiente
                         LEFT JOIN grupo_estudiante ge ON e.id_estudiante = ge.id_estudiante
                         LEFT JOIN grupo g ON ge.id_grupo = g.id_grupo
                         LEFT JOIN grado gr ON g.id_grado = gr.id_grado";
    
    // Añadir condición de búsqueda si existe
    if (!empty($busqueda)) {
        $query_estudiantes .= " WHERE e.nombre LIKE '%$busqueda%' OR e.apellidos LIKE '%$busqueda%'";
    }
    
    $query_estudiantes .= " ORDER BY e.nombre, e.apellidos";
    $resultado_estudiantes = mysqli_query($conexion, $query_estudiantes);
    
    // Obtener lista de acudientes para el formulario de edición
    $query_acudientes = "SELECT id_acudiente, nombre, apellidos FROM acudiente ORDER BY nombre, apellidos";
    $resultado_acudientes = mysqli_query($conexion, $query_acudientes);
    
    // Crear un array para usar en los formularios de edición
    $acudientes = array();
    while ($acudiente = mysqli_fetch_assoc($resultado_acudientes)) {
        $acudientes[] = $acudiente;
    }
    
    // Obtener lista de grupos para el formulario de edición
    $query_grupos = "SELECT g.id_grupo, g.nombre, gr.nombre as nombre_grado 
                     FROM grupo g 
                     LEFT JOIN grado gr ON g.id_grado = gr.id_grado 
                     ORDER BY g.nombre";
    $resultado_grupos_edit = mysqli_query($conexion, $query_grupos);
    
    // Crear un array para usar en los formularios de edición
    $grupos = array();
    while ($grupo = mysqli_fetch_assoc($resultado_grupos_edit)) {
        $grupos[] = $grupo;
    }
    
    // Debug: Comprobar cuántos estudiantes hay en la base de datos
    $query_total = "SELECT COUNT(*) as total FROM estudiante";
    $resultado_total = mysqli_query($conexion, $query_total);
    $datos_total = mysqli_fetch_assoc($resultado_total);
    $total_estudiantes = $datos_total['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Estudiantes</title>
</head>
<body>
    <h1>Lista de Estudiantes</h1>
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
        <p>Total de estudiantes en la base de datos: <?php echo $total_estudiantes; ?></p>
    </div>

    <!-- Formulario de búsqueda -->
    <h2>Buscar Estudiantes</h2>
    <form method="GET" action="">
        <div>
            <label for="busqueda">Buscar por nombre:</label>
            <input type="text" id="busqueda" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
            <button type="submit" name="buscar">Buscar</button>
            <?php if (!empty($busqueda)): ?>
                <a href="ver_estudiantes.php">Limpiar búsqueda</a>
            <?php endif; ?>
        </div>
    </form>

    <h2>Lista de Estudiantes</h2>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>Documento ID</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Acudiente</th>
                <th>Grupo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Verificar si hay resultados
            if (mysqli_num_rows($resultado_estudiantes) > 0) {
                while($estudiante = mysqli_fetch_assoc($resultado_estudiantes)): 
            ?>
            <tr>
                <td><?php echo $estudiante['id_estudiante']; ?></td>
                <td><?php echo $estudiante['nombre']; ?></td>
                <td><?php echo $estudiante['apellidos']; ?></td>
                <td><?php echo $estudiante['doc_identidad']; ?></td>
                <td><?php echo $estudiante['telefono']; ?></td>
                <td><?php echo $estudiante['correo']; ?></td>
                <td>
                    <?php 
                    if($estudiante['id_acudiente']) {
                        echo $estudiante['nombre_acudiente'] . ' ' . $estudiante['apellidos_acudiente'];
                    } else {
                        echo "Sin acudiente asignado";
                    }
                    ?>
                </td>
                <td>
                    <?php 
                    if($estudiante['id_grupo']) {
                        echo $estudiante['nombre_grupo'] . ' (' . $estudiante['nombre_grado'] . ')';
                    } else {
                        echo "Sin grupo asignado";
                    }
                    ?>
                </td>
                <td>
                    <form method="POST" action="">
                        <input type="hidden" name="id_estudiante" value="<?php echo $estudiante['id_estudiante']; ?>">
                        <button type="submit" name="eliminar">Eliminar</button>
                    </form>
                    
                    <button onclick="mostrarFormularioEdicion(<?php echo $estudiante['id_estudiante']; ?>)">Editar</button>
                    
                    <div id="editar-<?php echo $estudiante['id_estudiante']; ?>" style="display: none;">
                        <form method="POST" action="">
                            <input type="hidden" name="id_estudiante" value="<?php echo $estudiante['id_estudiante']; ?>">
                            <div>
                                <label>Nombre:</label>
                                <input type="text" name="nombre" value="<?php echo $estudiante['nombre']; ?>" required>
                            </div>
                            <div>
                                <label>Apellidos:</label>
                                <input type="text" name="apellidos" value="<?php echo $estudiante['apellidos']; ?>" required>
                            </div>
                            <div>
                                <label>Documento de Identidad:</label>
                                <input type="text" name="doc_identidad" value="<?php echo $estudiante['doc_identidad']; ?>" required>
                            </div>
                            <div>
                                <label>Teléfono:</label>
                                <input type="text" name="telefono" value="<?php echo $estudiante['telefono']; ?>" required>
                            </div>
                            <div>
                                <label>Correo:</label>
                                <input type="email" name="correo" value="<?php echo $estudiante['correo']; ?>" required>
                            </div>
                            <div>
                                <label>Nueva contraseña (dejar en blanco para mantener la actual):</label>
                                <input type="text" name="contrasena">
                            </div>
                            <div>
                                <label>Acudiente:</label>
                                <select name="id_acudiente" required>
                                    <option value="">Seleccione un acudiente</option>
                                    <?php foreach($acudientes as $acudiente): ?>
                                        <option value="<?php echo $acudiente['id_acudiente']; ?>" <?php echo ($estudiante['id_acudiente'] == $acudiente['id_acudiente']) ? 'selected' : ''; ?>>
                                            <?php echo $acudiente['nombre'] . ' ' . $acudiente['apellidos']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label>Grupo:</label>
                                <select name="id_grupo">
                                    <option value="">Sin grupo asignado</option>
                                    <?php foreach($grupos as $grupo): ?>
                                        <option value="<?php echo $grupo['id_grupo']; ?>" <?php echo ($estudiante['id_grupo'] == $grupo['id_grupo']) ? 'selected' : ''; ?>>
                                            <?php echo $grupo['nombre'] . ' - ' . $grupo['nombre_grado']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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
                echo "<tr><td colspan='9'>No se encontraron estudiantes</td></tr>";
            }
            ?>
        </tbody>
    </table>
    
    <hr>
    <a href="gestionar_estudiantes.php">Agregar Nuevo Estudiante</a>
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