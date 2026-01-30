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

$msgOk = '';
$msgError = '';

// Registrar pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_pago'])) {
    $id_atencion = (int)($_POST['id_atencion'] ?? 0);
    $monto       = (float)($_POST['monto'] ?? 0);
    $metodo      = $_POST['metodo'] ?? 'EFECTIVO';
    $tipo_pago   = $_POST['tipo_pago'] ?? 'TOTAL';
    $observacion = trim($_POST['observacion'] ?? '');

    if ($id_atencion <= 0 || $monto <= 0) {
        $msgError = 'Selecciona una atención y un monto válido.';
    } else {
        $metodosValidos = ['EFECTIVO','TARJETA','TRANSFERENCIA','CREDITO'];
        $metodo = strtoupper($metodo);
        if (!in_array($metodo, $metodosValidos, true)) $metodo = 'EFECTIVO';

        // Determinar estado según tipo de pago
        $estado = ($tipo_pago === 'PARCIAL') ? 'PENDIENTE' : 'PAGADO';

        try {
            $pdo->beginTransaction();

            // Insertar en pagos
            $stmt = $pdo->prepare("
                INSERT INTO pagos (id_atencion, monto, metodo, estado, registrado_por)
                VALUES (?, ?, ?, ?, ?)
            ");
            $id_usuario = $_SESSION['id_usuario'] ?? null;
            $stmt->execute([$id_atencion, $monto, $metodo, $estado, $id_usuario]);
            $id_pago = (int)$pdo->lastInsertId();

            // Generar número de recibo
            $st = $pdo->query("
                SELECT COALESCE(MAX(CAST(SUBSTRING(numero_recibo, 3) AS UNSIGNED)), 0) + 1 AS sig
                FROM recibos
            ");
            $row = $st->fetch();
            $numero = 'R-' . str_pad((int)$row['sig'], 5, '0', STR_PAD_LEFT);

            $stmtR = $pdo->prepare("
                INSERT INTO recibos (id_pago, numero_recibo, total)
                VALUES (?, ?, ?)
            ");
            $stmtR->execute([$id_pago, $numero, $monto]);

            $pdo->commit();
            $tipoTexto = ($tipo_pago === 'PARCIAL') ? 'Pago parcial' : 'Pago total';
            $msgOk = "{$tipoTexto} registrado correctamente. Recibo: {$numero}";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msgError = 'Error al registrar el pago: ' . $e->getMessage();
        }
    }
}

// Obtener atenciones con saldo pendiente o sin pago
$sqlAtenciones = "
    SELECT 
        a.id_atencion,
        DATE_FORMAT(a.fecha_atencion, '%d/%m/%Y %H:%i') AS fecha,
        a.diagnostico,
        CONCAT(p.nombres, ' ', p.apellido_paterno, ' ', IFNULL(p.apellido_materno,'')) AS paciente,
        p.ci,
        c.motivo AS servicio,
        COALESCE((SELECT SUM(monto) FROM pagos WHERE id_atencion = a.id_atencion), 0) AS total_pagado,
        COALESCE(t.total_servicios, 0) AS total_servicio
    FROM atenciones a
    JOIN citas c ON c.id_cita = a.id_cita
    JOIN pacientes p ON p.id_paciente = c.id_paciente
    LEFT JOIN vw_atencion_total t ON t.id_atencion = a.id_atencion
    ORDER BY a.fecha_atencion DESC
";
$atenciones = $pdo->query($sqlAtenciones)->fetchAll();

// Filtrar atenciones que tienen saldo pendiente o no tienen pagos
$atencionesConSaldo = array_filter($atenciones, function($a) {
    $saldo = $a['total_servicio'] - $a['total_pagado'];
    return $saldo > 0 || $a['total_pagado'] == 0;
});

// Estadísticas rápidas
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_pagos,
        SUM(CASE WHEN estado = 'PAGADO' THEN monto ELSE 0 END) as total_cobrado,
        SUM(CASE WHEN estado = 'PENDIENTE' THEN monto ELSE 0 END) as total_pendiente,
        COUNT(CASE WHEN estado = 'PENDIENTE' THEN 1 END) as pagos_pendientes
    FROM pagos
    WHERE DATE(fecha_pago) = CURDATE()
")->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pagos - Clínica Odontológica</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        body { background-color: #f8f9fa; }
        .stats-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .stats-card:hover { transform: translateY(-3px); }
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
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
        .form-floating > label { color: #6c757d; }
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
            <h2 class="mb-1"><i class="bi bi-cash-coin me-2"></i>Gestión de Pagos</h2>
            <p class="text-muted mb-0">Registro y control de pagos de la clínica</p>
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
            <a class="nav-link active" href="index.php"><i class="bi bi-plus-circle me-1"></i>Registrar Pago</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="ultimos_pagos.php"><i class="bi bi-clock-history me-1"></i>Historial de Pagos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="estado_cuenta.php"><i class="bi bi-person-lines-fill me-1"></i>Estado de Cuenta</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="reportes.php"><i class="bi bi-graph-up me-1"></i>Reportes</a>
        </li>
    </ul>

    <!-- Estadísticas del día -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 small">Pagos Hoy</h6>
                        <h4 class="mb-0"><?php echo (int)$stats['total_pagos']; ?></h4>
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
                        <h6 class="text-muted mb-1 small">Cobrado Hoy</h6>
                        <h4 class="mb-0">Bs. <?php echo number_format((float)$stats['total_cobrado'], 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 small">Pendiente Hoy</h6>
                        <h4 class="mb-0">Bs. <?php echo number_format((float)$stats['total_pendiente'], 2); ?></h4>
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
                        <h6 class="text-muted mb-1 small">Pagos Parciales</h6>
                        <h4 class="mb-0"><?php echo (int)$stats['pagos_pendientes']; ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Formulario de Pago -->
        <div class="col-12">
            <div class="card card-form">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="bi bi-plus-lg me-2"></i>Registrar Nuevo Pago</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($msgOk): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($msgOk); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($msgError): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($msgError); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" id="formPago">
                        <input type="hidden" name="registrar_pago" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Seleccionar Atención</label>
                            <select name="id_atencion" id="id_atencion" class="form-select form-select-lg" required onchange="actualizarInfoAtencion()">
                                <option value="">-- Seleccione una atención --</option>
                                <?php foreach ($atencionesConSaldo as $a): 
                                    $saldo = max(0, $a['total_servicio'] - $a['total_pagado']);
                                ?>
                                    <option value="<?php echo (int)$a['id_atencion']; ?>" 
                                            data-paciente="<?php echo htmlspecialchars($a['paciente']); ?>"
                                            data-ci="<?php echo htmlspecialchars($a['ci']); ?>"
                                            data-servicio="<?php echo htmlspecialchars($a['servicio']); ?>"
                                            data-fecha="<?php echo htmlspecialchars($a['fecha']); ?>"
                                            data-total="<?php echo $a['total_servicio']; ?>"
                                            data-pagado="<?php echo $a['total_pagado']; ?>"
                                            data-saldo="<?php echo $saldo; ?>">
                                        #<?php echo $a['id_atencion']; ?> - <?php echo htmlspecialchars($a['paciente']); ?> - <?php echo $a['fecha']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Info de la atención seleccionada -->
                        <div id="infoAtencion" class="card bg-light mb-3 d-none">
                            <div class="card-body py-2">
                                <div class="row small">
                                    <div class="col-6"><strong>Paciente:</strong> <span id="infoPaciente">-</span></div>
                                    <div class="col-6"><strong>CI:</strong> <span id="infoCI">-</span></div>
                                    <div class="col-12"><strong>Servicio:</strong> <span id="infoServicio">-</span></div>
                                    <div class="col-4"><strong>Total:</strong> <span id="infoTotal" class="text-primary">Bs. 0</span></div>
                                    <div class="col-4"><strong>Pagado:</strong> <span id="infoPagado" class="text-success">Bs. 0</span></div>
                                    <div class="col-4"><strong>Saldo:</strong> <span id="infoSaldo" class="text-danger fw-bold">Bs. 0</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tipo de Pago</label>
                                <select name="tipo_pago" id="tipo_pago" class="form-select" onchange="ajustarMonto()">
                                    <option value="TOTAL">Pago Total</option>
                                    <option value="PARCIAL">Pago Parcial</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Monto (Bs.)</label>
                                <input type="number" step="0.01" min="0.01" name="monto" id="monto" 
                                       class="form-control" required placeholder="0.00">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label fw-semibold">Método de Pago</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="metodo" id="metodo1" value="EFECTIVO" checked>
                                    <label class="btn btn-outline-success w-100" for="metodo1">
                                        <i class="bi bi-cash me-1"></i>Efectivo
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="metodo" id="metodo2" value="TARJETA">
                                    <label class="btn btn-outline-primary w-100" for="metodo2">
                                        <i class="bi bi-credit-card me-1"></i>Tarjeta
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="metodo" id="metodo3" value="TRANSFERENCIA">
                                    <label class="btn btn-outline-info w-100" for="metodo3">
                                        <i class="bi bi-bank me-1"></i>Transferencia
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="metodo" id="metodo4" value="CREDITO">
                                    <label class="btn btn-outline-warning w-100" for="metodo4">
                                        <i class="bi bi-credit-card-2-front me-1"></i>Crédito
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Observación (opcional)</label>
                            <textarea name="observacion" class="form-control" rows="2" placeholder="Notas adicionales..."></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Registrar Pago
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let saldoActual = 0;

function actualizarInfoAtencion() {
    const select = document.getElementById('id_atencion');
    const infoDiv = document.getElementById('infoAtencion');
    const option = select.options[select.selectedIndex];
    
    if (select.value) {
        infoDiv.classList.remove('d-none');
        document.getElementById('infoPaciente').textContent = option.dataset.paciente;
        document.getElementById('infoCI').textContent = option.dataset.ci;
        document.getElementById('infoServicio').textContent = option.dataset.servicio || '-';
        document.getElementById('infoTotal').textContent = 'Bs. ' + parseFloat(option.dataset.total || 0).toFixed(2);
        document.getElementById('infoPagado').textContent = 'Bs. ' + parseFloat(option.dataset.pagado || 0).toFixed(2);
        document.getElementById('infoSaldo').textContent = 'Bs. ' + parseFloat(option.dataset.saldo || 0).toFixed(2);
        saldoActual = parseFloat(option.dataset.saldo || 0);
        ajustarMonto();
    } else {
        infoDiv.classList.add('d-none');
        saldoActual = 0;
    }
}

function ajustarMonto() {
    const tipoPago = document.getElementById('tipo_pago').value;
    const montoInput = document.getElementById('monto');
    
    if (tipoPago === 'TOTAL' && saldoActual > 0) {
        montoInput.value = saldoActual.toFixed(2);
    }
}
</script>
</body>
</html>
