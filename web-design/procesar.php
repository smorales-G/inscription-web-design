<?php
// procesar.php

require_once 'conexion.php';

// Inicializar un arreglo para errores
$errores = [];
$exito = false;
$datos_ficha = [];

// Verificar si el formulario fue enviado por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Recepción y Saneamiento (Limpieza) de datos
    // filter_var y trim para evitar espacios en blanco adicionales y limpiar datos
    
    $nombre = trim(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING) ?? '');
    $dni = trim(filter_input(INPUT_POST, 'dni', FILTER_SANITIZE_STRING) ?? '');
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $correo = trim(filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL) ?? '');
    $carrera = trim(filter_input(INPUT_POST, 'carrera', FILTER_SANITIZE_STRING) ?? '');
    $titulo = isset($_POST['titulo_secundario']) ? true : false;
    
    // 2. Validaciones (Lógica de Negocio)
    
    // Validar campos vacíos
    if (empty($nombre) || empty($dni) || empty($fecha_nacimiento) || empty($correo) || empty($carrera)) {
        $errores[] = "Todos los campos marcados con * son obligatorios.";
    }
    
    // Validar DNI (solo numérico)
    if (!empty($dni) && !preg_match("/^[0-9]+$/", $dni)) {
        $errores[] = "El DNI debe contener únicamente números (sin puntos ni letras).";
    }
    
    // Validar Formato de Correo Electrónico
    if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del correo electrónico no es válido.";
    }
    
    // Validar Edad (Mayor de 18 años o que cumpla 18 este año)
    if (!empty($fecha_nacimiento)) {
        $año_nacimiento = date('Y', strtotime($fecha_nacimiento));
        $año_actual = date('Y');
        $edad_en_año_lectivo = $año_actual - $año_nacimiento;
        
        if ($edad_en_año_lectivo < 18) {
            $errores[] = "Debes ser mayor de 18 años (o cumplirlos en el año lectivo) para poder inscribirte.";
        }
    }
    
    // Validar Checklist de Título
    if (!$titulo) {
        $errores[] = "Debes confirmar que posees el título secundario o certificado en trámite.";
    }

    // Validar duplicados en la base de datos (solo si las validaciones previas son correctas)
    if (empty($errores)) {
        // Verificar DNI duplicado
        $stmt_dni = $pdo->prepare("SELECT COUNT(*) FROM inscripciones WHERE dni = :dni");
        $stmt_dni->execute([':dni' => $dni]);
        if ($stmt_dni->fetchColumn() > 0) {
            $errores[] = "Ya existe una pre-inscripción registrada con el DNI proporcionado.";
        }
        
        // Verificar Correo duplicado
        $stmt_correo = $pdo->prepare("SELECT COUNT(*) FROM inscripciones WHERE correo = :correo");
        $stmt_correo->execute([':correo' => $correo]);
        if ($stmt_correo->fetchColumn() > 0) {
            $errores[] = "Ya existe una pre-inscripción registrada con el correo electrónico proporcionado.";
        }
    }
    
    // 3. Procesamiento y Base de Datos (Si no hay errores)
    if (empty($errores)) {
        try {
            // Preparar la consulta SQL
            $sql = "INSERT INTO inscripciones (nombre_completo, dni, correo, fecha_nacimiento, carrera, titulo_secundario) 
                    VALUES (:nombre, :dni, :correo, :fecha_nacimiento, :carrera, :titulo)";
            
            $stmt = $pdo->prepare($sql);
            
            // Vincular parámetros
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':dni', $dni);
            $stmt->bindParam(':correo', $correo);
            $stmt->bindParam(':fecha_nacimiento', $fecha_nacimiento);
            $stmt->bindParam(':carrera', $carrera);
            $stmt->bindValue(':titulo', $titulo, PDO::PARAM_INT);
            
            // Ejecutar la inserción
            $stmt->execute();
            
            // Marcar éxito y preparar datos para la ficha
            $exito = true;
            $datos_ficha = [
                'nombre' => $nombre,
                'dni' => $dni,
                'correo' => $correo,
                'carrera' => $carrera,
                'fecha' => date('d/m/Y H:i')
            ];
            
        } catch (PDOException $e) {
            $errores[] = "Error al guardar en la base de datos. Asegúrese de que la base de datos 'nuevo_cuyo' ha sido creada importando el archivo database.sql";
        }
    }
} else {
    // Si acceden directamente a procesar.php sin enviar el formulario, redirigir al index
    header("Location: index.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado de Pre-Inscripción | Instituto Nuevo Cuyo</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <main class="container">
        <div class="form-wrapper">
            <header class="form-header">
                <div class="logo">INC</div>
                <h1>Instituto Nuevo Cuyo</h1>
            </header>

            <?php if ($exito): ?>
                <!-- Ficha de Inscripción (Éxito) -->
                <div class="ficha-inscripcion">
                    <div class="alert alert-success">
                        ✅ ¡Pre-Inscripción registrada con éxito!
                    </div>
                    
                    <h3>Ficha de Inscripción</h3>
                    <p>Hemos guardado los siguientes datos. Por favor, conserve esta información.</p>
                    
                    <div class="ficha-details">
                        <ul>
                            <li><strong>Nombre:</strong> <?php echo htmlspecialchars($datos_ficha['nombre']); ?></li>
                            <li><strong>DNI:</strong> <?php echo htmlspecialchars($datos_ficha['dni']); ?></li>
                            <li><strong>Correo:</strong> <?php echo htmlspecialchars($datos_ficha['correo']); ?></li>
                            <li><strong>Carrera:</strong> <?php echo htmlspecialchars($datos_ficha['carrera']); ?></li>
                            <li><strong>Fecha de alta:</strong> <?php echo $datos_ficha['fecha']; ?></li>
                            <li><strong>Estado de Título:</strong> Confirmado (Verificación presencial requerida)</li>
                        </ul>
                    </div>
                    
                    <a href="index.html" class="btn-back">Volver al Inicio</a>
                </div>
                
            <?php else: ?>
                <!-- Mostrar Errores -->
                <div class="errores-contenedor">
                    <div class="alert alert-error">
                        ❌ Hubo problemas con su solicitud:
                    </div>
                    <ul style="color: var(--error-color); margin-left: 1.5rem; margin-bottom: 1.5rem; line-height: 1.6;">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="javascript:history.back()" class="btn-back">Volver al Formulario</a>
                </div>
            <?php endif; ?>

        </div>
    </main>
</body>
</html>
