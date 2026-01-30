<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dsn  = 'mysql:host=localhost;dbname=clinica_odontologica;charset=utf8mb4';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Error de conexión: ' . htmlspecialchars($e->getMessage()));
}

// Filtros
$filtro_estado = $_GET['estado'] ?? 'todos';
$busqueda = trim($_GET['busqueda'] ?? '');
$id_paciente_filtro = isset($_GET['id_paciente']) ? (int)$_GET['id_paciente'] : 0;

// Query para obtener estado de cuenta de todos los clientes
$sql = "
    SELECT 
        p.id_paciente,
        p.ci,
        CONCAT(p.nombres, ' ', p.apellido_paterno, ' ', IFNULL(p.apellido_materno, '')) AS paciente,
        p.telefono,
        COUNT(DISTINCT a.id_atencion) AS total_atenciones,
        COALESCE(SUM(DISTINCT t.total_servicios), 0) AS total_servicios,
        COALESCE((
            SELECT SUM(pg.monto) 
            FROM pagos pg 
            JOIN atenciones a2 ON pg.id_atencion = a2.id_atencion
            JOIN citas c2 ON a2.id_cita = c2.id_cita
            WHERE c2.id_paciente = p.id_paciente
        ), 0) AS total_pagado,
        (
            SELECT MAX(pg.fecha_pago) 
            FROM pagos pg 
            JOIN atenciones a2 ON pg.id_atencion = a2.id_atencion
            JOIN citas c2 ON a2.id_cita = c2.id_cita
            WHERE c2.id_paciente = p.id_paciente
        ) AS ultimo_pago,
        (
            SELECT COUNT(*) 
            FROM pagos pg 
            JOIN atenciones a2 ON pg.id_atencion = a2.id_atencion
            JOIN citas c2 ON a2.id_cita = c2.id_cita
            WHERE c2.id_paciente = p.id_paciente AND pg.estado = 'PENDIENTE'
        ) AS pagos_pendientes
    FROM pacientes p
    LEFT JOIN citas c ON c.id_paciente = p.id_paciente
    LEFT JOIN atenciones a ON a.id_cita = c.id_cita
    LEFT JOIN vw_atencion_total t ON t.id_atencion = a.id_atencion
";

$where = [];
$params = [];

if ($id_paciente_filtro > 0) {
    $where[] = "p.id_paciente = ?";
    $params[] = $id_paciente_filtro;
}
if ($busqueda) {
    $where[] = "(p.nombres LIKE ? OR p.apellido_paterno LIKE ? OR p.ci LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " GROUP BY p.id_paciente, p.ci, p.nombres, p.apellido_paterno, p.apellido_materno, p.telefono";

// Filtro por estado después del GROUP BY
if ($filtro_estado === 'pendiente') {
    $sql .= " HAVING (COALESCE(SUM(DISTINCT t.total_servicios), 0) - COALESCE((
            SELECT SUM(pg.monto) 
            FROM pagos pg 
            JOIN atenciones a2 ON pg.id_atencion = a2.id_atencion
            JOIN citas c2 ON a2.id_cita = c2.id_cita
            WHERE c2.id_paciente = p.id_paciente
        ), 0)) > 0";
} elseif ($filtro_estado === 'al_dia') {
    $sql .= " HAVING (COALESCE(SUM(DISTINCT t.total_servicios), 0) - COALESCE((
            SELECT SUM(pg.monto) 
            FROM pagos pg 
            JOIN atenciones a2 ON pg.id_atencion = a2.id_atencion
            JOIN citas c2 ON a2.id_cita = c2.id_cita
            WHERE c2.id_paciente = p.id_paciente
        ), 0)) <= 0";
}

$sql .= " ORDER BY p.nombres, p.apellido_paterno";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();

// Calcular totales
$total_general_servicios = 0;
$total_general_pagado = 0;
$total_general_pendiente = 0;
$clientes_con_deuda = 0;

