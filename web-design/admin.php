<?php
// admin.php
session_start();
require_once 'conexion.php';

// 1. Obtener estadísticas generales
try {
    // Total inscriptos
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM inscripciones");
    $total_inscriptos = $total_stmt->fetchColumn();

    // Con título secundario completo
    $titulo_stmt = $pdo->query("SELECT COUNT(*) FROM inscripciones WHERE titulo_secundario = 1");
    $con_titulo = $titulo_stmt->fetchColumn();

    // Porcentaje con título
    $porcentaje_titulo = $total_inscriptos > 0 ? round(($con_titulo / $total_inscriptos) * 100) : 0;

    // Desglose por carrera para mostrar en resumen rápido
    $carreras_stats_stmt = $pdo->query("SELECT carrera, COUNT(*) as cantidad FROM inscripciones GROUP BY carrera");
    $carreras_stats = $carreras_stats_stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al cargar estadísticas: " . $e->getMessage());
}

// 2. Procesar filtros y búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$carrera_filter = isset($_GET['carrera']) ? trim($_GET['carrera']) : '';

// Lista de carreras disponibles para el selector de filtro
$carreras_disponibles = [
    "Tecnicatura Superior en Programación",
    "Tecnicatura Superior en Desarrollo de Software",
    "Analista de Sistemas",
    "Licenciatura en Sistemas"
];

// Construir consulta dinámica
$sql = "SELECT * FROM inscripciones WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (nombre_completo LIKE :search OR dni LIKE :search OR correo LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($carrera_filter)) {
    $sql .= " AND carrera = :carrera";
    $params[':carrera'] = $carrera_filter;
}

$sql .= " ORDER BY fecha_inscripcion DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $inscripciones = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al consultar inscripciones: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración | Instituto Nuevo Cuyo</title>
    <link rel="stylesheet" href="admin.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        
        <!-- Header -->
        <header class="admin-header">
            <div class="admin-header-title">
                <h1>Panel de Administración</h1>
                <p>Gestión y Control de Pre-Inscripciones Académicas</p>
            </div>
            <div>
                <a href="admin_create.php" class="btn btn-primary">+ Nueva Inscripción</a>
                <a href="index.html" class="btn btn-secondary" style="margin-left: 0.5rem;">Ver Sitio Público</a>
            </div>
        </header>

        <!-- Alertas de Sesión -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert-box alert-box-success">
                <span>✅</span> <?php echo htmlspecialchars($_SESSION['mensaje']); unset($_SESSION['mensaje']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-box alert-box-error">
                <span>❌</span> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Tarjetas de Estadísticas -->
        <section class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-card-title">Total de Aspirantes</div>
                <div class="stat-card-value"><?php echo $total_inscriptos; ?></div>
                <div class="stat-card-desc">Pre-inscripciones completadas</div>
            </div>
            <div class="stat-card success">
                <div class="stat-card-title">Título Secundario</div>
                <div class="stat-card-value"><?php echo $con_titulo; ?></div>
                <div class="stat-card-desc"><?php echo $porcentaje_titulo; ?>% del total de aspirantes</div>
            </div>
            <div class="stat-card accent">
                <div class="stat-card-title">Carreras Activas</div>
                <div class="stat-card-value"><?php echo count($carreras_stats); ?></div>
                <div class="stat-card-desc">Con al menos 1 inscripto</div>
            </div>
        </section>

        <!-- Filtros y Búsqueda -->
        <section class="controls-card">
            <form action="admin.php" method="GET" class="controls-form">
                <div class="search-input-group">
                    <input type="text" name="search" placeholder="Buscar por Nombre, DNI o Correo..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-select-group">
                    <select name="carrera">
                        <option value="">Todas las Carreras</option>
                        <?php foreach ($carreras_disponibles as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $carrera_filter === $c ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Buscar / Filtrar</button>
                    <?php if (!empty($search) || !empty($carrera_filter)): ?>
                        <a href="admin.php" class="btn btn-secondary">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- Tabla de Resultados -->
        <section class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>DNI</th>
                            <th>Correo Electrónico</th>
                            <th>Fecha Nac.</th>
                            <th>Carrera</th>
                            <th>Título Sec.</th>
                            <th>Fecha de Alta</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($inscripciones) > 0): ?>
                            <?php foreach ($inscripciones as $reg): ?>
                                <tr>
                                    <td data-label="Nombre Completo"><strong><?php echo htmlspecialchars($reg['nombre_completo']); ?></strong></td>
                                    <td data-label="DNI"><?php echo htmlspecialchars($reg['dni']); ?></td>
                                    <td data-label="Correo Electrónico"><?php echo htmlspecialchars($reg['correo']); ?></td>
                                    <td data-label="Fecha Nac."><?php echo date('d/m/Y', strtotime($reg['fecha_nacimiento'])); ?></td>
                                    <td data-label="Carrera"><?php echo htmlspecialchars($reg['carrera']); ?></td>
                                    <td data-label="Título Sec.">
                                        <?php if ($reg['titulo_secundario']): ?>
                                            <span class="badge badge-success">Presentado</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Fecha de Alta"><?php echo date('d/m/Y H:i', strtotime($reg['fecha_inscripcion'])); ?></td>
                                    <td data-label="Acciones" style="text-align: center;">
                                        <div class="btn-group" style="justify-content: center;">
                                            <a href="admin_edit.php?id=<?php echo $reg['id']; ?>" class="btn btn-accent btn-sm">Editar</a>
                                            <a href="admin_delete.php?id=<?php echo $reg['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está completamente seguro de que desea eliminar la pre-inscripción de <?php echo htmlspecialchars(addslashes($reg['nombre_completo'])); ?>? Esta acción no se puede deshacer.');">Eliminar</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                    No se encontraron pre-inscripciones registradas que coincidan con la búsqueda.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</body>
</html>
