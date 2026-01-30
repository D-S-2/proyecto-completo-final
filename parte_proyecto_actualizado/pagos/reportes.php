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

// Filtros de fecha
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Hoy
$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_metodo = $_GET['metodo'] ?? 'todos';

// Query principal de pagos por rango de fecha
$sql = "
    SELECT 
        p.id_pago,
        DATE_FORMAT(p.fecha_pago, '%d/%m/%Y %H:%i') AS fecha_pago,
        DATE(p.fecha_pago) AS fecha_solo,
        p.monto,
        p.metodo,
        p.estado,
        r.numero_recibo,
        pac.id_paciente,
        pac.ci,
        CONCAT(pac.nombres, ' ', pac.apellido_paterno, ' ', IFNULL(pac.apellido_materno, '')) AS paciente,
        c.motivo AS servicio,
        a.diagnostico
    FROM pagos p
    JOIN recibos r ON r.id_pago = p.id_pago
    JOIN atenciones a ON a.id_atencion = p.id_atencion
    JOIN citas c ON c.id_cita = a.id_cita
    JOIN pacientes pac ON pac.id_paciente = c.id_paciente
    WHERE DATE(p.fecha_pago) BETWEEN ? AND ?
";

$params = [$fecha_inicio, $fecha_fin];

if ($filtro_estado !== 'todos') {
    $sql .= " AND p.estado = ?";
    $params[] = $filtro_estado;
}

if ($filtro_metodo !== 'todos') {
    $sql .= " AND p.metodo = ?";
    $params[] = $filtro_metodo;
}

$sql .= " ORDER BY p.fecha_pago DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pagos = $stmt->fetchAll();

// Estadísticas del período
$sqlStats = "
    SELECT 
        COUNT(*) as total_pagos,
        COALESCE(SUM(monto), 0) as total_monto,
        COALESCE(SUM(CASE WHEN estado = 'PAGADO' THEN monto ELSE 0 END), 0) as total_pagado,
        COALESCE(SUM(CASE WHEN estado = 'PENDIENTE' THEN monto ELSE 0 END), 0) as total_pendiente,
        COUNT(CASE WHEN metodo = 'EFECTIVO' THEN 1 END) as pagos_efectivo,
        COALESCE(SUM(CASE WHEN metodo = 'EFECTIVO' THEN monto ELSE 0 END), 0) as monto_efectivo,
        COUNT(CASE WHEN metodo = 'TARJETA' THEN 1 END) as pagos_tarjeta,
        COALESCE(SUM(CASE WHEN metodo = 'TARJETA' THEN monto ELSE 0 END), 0) as monto_tarjeta,
        COUNT(CASE WHEN metodo = 'TRANSFERENCIA' THEN 1 END) as pagos_transferencia,
        COALESCE(SUM(CASE WHEN metodo = 'TRANSFERENCIA' THEN monto ELSE 0 END), 0) as monto_transferencia,
        COUNT(CASE WHEN metodo = 'CREDITO' THEN 1 END) as pagos_credito,
        COALESCE(SUM(CASE WHEN metodo = 'CREDITO' THEN monto ELSE 0 END), 0) as monto_credito
    FROM pagos
    WHERE DATE(fecha_pago) BETWEEN ? AND ?
