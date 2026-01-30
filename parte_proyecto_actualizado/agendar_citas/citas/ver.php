<?php
session_start();
$sidebar_base = '../../';
$sidebar_carpeta = 'agendar_citas';

require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Odontologo.php';

if (!isset($_GET['id'])) { header('Location: calendario.php'); exit; }
$id_cita = (int)$_GET['id'];

$citaModel = new Cita();
$cita = $citaModel->obtenerPorId($id_cita);
if (!$cita) { echo "<h1>Cita no encontrada</h1>"; exit; }

$paciente = (new Paciente())->obtenerPorId($cita['id_paciente']);
$odontologo = (new Odontologo())->obtenerPorId($cita['id_odontologo']);

$fecha_solo = date('Y-m-d', strtotime($cita['fecha_hora_inicio']));
$hora_inicio = date('H:i', strtotime($cita['fecha_hora_inicio']));
$hora_fin = date('H:i', strtotime($cita['fecha_hora_fin']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Cita #<?php echo $id_cita; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/citas.css">
    <style>
        .view-form { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .status-badge { padding: 8px 16px; border-radius: 20px; font-weight: bold; font-size: 0.9rem; text-transform: uppercase; color: white; }
        .st-PROGRAMADA { background-color: #3498db; }
        .st-ATENDIDA { background-color: #2ecc71; }
        .st-CANCELADA { background-color: #e74c3c; }
        .st-NO_ASISTIO { background-color: #95a5a6; }
        .info-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .info-label { font-weight: 600; color: #2c3e50; margin-bottom: 5px; }
        .info-value { font-size: 1.1rem; color: #34495e; }
        .readonly-field { background-color: #e9ecef; border: 1px solid #ced4da; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../../panel/sidebar.php'; ?>
<div class="dashboard-main">
    <div class="page-header">
        <div class="d-flex align-items-center gap-3">
            <h1><i class="fas fa-eye"></i> Ver Cita #<?php echo $id_cita; ?></h1>
            <span class="status-badge st-<?php echo htmlspecialchars($cita['estado']); ?>"><?php echo htmlspecialchars($cita['estado']); ?></span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span id="hora-actual" class="badge bg-dark fs-6 px-3 py-2" style="font-family: monospace;">
                <i class="fas fa-clock me-1"></i> <span id="hora-actual-texto">--:--:--</span>
            </span>
            <small class="text-muted">Horario: 8:30 a.m.–12:30 p.m. / 3:30–6:30 p.m.</small>
            <a href="calendario.php" class="btn-primary" style="background-color: #7f8c8d; text-decoration: none;"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
    </div>

    <div style="max-width: 900px; margin: 0 auto;">
        <div class="view-form">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <div>
                    <h3 style="border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; color: #2c3e50;">
                        <i class="fas fa-user"></i> Datos del Paciente
                    </h3>
                    <div class="info-section">
                        <div class="info-label">Nombre Completo:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($paciente['nombres'] . ' ' . ($paciente['apellido_paterno'] ?? '') . ' ' . ($paciente['apellido_materno'] ?? '')); ?>
                        </div>
                        
                        <div class="info-label mt-3">Carnet de Identidad:</div>
                        <div class="info-value"><?php echo htmlspecialchars($paciente['ci'] ?? 'No especificado'); ?></div>
                        
                        <div class="info-label mt-3">Teléfono:</div>
                        <div class="info-value"><?php echo htmlspecialchars($paciente['telefono'] ?? 'No especificado'); ?></div>
                        
                        <div class="info-label mt-3">Correo:</div>
                        <div class="info-value"><?php echo htmlspecialchars($paciente['correo'] ?? 'No especificado'); ?></div>
                    </div>
                </div>

                <div>
                    <h3 style="border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; color: #2c3e50;">
                        <i class="fas fa-user-md"></i> Datos de la Cita
                    </h3>
                    <div class="info-section">
                        <div class="info-label">Odontólogo:</div>
                        <div class="info-value">
                            Dr. <?php echo htmlspecialchars($odontologo['nombres'] . ' ' . ($odontologo['apellido_paterno'] ?? '') . ' ' . ($odontologo['apellido_materno'] ?? '')); ?>
                        </div>
                        
                        <div class="info-label mt-3">Fecha:</div>
                        <div class="info-value"><?php echo date('d/m/Y', strtotime($cita['fecha_hora_inicio'])); ?></div>
                        
                        <div class="info-label mt-3">Hora Inicio:</div>
                        <div class="info-value"><?php echo date('h:i A', strtotime($cita['fecha_hora_inicio'])); ?></div>
                        
                        <div class="info-label mt-3">Hora Fin:</div>
                        <div class="info-value"><?php echo date('h:i A', strtotime($cita['fecha_hora_fin'])); ?></div>
                        
                        <div class="info-label mt-3">Estado:</div>
                        <div class="info-value">
                            <span class="status-badge st-<?php echo htmlspecialchars($cita['estado']); ?>">
                                <?php echo htmlspecialchars($cita['estado']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <h3 style="border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; color: #2c3e50;">
                    <i class="fas fa-notes-medical"></i> Motivo de la Cita
                </h3>
                <div class="readonly-field" style="min-height: 80px;">
                    <?php echo nl2br(htmlspecialchars($cita['motivo'] ?? 'No especificado')); ?>
                </div>
            </div>

            <?php if (!empty($cita['observaciones'])): ?>
            <div style="margin-top: 30px;">
                <h3 style="border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; color: #2c3e50;">
                    <i class="fas fa-comment-medical"></i> Observaciones
                </h3>
                <div class="readonly-field" style="min-height: 60px;">
                    <?php echo nl2br(htmlspecialchars($cita['observaciones'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 8px; text-align: center;">
                <p class="mb-0" style="color: #1976d2; font-weight: 500;">
                    <i class="fas fa-info-circle"></i> 
                    Esta es una vista de solo lectura. Si necesita realizar cambios en esta cita, 
                    por favor contacte al personal administrativo.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function actualizarHora() {
    var now = new Date();
    var h = String(now.getHours()).padStart(2, '0');
    var m = String(now.getMinutes()).padStart(2, '0');
    var s = String(now.getSeconds()).padStart(2, '0');
    var el = document.getElementById('hora-actual-texto');
    if (el) el.textContent = h + ':' + m + ':' + s;
}
actualizarHora();
setInterval(actualizarHora, 1000);
</script>

</body>
</html>
