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

// Historial de pagos (últimos 30)
$sqlHistorial = "
    SELECT 
        p.id_pago,
        DATE_FORMAT(p.fecha_pago, '%d/%m/%Y %H:%i') AS fecha,
        p.monto,
        p.metodo,
        p.estado,
        r.numero_recibo,
        CONCAT(pac.nombres,' ',pac.apellido_paterno) AS paciente,
        pac.ci,
        c.motivo AS servicio,
        a.diagnostico
    FROM pagos p
    JOIN recibos r ON r.id_pago = p.id_pago
    JOIN atenciones a ON a.id_atencion = p.id_atencion
    JOIN citas c ON c.id_cita = a.id_cita
    JOIN pacientes pac ON pac.id_paciente = c.id_paciente
    ORDER BY p.fecha_pago DESC
    LIMIT 30
";
$historial = $pdo->query($sqlHistorial)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Pagos - Clínica Odontológica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0d6efd;
        }
        body { background-color: #f8f9fa; }
        .card-form {
            border-radius: 15px;
            border: none;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .nav-pagos .nav-link {
            color: #6c757d;
            border-radius: 10px;
            margin-right: 5px;
            padding: 10px 20px;
        }
        .nav-pagos .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        .nav-pagos .nav-link:hover:not(.active) {
            background-color: #e9ecef;
        }
        .table-pagos th {
            background-color: #f8f9fa;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-metodo {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .btn-imprimir {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .btn-imprimir:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
            color: white;
        }
    </style>
</head>
<body>
<?php $sidebar_base = '../'; $sidebar_carpeta = 'pagos'; require_once __DIR__ . '/../panel/sidebar.php'; ?>

<div class="dashboard-main">
<div class="container-fluid py-4 px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-clock-history me-2"></i>Historial de Pagos</h2>
            <p class="text-muted mb-0">Listado de los últimos pagos registrados en el sistema</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-secondary fs-6 py-2 px-3">
                <i class="bi bi-person me-1"></i><?php echo $_SESSION['NombreUsuario'] ?? 'Usuario'; ?>
            </span>
        </div>
    </div>

    <!-- Navegación -->
    <ul class="nav nav-pagos mb-4">
        <li class="nav-item">
            <a class="nav-link" href="index.php"><i class="bi bi-plus-circle me-1"></i>Registrar Pago</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="ultimos_pagos.php"><i class="bi bi-clock-history me-1"></i>Historial de Pagos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="estado_cuenta.php"><i class="bi bi-person-lines-fill me-1"></i>Estado de Cuenta</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="reportes.php"><i class="bi bi-graph-up me-1"></i>Reportes</a>
        </li>
    </ul>

    <!-- Tabla Historial de Pagos -->
    <div class="card card-form">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Historial de Pagos</h5>
            <span class="badge bg-primary"><?php echo count($historial); ?> registros</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-hover table-pagos mb-0">
                    <thead class="sticky-top">
                        <tr>
                            <th>Fecha</th>
                            <th>Paciente</th>
                            <th>Servicio</th>
                            <th>Monto</th>
                            <th>Método</th>
                            <th>Estado</th>
                            <th>Recibo</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historial)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                    No hay pagos registrados
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historial as $p): ?>
                                <tr>
                                    <td class="small"><?php echo htmlspecialchars($p['fecha']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($p['paciente']); ?></strong>
                                        <br><small class="text-muted">CI: <?php echo htmlspecialchars($p['ci']); ?></small>
                                    </td>
                                    <td class="small"><?php echo htmlspecialchars(substr($p['servicio'] ?? '-', 0, 30)); ?></td>
                                    <td><strong>Bs. <?php echo number_format((float)$p['monto'], 2); ?></strong></td>
                                    <td>
                                        <?php 
                                        $iconos = [
                                            'EFECTIVO' => 'cash text-success',
                                            'TARJETA' => 'credit-card text-primary',
                                            'TRANSFERENCIA' => 'bank text-info',
                                            'CREDITO' => 'credit-card-2-front text-warning'
                                        ];
                                        $icono = $iconos[$p['metodo']] ?? 'cash';
                                        ?>
                                        <span class="badge badge-metodo bg-light text-dark">
                                            <i class="bi bi-<?php echo $icono; ?> me-1"></i><?php echo $p['metodo']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $p['estado'] === 'PAGADO' ? 'success' : 'warning'; ?>">
                                            <?php echo $p['estado']; ?>
                                        </span>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($p['numero_recibo']); ?></code></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-imprimir" 
                                            onclick="imprimirRecibo(
                                                '<?php echo htmlspecialchars($p['numero_recibo'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($p['fecha'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($p['paciente'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($p['ci'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($p['servicio'] ?? 'Servicio odontológico', ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($p['metodo'], ENT_QUOTES); ?>',
                                                '<?php echo number_format((float)$p['monto'], 2); ?>',
                                                '<?php echo $p['estado']; ?>'
                                            )">
                                            <i class="bi bi-printer"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function imprimirRecibo(numero, fecha, paciente, ci, servicio, metodo, monto, estado) {
    const html = `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo ${numero}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; background: #fff; }
        .recibo { max-width: 800px; margin: 0 auto; border: 2px solid #333; padding: 30px; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid #0d6efd; padding-bottom: 20px; margin-bottom: 20px; }
        .logo { display: flex; align-items: center; gap: 15px; }
        .logo-icon { width: 70px; height: 70px; background: linear-gradient(135deg, #0d6efd, #6610f2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 35px; }
        .empresa h1 { font-size: 22px; color: #333; margin-bottom: 5px; }
        .empresa p { font-size: 12px; color: #666; }
        .recibo-info { text-align: right; }
        .recibo-info h2 { color: #0d6efd; font-size: 18px; margin-bottom: 10px; }
        .recibo-info p { font-size: 13px; color: #333; margin: 3px 0; }
        .datos-paciente { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .datos-paciente h3 { font-size: 14px; color: #0d6efd; margin-bottom: 10px; text-transform: uppercase; }
        .datos-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .datos-grid p { font-size: 13px; }
        .datos-grid strong { color: #333; }
        .detalle { margin: 20px 0; }
        .detalle h3 { font-size: 14px; color: #0d6efd; margin-bottom: 10px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #0d6efd; color: white; font-size: 12px; text-transform: uppercase; }
        td { font-size: 13px; }
        .total-row { background: #f8f9fa; font-weight: bold; }
        .total-row td:last-child { color: #0d6efd; font-size: 16px; }
        .estado { display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .estado-pagado { background: #d4edda; color: #155724; }
        .estado-pendiente { background: #fff3cd; color: #856404; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; display: flex; justify-content: space-between; font-size: 11px; color: #666; }
        .firma { text-align: center; margin-top: 40px; }
        .firma-linea { width: 200px; border-top: 1px solid #333; margin: 0 auto 5px; }
        @media print {
            body { padding: 0; }
            .recibo { border: none; }
        }
    </style>
</head>
<body>
    <div class="recibo">
        <div class="header">
            <div class="logo">
                <div class="logo-icon">🦷</div>
                <div class="empresa">
                    <h1>Clínica Dr. Muelitas</h1>
                    <p>Santa Cruz de la Sierra - Bolivia</p>
                    <p>Tel: (591) 3-XXX-XXXX</p>
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
        
        <div class="datos-paciente">
            <h3>Datos del Paciente</h3>
            <div class="datos-grid">
                <p><strong>Nombre:</strong> ${paciente}</p>
                <p><strong>CI:</strong> ${ci}</p>
                <p><strong>Método de Pago:</strong> ${metodo}</p>
                <p><strong>Tipo:</strong> ${estado === 'PENDIENTE' ? 'Pago Parcial' : 'Pago Total'}</p>
            </div>
        </div>
        
        <div class="detalle">
            <h3>Detalle del Servicio</h3>
            <table>
                <thead>
                    <tr>
                        <th>Cant.</th>
                        <th>Descripción</th>
                        <th>P. Unit.</th>
                        <th>Importe</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>${servicio}</td>
                        <td>Bs. ${monto}</td>
                        <td>Bs. ${monto}</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;">TOTAL ${estado === 'PENDIENTE' ? 'ABONADO' : 'PAGADO'}:</td>
                        <td>Bs. ${monto}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="firma">
            <div class="firma-linea"></div>
            <p>Firma y Sello</p>
        </div>
        
        <div class="footer">
            <p>Este documento es un comprobante válido de pago.</p>
            <p>Gracias por su preferencia.</p>
        </div>
    </div>
    <script>window.onload = function() { window.print(); }<\/script>
</body>
</html>`;

    const win = window.open('', '_blank');
    if (win) {
        win.document.write(html);
        win.document.close();
    } else {
        alert('Permite ventanas emergentes para imprimir el recibo.');
    }
}
</script>
</body>
</html>
