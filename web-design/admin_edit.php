<?php
// admin_edit.php
session_start();
require_once 'conexion.php';

$errores = [];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    $_SESSION['error'] = "ID de pre-inscripción no válido.";
    header("Location: admin.php");
    exit();
}

// 1. Obtener los datos actuales del registro
try {
    $stmt = $pdo->prepare("SELECT * FROM inscripciones WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $registro = $stmt->fetch();
    
    if (!$registro) {
        $_SESSION['error'] = "No se encontró la pre-inscripción solicitada.";
        header("Location: admin.php");
        exit();
    }
} catch (PDOException $e) {
    die("Error al consultar el registro: " . $e->getMessage());
}

// Inicializar variables del formulario con los valores actuales de la BD
$nombre = $registro['nombre_completo'];
$dni = $registro['dni'];
$correo = $registro['correo'];
$fecha_nacimiento = $registro['fecha_nacimiento'];
$carrera = $registro['carrera'];
$titulo = $registro['titulo_secundario'] ? true : false;

// 2. Procesar el formulario de edición
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Saneamiento de datos
    $nombre = trim(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING) ?? '');
    $dni = trim(filter_input(INPUT_POST, 'dni', FILTER_SANITIZE_STRING) ?? '');
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $correo = trim(filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL) ?? '');
    $carrera = trim(filter_input(INPUT_POST, 'carrera', FILTER_SANITIZE_STRING) ?? '');
    $titulo = isset($_POST['titulo_secundario']) ? true : false;
    
    // Validaciones
    if (empty($nombre) || empty($dni) || empty($fecha_nacimiento) || empty($correo) || empty($carrera)) {
        $errores[] = "Todos los campos marcados con * son obligatorios.";
    }
    
    if (!empty($dni) && !preg_match("/^[0-9]+$/", $dni)) {
        $errores[] = "El DNI debe contener únicamente números.";
    }
    
    if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del correo electrónico no es válido.";
    }
    
    if (!empty($fecha_nacimiento)) {
        $año_nacimiento = date('Y', strtotime($fecha_nacimiento));
        $año_actual = date('Y');
        $edad_en_año_lectivo = $año_actual - $año_nacimiento;
        
        if ($edad_en_año_lectivo < 18) {
            $errores[] = "Debes ser mayor de 18 años (o cumplirlos en el año lectivo) para inscribirte.";
        }
    }
    
    // Validar duplicados en la base de datos (solo si las validaciones previas son correctas, excluyendo el ID actual)
    if (empty($errores)) {
        // Verificar DNI duplicado
        $stmt_dni = $pdo->prepare("SELECT COUNT(*) FROM inscripciones WHERE dni = :dni AND id != :id");
        $stmt_dni->execute([':dni' => $dni, ':id' => $id]);
        if ($stmt_dni->fetchColumn() > 0) {
            $errores[] = "Ya existe otra pre-inscripción registrada con el DNI proporcionado.";
        }
        
        // Verificar Correo duplicado
        $stmt_correo = $pdo->prepare("SELECT COUNT(*) FROM inscripciones WHERE correo = :correo AND id != :id");
        $stmt_correo->execute([':correo' => $correo, ':id' => $id]);
        if ($stmt_correo->fetchColumn() > 0) {
            $errores[] = "Ya existe otra pre-inscripción registrada con el correo electrónico proporcionado.";
        }
    }

    // Guardar cambios si no hay errores
    if (empty($errores)) {
        try {
            $sql = "UPDATE inscripciones 
                    SET nombre_completo = :nombre, 
                        dni = :dni, 
                        correo = :correo, 
                        fecha_nacimiento = :fecha_nacimiento, 
                        carrera = :carrera, 
                        titulo_secundario = :titulo 
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':dni', $dni);
            $stmt->bindParam(':correo', $correo);
            $stmt->bindParam(':fecha_nacimiento', $fecha_nacimiento);
            $stmt->bindParam(':carrera', $carrera);
            $stmt->bindValue(':titulo', $titulo, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $_SESSION['mensaje'] = "¡Pre-Inscripción de $nombre actualizada con éxito!";
            header("Location: admin.php");
            exit();
        } catch (PDOException $e) {
            $errores[] = "Error de base de datos al guardar los cambios: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pre-Inscripción | Panel de Administración</title>
    <link rel="stylesheet" href="admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container" style="margin-top: 2rem; margin-bottom: 2rem;">
        
        <div class="admin-card-form">
            <h2>Editar Pre-Inscripción</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem; font-size: 0.9rem;">
                Modifique los campos correspondientes para actualizar la ficha de pre-inscripción del aspirante.
            </p>

            <!-- Errores -->
            <?php if (!empty($errores)): ?>
                <div class="alert-box alert-box-error" style="display: block;">
                    <strong>❌ Por favor corrija los siguientes errores:</strong>
                    <ul style="margin-top: 0.5rem; margin-left: 1.25rem;">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="admin_edit.php?id=<?php echo $id; ?>" method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre Completo *</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required placeholder="Ej. Juan Pérez">
                </div>

                <div class="form-group">
                    <label for="dni">DNI (Sin puntos) *</label>
                    <input type="text" id="dni" name="dni" value="<?php echo htmlspecialchars($dni); ?>" required pattern="[0-9]+" placeholder="Ej. 12345678">
                </div>

                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de Nacimiento *</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo htmlspecialchars($fecha_nacimiento); ?>" required>
                </div>

                <div class="form-group">
                    <label for="correo">Correo Electrónico *</label>
                    <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($correo); ?>" required placeholder="ejemplo@correo.com">
                </div>

                <div class="form-group">
                    <label for="carrera">Carrera a la que se inscribe *</label>
                    <select id="carrera" name="carrera" required>
                        <option value="Tecnicatura Superior en Programación" <?php echo $carrera === "Tecnicatura Superior en Programación" ? 'selected' : ''; ?>>Tecnicatura Superior en Programación</option>
                        <option value="Tecnicatura Superior en Desarrollo de Software" <?php echo $carrera === "Tecnicatura Superior en Desarrollo de Software" ? 'selected' : ''; ?>>Tecnicatura Superior en Desarrollo de Software</option>
                        <option value="Analista de Sistemas" <?php echo $carrera === "Analista de Sistemas" ? 'selected' : ''; ?>>Analista de Sistemas</option>
                        <option value="Licenciatura en Sistemas" <?php echo $carrera === "Licenciatura en Sistemas" ? 'selected' : ''; ?>>Licenciatura en Sistemas</option>
                    </select>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="titulo_secundario" name="titulo_secundario" <?php echo $titulo ? 'checked' : ''; ?>>
                    <label for="titulo_secundario">Presentó título secundario completo o certificado en trámite.</label>
                </div>

                <div class="btn-group" style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-accent">Guardar Cambios</button>
                    <a href="admin.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>

    </div>
</body>
</html>