";
$stmtStats = $pdo->prepare($sqlStats);
$stmtStats->execute([$fecha_inicio, $fecha_fin]);
$stats = $stmtStats->fetch();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Pagos - Clínica Odontológica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f0f2f5; }
        .card-reporte { border: none; border-radius: 15px; box-shadow: 0 2px 15px rgba(0,0,0,0.08); }
        .stats-card { border-radius: 15px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .stats-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .nav-pagos .nav-link { color: #6c757d; border-radius: 10px; margin-right: 5px; padding: 10px 20px; }
        .nav-pagos .nav-link.active { background-color: #0d6efd; color: white; }
        .nav-pagos .nav-link:hover:not(.active) { background-color: #e9ecef; }
        .filter-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px; }
        .metodo-badge { font-size: 0.75rem; padding: 5px 12px; border-radius: 20px; }
        .chart-container { position: relative; height: 300px; }
        .table-reporte th { background-color: #f8f9fa; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; }
        @media print {
            .no-print { display: none !important; }
            .dashboard-main { margin-left: 0 !important; }
            .card-reporte { box-shadow: none; border: 1px solid #dee2e6; }
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
            <h2 class="mb-1"><i class="bi bi-graph-up me-2"></i>Reportes de Pagos</h2>
            <p class="text-muted mb-0">Análisis y reportes por rango de fecha</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Imprimir
            </button>
        </div>
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
            <a class="nav-link" href="estado_cuenta.php"><i class="bi bi-person-lines-fill me-1"></i>Estado de Cuenta</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="reportes.php"><i class="bi bi-graph-up me-1"></i>Reportes</a>
        </li>
    </ul>

    <!-- Filtros -->
    <div class="card filter-card mb-4 no-print">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Fecha Fin</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : ''; ?>>Todos</option>
                        <option value="PAGADO" <?php echo $filtro_estado === 'PAGADO' ? 'selected' : ''; ?>>Pagado</option>
                        <option value="PENDIENTE" <?php echo $filtro_estado === 'PENDIENTE' ? 'selected' : ''; ?>>Pendiente</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Método</label>
                    <select name="metodo" class="form-select">
                        <option value="todos" <?php echo $filtro_metodo === 'todos' ? 'selected' : ''; ?>>Todos</option>
                        <option value="EFECTIVO" <?php echo $filtro_metodo === 'EFECTIVO' ? 'selected' : ''; ?>>Efectivo</option>
                        <option value="TARJETA" <?php echo $filtro_metodo === 'TARJETA' ? 'selected' : ''; ?>>Tarjeta</option>
                        <option value="TRANSFERENCIA" <?php echo $filtro_metodo === 'TRANSFERENCIA' ? 'selected' : ''; ?>>Transferencia</option>
                        <option value="CREDITO" <?php echo $filtro_metodo === 'CREDITO' ? 'selected' : ''; ?>>Crédito</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-light w-100">
                        <i class="bi bi-filter me-1"></i>Filtrar
                    </button>
                </div>
                <div class="col-md-2">
                    <div class="btn-group w-100">
                        <a href="?fecha_inicio=<?php echo date('Y-m-d'); ?>&fecha_fin=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-light btn-sm">Hoy</a>
                        <a href="?fecha_inicio=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&fecha_fin=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-light btn-sm">7 días</a>
                        <a href="?fecha_inicio=<?php echo date('Y-m-01'); ?>&fecha_fin=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-light btn-sm">Mes</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Período seleccionado -->
    <div class="alert alert-info mb-4">
        <i class="bi bi-calendar-range me-2"></i>
        <strong>Período:</strong> 
        <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> - <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
        <span class="ms-3"><strong>Total registros:</strong> <?php echo count($pagos); ?></span>
    </div>

    <!-- Estadísticas del período -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 small">Total Pagos</h6>
                        <h4 class="mb-0"><?php echo (int)$stats['total_pagos']; ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 small">Total Recaudado</h6>
                        <h4 class="mb-0">Bs. <?php echo number_format((float)$stats['total_monto'], 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-info bg-opacity-10 text-info me-3">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 small">Pagos Completos</h6>
                        <h4 class="mb-0">Bs. <?php echo number_format((float)$stats['total_pagado'], 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 small">Pagos Parciales</h6>
                        <h4 class="mb-0">Bs. <?php echo number_format((float)$stats['total_pendiente'], 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen por método de pago -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card card-reporte h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Por Método de Pago</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="chartMetodos"></canvas>
                    </div>
                    <hr>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1">
                            <span><i class="bi bi-cash text-success me-1"></i>Efectivo</span>
                            <span>Bs. <?php echo number_format((float)$stats['monto_efectivo'], 2); ?> (<?php echo $stats['pagos_efectivo']; ?>)</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span><i class="bi bi-credit-card text-primary me-1"></i>Tarjeta</span>
                            <span>Bs. <?php echo number_format((float)$stats['monto_tarjeta'], 2); ?> (<?php echo $stats['pagos_tarjeta']; ?>)</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span><i class="bi bi-bank text-info me-1"></i>Transferencia</span>
                            <span>Bs. <?php echo number_format((float)$stats['monto_transferencia'], 2); ?> (<?php echo $stats['pagos_transferencia']; ?>)</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span><i class="bi bi-credit-card-2-front text-warning me-1"></i>Crédito</span>
                            <span>Bs. <?php echo number_format((float)$stats['monto_credito'], 2); ?> (<?php echo $stats['pagos_credito']; ?>)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalle de Pagos -->
    <div class="card card-reporte">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Detalle de Pagos</h5>
            <span class="badge bg-primary"><?php echo count($pagos); ?> registros</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tablaPagos" class="table table-hover table-reporte mb-0">
                    <thead>
                        <tr>
                            <th>Recibo</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>CI</th>
                            <th>Servicio</th>
                            <th class="text-end">Monto</th>
                            <th>Método</th>
                            <th>Estado</th>
                            <th class="text-center no-print">Imprimir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pagos)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                    No hay pagos en el período seleccionado
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pagos as $p): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($p['numero_recibo']); ?></code></td>
                                    <td><?php echo htmlspecialchars($p['fecha_pago']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($p['paciente']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($p['ci']); ?></td>
                                    <td class="small"><?php echo htmlspecialchars(substr($p['servicio'] ?? '-', 0, 30)); ?></td>
                                    <td class="text-end fw-bold">Bs. <?php echo number_format((float)$p['monto'], 2); ?></td>
                                    <td>
                                        <?php 
                                        $colores = [
                                            'EFECTIVO' => 'success',
                                            'TARJETA' => 'primary',
                                            'TRANSFERENCIA' => 'info',
                                            'CREDITO' => 'warning'
                                        ];
                                        $color = $colores[$p['metodo']] ?? 'secondary';
                                        ?>
                                        <span class="badge metodo-badge bg-<?php echo $color; ?>">
                                            <?php echo $p['metodo']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $p['estado'] === 'PAGADO' ? 'success' : 'warning'; ?>">
                                            <?php echo $p['estado']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center no-print">
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            onclick="imprimirRecibo('<?php echo htmlspecialchars($p['numero_recibo'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['fecha_pago'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['paciente'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['ci'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['servicio'] ?? 'Servicio', ENT_QUOTES); ?>', '<?php echo $p['metodo']; ?>', '<?php echo number_format((float)$p['monto'], 2); ?>', '<?php echo $p['estado']; ?>')">
                                            <i class="bi bi-printer"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($pagos)): ?>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="5" class="text-end">TOTAL DEL PERÍODO:</td>
                            <td class="text-end">Bs. <?php echo number_format((float)$stats['total_monto'], 2); ?></td>
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
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
// Gráfico de métodos de pago
const ctxMetodos = document.getElementById('chartMetodos').getContext('2d');
new Chart(ctxMetodos, {
    type: 'doughnut',
    data: {
        labels: ['Efectivo', 'Tarjeta', 'Transferencia', 'Crédito'],
        datasets: [{
            data: [
                <?php echo (float)$stats['monto_efectivo']; ?>,
                <?php echo (float)$stats['monto_tarjeta']; ?>,
                <?php echo (float)$stats['monto_transferencia']; ?>,
                <?php echo (float)$stats['monto_credito']; ?>
            ],
            backgroundColor: [
                'rgba(25, 135, 84, 0.8)',
                'rgba(13, 110, 253, 0.8)',
                'rgba(13, 202, 240, 0.8)',
                'rgba(255, 193, 7, 0.8)'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { boxWidth: 12 }
            }
        }
    }
});

// DataTable
$(document).ready(function() {
    $('#tablaPagos').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        pageLength: 25,
        order: [[1, 'desc']],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-excel me-1"></i>Excel',
                className: 'btn btn-success btn-sm',
                title: 'Reporte_Pagos_<?php echo $fecha_inicio; ?>_<?php echo $fecha_fin; ?>'
            },
            {
                extend: 'csvHtml5',
                text: '<i class="bi bi-filetype-csv me-1"></i>CSV',
                className: 'btn btn-info btn-sm',
                title: 'Reporte_Pagos_<?php echo $fecha_inicio; ?>_<?php echo $fecha_fin; ?>'
            }
        ]
    });
});

function imprimirRecibo(numero, fecha, paciente, ci, servicio, metodo, monto, estado) {
    const html = `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo ${numero}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; }
        .header { border-bottom: 3px solid #0d6efd; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; }
        .logo { display: flex; align-items: center; gap: 15px; }
        .logo-icon { font-size: 40px; }
        .empresa h1 { margin: 0; color: #333; font-size: 20px; }
        .empresa p { margin: 2px 0; font-size: 11px; color: #666; }
        .recibo-info h2 { color: #0d6efd; margin: 0 0 10px; font-size: 16px; }
        .recibo-info p { margin: 3px 0; font-size: 12px; }
        .datos { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .datos h3 { font-size: 13px; margin: 0 0 10px; color: #0d6efd; }
        .datos p { margin: 5px 0; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; font-size: 12px; }
        th { background: #0d6efd; color: white; }
        .total { text-align: right; font-size: 18px; font-weight: bold; color: #0d6efd; }
        .estado { display: inline-block; padding: 3px 10px; border-radius: 15px; font-size: 11px; }
        .estado-pagado { background: #d4edda; color: #155724; }
        .estado-pendiente { background: #fff3cd; color: #856404; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <span class="logo-icon">🦷</span>
            <div class="empresa">
                <h1>Clínica Dr. Muelitas</h1>
                <p>Santa Cruz de la Sierra - Bolivia</p>
                <p>NIT: 12345678901</p>
            </div>
        </div>
        <div class="recibo-info">
            <h2>RECIBO DE PAGO</h2>
            <p><strong>N°:</strong> ${numero}</p>
            <p><strong>Fecha:</strong> ${fecha}</p>
            <p><span class="estado ${estado === 'PAGADO' ? 'estado-pagado' : 'estado-pendiente'}">${estado}</span></p>
        </div>
    </div>
    
    <div class="datos">
        <h3>Datos del Paciente</h3>
        <p><strong>Nombre:</strong> ${paciente}</p>
        <p><strong>CI:</strong> ${ci}</p>
        <p><strong>Método:</strong> ${metodo}</p>
    </div>
    
    <table>
        <thead>
            <tr><th>Descripción</th><th style="text-align:right;">Importe</th></tr>
        </thead>
        <tbody>
            <tr><td>${servicio}</td><td style="text-align:right;">Bs. ${monto}</td></tr>
        </tbody>
    </table>
    
    <p class="total">TOTAL: Bs. ${monto}</p>
    
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