foreach ($clientes as &$c) {
    $c['saldo'] = $c['total_servicios'] - $c['total_pagado'];
    $total_general_servicios += $c['total_servicios'];
    $total_general_pagado += $c['total_pagado'];
    if ($c['saldo'] > 0) {
        $total_general_pendiente += $c['saldo'];
        $clientes_con_deuda++;
    }
}
unset($c);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de Cuenta - Clínica Odontológica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .card-estado { border: none; border-radius: 15px; box-shadow: 0 2px 15px rgba(0,0,0,0.08); }
        .stats-card { border-radius: 15px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.08); transition: transform 0.2s; }
        .stats-card:hover { transform: translateY(-3px); }
        .stats-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .nav-pagos .nav-link { color: #6c757d; border-radius: 10px; margin-right: 5px; padding: 10px 20px; }
        .nav-pagos .nav-link.active { background-color: #0d6efd; color: white; }
        .nav-pagos .nav-link:hover:not(.active) { background-color: #e9ecef; }
        .filter-btn { border-radius: 20px; padding: 8px 20px; }
        .filter-btn.active { background-color: #0d6efd; color: white; border-color: #0d6efd; }
        .table-estado th { background-color: #f8f9fa; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; }
        .saldo-negativo { color: #dc3545; font-weight: bold; }
        .saldo-positivo { color: #198754; font-weight: bold; }
        .badge-estado { font-size: 0.75rem; padding: 5px 12px; border-radius: 20px; }
        .btn-detalle { padding: 5px 10px; font-size: 0.8rem; }
        @media print {
            .no-print { display: none !important; }
            .dashboard-main { margin-left: 0 !important; }
        }
    </style>
</head>
<body>
<?php $sidebar_base = '../'; $sidebar_carpeta = 'pagos'; require_once __DIR__ . '/../panel/sidebar.php'; ?>

<div class="dashboard-main">
<div class="container-fluid py-4 px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h2 class="mb-1"><i class="bi bi-person-lines-fill me-2"></i>Estado de Cuenta por Cliente</h2>
            <p class="text-muted mb-0">Consulta de saldos y pagos pendientes</p>
        </div>
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
    </div>

    <!-- Navegación -->
    <ul class="nav nav-pagos mb-4 no-print">
        <li class="nav-item">
            <a class="nav-link" href="index.php"><i class="bi bi-plus-circle me-1"></i>Registrar Pago</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="ultimos_pagos.php"><i class="bi bi-clock-history me-1"></i>Historial de Pagos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="estado_cuenta.php"><i class="bi bi-person-lines-fill me-1"></i>Estado de Cuenta</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="reportes.php"><i class="bi bi-graph-up me-1"></i>Reportes</a>
        </li>
    </ul>

    <!-- Resumen General -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 small">Total Clientes</h6>
                        <h4 class="mb-0"><?php echo count($clientes); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-info bg-opacity-10 text-info me-3">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 small">Total Servicios</h6>
                        <h4 class="mb-0">Bs. <?php echo number_format($total_general_servicios, 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 small">Total Cobrado</h6>
                        <h4 class="mb-0">Bs. <?php echo number_format($total_general_pagado, 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-danger bg-opacity-10 text-danger me-3">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 small">Por Cobrar</h6>
                        <h4 class="mb-0">Bs. <?php echo number_format($total_general_pendiente, 2); ?></h4>
                        <small class="text-muted"><?php echo $clientes_con_deuda; ?> clientes</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card card-estado mb-4 no-print">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Buscar Cliente</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="busqueda" class="form-control" 
                               placeholder="Nombre o CI..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Filtrar por Estado</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="estado" id="todos" value="todos" 
                               <?php echo $filtro_estado === 'todos' ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-secondary filter-btn" for="todos">Todos</label>

                        <input type="radio" class="btn-check" name="estado" id="pendiente" value="pendiente"
                               <?php echo $filtro_estado === 'pendiente' ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-danger filter-btn" for="pendiente">
                            <i class="bi bi-exclamation-circle me-1"></i>Con Deuda
                        </label>

                        <input type="radio" class="btn-check" name="estado" id="al_dia" value="al_dia"
                               <?php echo $filtro_estado === 'al_dia' ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-success filter-btn" for="al_dia">
                            <i class="bi bi-check-circle me-1"></i>Al Día
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i>Aplicar Filtros
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Estado de Cuenta -->
    <div class="card card-estado">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i>Listado de Clientes</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tablaEstado" class="table table-hover table-estado mb-0">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>CI</th>
                            <th>Teléfono</th>
                            <th class="text-center">Atenciones</th>
                            <th class="text-end">Total Servicios</th>
                            <th class="text-end">Total Pagado</th>
                            <th class="text-end">Saldo</th>
                            <th>Estado</th>
                            <th>Último Pago</th>
                            <th class="text-center no-print">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clientes)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                    No se encontraron clientes
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clientes as $c): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($c['paciente']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($c['ci']); ?></td>
                                    <td><?php echo htmlspecialchars($c['telefono'] ?? '-'); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?php echo $c['total_atenciones']; ?></span>
                                    </td>
                                    <td class="text-end">Bs. <?php echo number_format($c['total_servicios'], 2); ?></td>
                                    <td class="text-end text-success">Bs. <?php echo number_format($c['total_pagado'], 2); ?></td>
                                    <td class="text-end <?php echo $c['saldo'] > 0 ? 'saldo-negativo' : 'saldo-positivo'; ?>">
                                        Bs. <?php echo number_format($c['saldo'], 2); ?>
                                    </td>
                                    <td>
                                        <?php if ($c['saldo'] > 0): ?>
                                            <span class="badge badge-estado bg-danger">Pendiente</span>
                                        <?php elseif ($c['total_atenciones'] > 0): ?>
                                            <span class="badge badge-estado bg-success">Al día</span>
                                        <?php else: ?>
                                            <span class="badge badge-estado bg-secondary">Sin atenciones</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $c['ultimo_pago'] ? date('d/m/Y', strtotime($c['ultimo_pago'])) : '-'; ?>
                                    </td>
                                    <td class="text-center no-print">
                                        <?php if ($c['saldo'] > 0): ?>
                                        <a href="index.php?id_paciente=<?php echo $c['id_paciente']; ?>" 
                                           class="btn btn-sm btn-outline-success btn-detalle" title="Registrar Pago">
                                            <i class="bi bi-cash"></i>
                                        </a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-detalle" 
                                                onclick="imprimirEstadoCuenta(<?php echo $c['id_paciente']; ?>, '<?php echo htmlspecialchars($c['paciente'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($c['ci'], ENT_QUOTES); ?>', <?php echo $c['total_servicios']; ?>, <?php echo $c['total_pagado']; ?>, <?php echo $c['saldo']; ?>)"
                                                title="Imprimir Estado">
                                            <i class="bi bi-printer"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($clientes)): ?>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="4" class="text-end">TOTALES:</td>
                            <td class="text-end">Bs. <?php echo number_format($total_general_servicios, 2); ?></td>
                            <td class="text-end text-success">Bs. <?php echo number_format($total_general_pagado, 2); ?></td>
                            <td class="text-end text-danger">Bs. <?php echo number_format($total_general_pendiente, 2); ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#tablaEstado').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        pageLength: 25,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: -1 }
        ]
    });
});

function imprimirEstadoCuenta(id, nombre, ci, servicios, pagado, saldo) {
    const fecha = new Date().toLocaleDateString('es-BO');
    const html = `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Cuenta - ${nombre}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; }
        .header { border-bottom: 3px solid #0d6efd; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #0d6efd; margin: 0; }
        .info-box { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #dee2e6; }
        .info-row:last-child { border-bottom: none; }
        .total { font-size: 1.5rem; color: ${saldo > 0 ? '#dc3545' : '#198754'}; font-weight: bold; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>🦷 Clínica Dr. Muelitas</h1>
        <p>Estado de Cuenta del Cliente</p>
    </div>
    
    <div class="info-box">
        <h3>Datos del Cliente</h3>
        <div class="info-row">
            <span><strong>Nombre:</strong></span>
            <span>${nombre}</span>
        </div>
        <div class="info-row">
            <span><strong>CI:</strong></span>
            <span>${ci}</span>
        </div>
        <div class="info-row">
            <span><strong>Fecha de emisión:</strong></span>
            <span>${fecha}</span>
        </div>
    </div>
    
    <div class="info-box">
        <h3>Resumen de Cuenta</h3>
        <div class="info-row">
            <span><strong>Total Servicios:</strong></span>
            <span>Bs. ${servicios.toFixed(2)}</span>
        </div>
        <div class="info-row">
            <span><strong>Total Pagado:</strong></span>
            <span style="color: #198754;">Bs. ${pagado.toFixed(2)}</span>
        </div>
        <div class="info-row">
            <span><strong>Saldo Pendiente:</strong></span>
            <span class="total">Bs. ${saldo.toFixed(2)}</span>
        </div>
    </div>
    
    <p style="text-align: center; color: #666; margin-top: 40px;">
        Este documento es un resumen del estado de cuenta del cliente.<br>
        Para más detalles, consulte con recepción.
    </p>
    
    <script>window.onload = function() { window.print(); }<\/script>
</body>
</html>`;

    const win = window.open('', '_blank');
    if (win) {
        win.document.write(html);
        win.document.close();
    }
}
</script>
</body>
</html>
