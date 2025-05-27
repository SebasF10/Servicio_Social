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
        // Eliminar grupo
        if (isset($_POST['eliminar'])) {
            $id_grupo = mysqli_real_escape_string($conexion, $_POST['id_grupo']);
            
            // Comprobar si tiene registros asociados en otras tablas (como grupo_estudiante)
            $verificar = "SELECT * FROM grupo_estudiante WHERE id_grupo = '$id_grupo'";
            $resultado_verificar = mysqli_query($conexion, $verificar);
            
            if (mysqli_num_rows($resultado_verificar) > 0) {
                $mensaje = "No se puede eliminar este grupo porque tiene estudiantes asociados";
            } else {
                $eliminar = "DELETE FROM grupo WHERE id_grupo = '$id_grupo'";
                
                if (mysqli_query($conexion, $eliminar)) {
                    $mensaje = "Grupo eliminado correctamente";
                } else {
                    $mensaje = "Error al eliminar grupo: " . mysqli_error($conexion);
                }
            }
        }
        
        // Actualizar grupo
        if (isset($_POST['actualizar'])) {
            $id_grupo_original = mysqli_real_escape_string($conexion, $_POST['id_grupo_original']);
            $id_grupo = mysqli_real_escape_string($conexion, $_POST['id_grupo']);
            $nombre_grupo = mysqli_real_escape_string($conexion, $_POST['nombre_grupo']);
            $id_grado = mysqli_real_escape_string($conexion, $_POST['id_grado']);
            
            // Verificar si el nuevo ID del grupo ya existe (pero no es el mismo grupo)
            $verificar = "SELECT * FROM grupo WHERE id_grupo = '$id_grupo' AND id_grupo != '$id_grupo_original'";
            $resultado_verificar = mysqli_query($conexion, $verificar);
            
            if (mysqli_num_rows($resultado_verificar) > 0) {
                $mensaje = "El ID del grupo ya está registrado con otro grupo";
            } else {
                $actualizar = "UPDATE grupo SET 
                              id_grupo = '$id_grupo', 
                              nombre = '$nombre_grupo',
                              id_grado = '$id_grado'
                              WHERE id_grupo = '$id_grupo_original'";
                
                if (mysqli_query($conexion, $actualizar)) {
                    $mensaje = "Grupo actualizado correctamente";
                } else {
                    $mensaje = "Error al actualizar grupo: " . mysqli_error($conexion);
                }
            }
        }
    }

    // Obtener datos de grupos para la tabla (con búsqueda si aplica)
    $query_grupos = "SELECT g.id_grupo, g.nombre as nombre_grupo, g.id_grado, gr.nombre as nombre_grado 
                     FROM grupo g 
                     LEFT JOIN grado gr ON g.id_grado = gr.id_grado";
    
    // Añadir condición de búsqueda si existe
    if (!empty($busqueda)) {
        $query_grupos .= " WHERE g.id_grupo LIKE '%$busqueda%' OR g.nombre LIKE '%$busqueda%' OR gr.nombre LIKE '%$busqueda%'";
    }
    
    $query_grupos .= " ORDER BY g.id_grupo, gr.nombre";
    $resultado_grupos = mysqli_query($conexion, $query_grupos);
    
    // Obtener lista de grados para el formulario de edición
    $query_grados = "SELECT id_grado, nombre FROM grado ORDER BY nombre";
    $resultado_grados = mysqli_query($conexion, $query_grados);
    
    // Crear un array para usar en los formularios de edición
    $grados = array();
    while ($grado = mysqli_fetch_assoc($resultado_grados)) {
        $grados[] = $grado;
    }
    
    // Debug: Comprobar cuántos grupos hay en la base de datos
    $query_total = "SELECT COUNT(*) as total FROM grupo";
    $resultado_total = mysqli_query($conexion, $query_total);
    $datos_total = mysqli_fetch_assoc($resultado_total);
    $total_grupos = $datos_total['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Grupos</title>
</head>
<body>
    <h1>Lista de Grupos</h1>
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
        <p>Total de grupos en la base de datos: <?php echo $total_grupos; ?></p>
    </div>

    <!-- Formulario de búsqueda -->
    <h2>Buscar Grupos</h2>
    <form method="GET" action="">
        <div>
            <label for="busqueda">Buscar por ID, Nombre o Grado:</label>
            <input type="text" id="busqueda" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
            <button type="submit" name="buscar">Buscar</button>
            <?php if (!empty($busqueda)): ?>
                <a href="ver_grupos.php">Limpiar búsqueda</a>
            <?php endif; ?>
        </div>
    </form>

    <h2>Lista de Grupos</h2>
    <table border="1">
        <thead>
            <tr>
                <th>ID Grupo</th>
                <th>Nombre del Grupo</th>
                <th>Grado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Verificar si hay resultados
            if (mysqli_num_rows($resultado_grupos) > 0) {
                while($grupo = mysqli_fetch_assoc($resultado_grupos)): 
            ?>
            <tr>
                <td><?php echo $grupo['id_grupo']; ?></td>
                <td><?php echo $grupo['nombre_grupo'] ? $grupo['nombre_grupo'] : 'Sin nombre'; ?></td>
                <td>
                    <?php 
                    if($grupo['id_grado']) {
                        echo $grupo['nombre_grado'];
                    } else {
                        echo "Sin grado asignado";
                    }
                    ?>
                </td>
                <td>
                    <form method="POST" action="">
                        <input type="hidden" name="id_grupo" value="<?php echo $grupo['id_grupo']; ?>">
                        <button type="submit" name="eliminar">Eliminar</button>
                    </form>
                    
                    <button onclick="mostrarFormularioEdicion('<?php echo $grupo['id_grupo']; ?>')">Editar</button>
                    
                    <div id="editar-<?php echo $grupo['id_grupo']; ?>" style="display: none;">
                        <form method="POST" action="">
                            <input type="hidden" name="id_grupo_original" value="<?php echo $grupo['id_grupo']; ?>">
                            <div>
                                <label>ID del Grupo:</label>
                                <input type="text" name="id_grupo" value="<?php echo $grupo['id_grupo']; ?>" required>
                            </div>
                            <div>
                                <label>Nombre del Grupo:</label>
                                <input type="text" name="nombre_grupo" value="<?php echo $grupo['nombre_grupo']; ?>" required>
                            </div>
                            <div>
                                <label>Grado:</label>
                                <select name="id_grado" required>
                                    <option value="">Seleccione un grado</option>
                                    <?php foreach($grados as $grado): ?>
                                        <option value="<?php echo $grado['id_grado']; ?>" <?php echo ($grupo['id_grado'] == $grado['id_grado']) ? 'selected' : ''; ?>>
                                            <?php echo $grado['nombre']; ?>
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
                echo "<tr><td colspan='4'>No se encontraron grupos</td></tr>";
            }
            ?>
        </tbody>
    </table>
    
    <hr>
    <a href="gestionar_grupos.php">Agregar Nuevo Grupo</a>
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