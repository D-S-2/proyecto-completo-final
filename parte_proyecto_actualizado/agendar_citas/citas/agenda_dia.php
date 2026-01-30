<?php
session_start();
$sidebar_base = '../../';
$sidebar_carpeta = 'agendar_citas';

require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../models/Odontologo.php';

$citaModel = new Cita();
$odoModel = new Odontologo();
$doctores = $odoModel->listarTodos();

$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$id_odontologo = isset($_GET['id_odontologo']) && $_GET['id_odontologo'] !== '' ? (int)$_GET['id_odontologo'] : null;
$citas = $citaModel->obtenerCitasDelDia($fecha, $id_odontologo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda del Día - Dr. Muelitas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/citas.css">
</head>
<body>
<?php require_once __DIR__ . '/../../panel/sidebar.php'; ?>
<div class="dashboard-main">
    <div class="page-header">
        <h1><i class="fas fa-calendar-day"></i> Agenda del Día</h1>
        <div class="d-flex align-items-center gap-3">
            <span id="hora-actual" class="badge bg-dark fs-6 px-3 py-2" style="font-family: monospace;">
                <i class="fas fa-clock me-1"></i> <span id="hora-actual-texto">--:--:--</span>
            </span>
            <small class="text-muted">Horario: 8:30 a.m.–12:30 p.m. / 3:30–6:30 p.m.</small>
            <a href="calendario.php" class="btn" style="background-color: #1E6F78; color: white; text-decoration: none; border: none;"><i class="far fa-calendar-alt"></i> Ver Calendario</a>
            <a href="nueva.php" class="btn" style="background-color: #1E6F78; color: white; text-decoration: none; border: none;"><i class="fas fa-plus"></i> Nueva Cita</a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Fecha:</label>
                    <input type="date" name="fecha" class="form-control" value="<?php echo htmlspecialchars($fecha); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Doctor:</label>
                    <select name="id_odontologo" class="form-select">
                        <option value="">Todos los doctores</option>
                        <?php foreach ($doctores as $d): ?>
                            <option value="<?php echo $d['id_odontologo']; ?>" <?php echo ($id_odontologo == $d['id_odontologo']) ? 'selected' : ''; ?>>
                                Dr. <?php echo htmlspecialchars($d['nombres'] . ' ' . $d['apellidos']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn w-100" style="background-color: #1E6F78; color: white; border: none;"><i class="fas fa-search"></i> Buscar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm overflow-hidden">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="far fa-clock"></i>
                <?php
                setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain');
                echo strftime('%A, %d de %B de %Y', strtotime($fecha));
                ?>
            </h5>
            <span class="badge bg-light text-dark"><?php echo count($citas); ?> cita(s)</span>
        </div>
        <?php if (empty($citas)): ?>
            <div class="text-center py-5 text-muted">
                <i class="far fa-calendar-times fa-4x mb-3 opacity-25"></i>
                <p class="fs-5">No hay citas programadas para este día</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($citas as $c):
                    $hora_inicio = date('H:i', strtotime($c['fecha_hora_inicio']));
                    $hora_fin = date('H:i', strtotime($c['fecha_hora_fin']));
                    $color_borde = '#3498db';
                    $color_fondo = '#e3f2fd';
                    $icono_estado = 'fa-clock';
                    if ($c['estado'] == 'ATENDIDA') {
                        $color_borde = '#2ecc71';
                        $color_fondo = '#e8f5e9';
                        $icono_estado = 'fa-check-circle';
                    } elseif ($c['estado'] == 'CANCELADA') {
                        $color_borde = '#e74c3c';
                        $color_fondo = '#ffebee';
                        $icono_estado = 'fa-ban';
                    } elseif ($c['estado'] == 'NO_ASISTIO') {
                        $color_borde = '#95a5a6';
                        $color_fondo = '#f5f5f5';
                        $icono_estado = 'fa-user-times';
                    }
                    $dep = isset($c['departamento']) && $c['departamento'] ? ' (' . htmlspecialchars($c['departamento']) . ')' : '';
                ?>
                    <div class="list-group-item d-flex gap-3 align-items-center" style="border-left: 5px solid <?php echo $color_borde; ?>; background: <?php echo $color_fondo; ?>;">
                        <div class="text-center" style="min-width: 80px;">
                            <div class="fw-bold fs-5" style="color: <?php echo $color_borde; ?>;"><?php echo $hora_inicio; ?></div>
                            <small class="text-muted"><?php echo $hora_fin; ?></small>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="mb-0"><?php echo htmlspecialchars($c['paciente']); ?></h6>
                                <span class="badge bg-white text-dark border">CI: <?php echo htmlspecialchars($c['ci'] . $dep); ?></span>
                            </div>
                            <p class="mb-0 small text-muted"><i class="fas fa-stethoscope"></i> <strong><?php echo htmlspecialchars($c['odontologo']); ?></strong></p>
                            <p class="mb-0 small"><i class="fas fa-comment-medical"></i> <?php echo htmlspecialchars($c['motivo'] ?? ''); ?></p>
                            <?php if (!empty($c['telefono'])): ?>
                                <p class="mb-0 small"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($c['telefono']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <span class="badge rounded-pill mb-2" style="background: <?php echo $color_borde; ?>;">
                                <i class="fas <?php echo $icono_estado; ?>"></i> <?php echo htmlspecialchars($c['estado']); ?>
                            </span>
                            <div>
                                <a href="editar.php?id=<?php echo $c['id_cita']; ?>" class="btn btn-sm btn-outline-primary">Ver / Editar</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-4 text-center">
        <button onclick="window.print()" class="btn btn-secondary"><i class="fas fa-print"></i> Imprimir Agenda</button>
    </div>
</div>

<script>
(function() {
    function actualizarHora() {
        var el = document.getElementById('hora-actual-texto');
        if (!el) return;
        var now = new Date();
        el.textContent = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ':' + String(now.getSeconds()).padStart(2, '0');
    }
    actualizarHora();
    setInterval(actualizarHora, 1000);
})();
</script>
<style>
@media print {
    .dashboard-sidebar, .page-header a, .btn, button, .card-header .badge, nav, #hora-actual { display: none !important; }
    .dashboard-main { margin-left: 0 !important; }
    body { background: white; }
}
</style>
</body>
</html>
