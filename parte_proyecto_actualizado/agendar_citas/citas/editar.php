<?php
session_start();
$sidebar_base = '../../';
$sidebar_carpeta = 'agendar_citas';

// Redirect dentists to view-only page
if (($_SESSION['rol'] ?? '') === 'DOCTOR') {
    if (isset($_GET['id'])) {
        header('Location: ver.php?id=' . (int)$_GET['id']);
    } else {
        header('Location: calendario.php');
    }
    exit;
}

require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Odontologo.php';

if (!isset($_GET['id'])) { header('Location: calendario.php'); exit; }
$id_cita = (int)$_GET['id'];

$citaModel = new Cita();
$cita = $citaModel->obtenerPorId($id_cita);
if (!$cita) { echo "<h1>Cita no encontrada</h1>"; exit; }

$pacientes = (new Paciente())->listarTodos();
$doctores = (new Odontologo())->listarTodos();

$fecha_solo = date('Y-m-d', strtotime($cita['fecha_hora_inicio']));
$hora_inicio = date('H:i', strtotime($cita['fecha_hora_inicio']));
$hora_fin = date('H:i', strtotime($cita['fecha_hora_fin']));
if (!isset($cita['departamento'])) $cita['departamento'] = '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Cita #<?php echo $id_cita; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/citas.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .clinical-form { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .status-badge { padding: 5px 12px; border-radius: 15px; font-weight: bold; font-size: 0.9rem; text-transform: uppercase; color: white; }
        .st-PROGRAMADA { background-color: #3498db; } .st-ATENDIDA { background-color: #2ecc71; }
        .st-CANCELADA { background-color: #e74c3c; } .st-NO_ASISTIO { background-color: #95a5a6; }
        .category-label { font-size: 0.7rem; font-weight: 800; color: #95a5a6; margin-top: 10px; margin-bottom: 4px; text-transform: uppercase; border-bottom: 1px solid #eee; }
        .treatment-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 6px; }
        .t-btn { background: #fff; border: 1px solid #dcdcdc; border-radius: 4px; padding: 6px 8px; cursor: pointer; font-size: 0.8rem; color: #2c3e50; display: flex; justify-content: space-between; align-items: center; }
        .t-btn:hover { background: #f1f8ff; border-color: #3498db; } .t-btn.active { background-color: #3498db; color: white; border-color: #2980b9; font-weight: 600; }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../../panel/sidebar.php'; ?>
<div class="dashboard-main">
    <div class="page-header">
        <div class="d-flex align-items-center gap-3">
            <h1><i class="fas fa-edit"></i> Cita #<?php echo $id_cita; ?></h1>
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

    <div style="max-width: 1100px; margin: 0 auto;">
        <?php if ($cita['estado'] == 'PROGRAMADA'): ?>
        <div class="alert alert-light border mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <strong>Acciones:</strong>
            <div class="d-flex gap-2">
                <a href="../controllers/citaController.php?accion=atendida&id=<?php echo $id_cita; ?>" class="btn btn-success btn-sm" onclick="return confirm('¿Confirmar que el paciente ASISTIÓ a la cita?')"><i class="fas fa-check-circle"></i> Confirmar Asistencia</a>
                <a href="../controllers/citaController.php?accion=no_asistio&id=<?php echo $id_cita; ?>" class="btn btn-secondary btn-sm" onclick="return confirm('¿Marcar que NO ASISTIÓ?')"><i class="fas fa-user-slash"></i> No Asistió</a>
                <a href="../controllers/citaController.php?accion=cancelar&id=<?php echo $id_cita; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Cancelar cita?')"><i class="fas fa-times-circle"></i> Cancelar</a>
            </div>
        </div>
        <?php endif; ?>

        <form action="../controllers/citaController.php" method="POST" class="clinical-form" id="formEditar">
            <input type="hidden" name="id_cita" value="<?php echo $id_cita; ?>">
            <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 30px;">
                <div>
                    <h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; color: #2c3e50;"><i class="fas fa-info-circle"></i> Detalles</h3>
                    <div class="form-group">
                        <label>Paciente:</label>
                        <select name="id_paciente" class="form-control select2" required style="width: 100%;" <?php echo $cita['estado'] != 'PROGRAMADA' ? 'disabled' : ''; ?>>
                            <?php foreach ($pacientes as $p): ?>
                                <option value="<?php echo $p['id_paciente']; ?>" <?php echo ($p['id_paciente'] == $cita['id_paciente']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(($p['ci'] ?? '') . ' - ' . $p['nombres'] . ' ' . $p['apellido_paterno']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mt-3">
                        <label>Odontólogo:</label>
                        <select name="id_odontologo" class="form-control select2" required style="width: 100%;" <?php echo $cita['estado'] != 'PROGRAMADA' ? 'disabled' : ''; ?>>
                            <?php foreach ($doctores as $d): ?>
                                <option value="<?php echo $d['id_odontologo']; ?>" <?php echo ($d['id_odontologo'] == $cita['id_odontologo']) ? 'selected' : ''; ?>>
                                    Dr. <?php echo htmlspecialchars($d['nombres'] . ' ' . $d['apellidos']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px;">
                        <div class="text-secondary small mb-2"><i class="fas fa-clock"></i> <b>Horario</b></div>
                        <input type="date" name="fecha" id="fecha" required class="form-control" value="<?php echo $fecha_solo; ?>" min="<?php echo date('Y-m-d'); ?>" <?php echo $cita['estado'] != 'PROGRAMADA' ? 'readonly' : ''; ?>>
                        <div class="d-flex gap-2 mt-2">
                            <div class="flex-grow-1">
                                <label class="small">Inicio:</label>
                                <input type="time" name="hora_inicio" id="h_ini" required class="form-control" value="<?php echo $hora_inicio; ?>" <?php echo $cita['estado'] != 'PROGRAMADA' ? 'readonly' : ''; ?>>
                            </div>
                            <div class="flex-grow-1">
                                <label class="small">Fin:</label>
                                <input type="time" name="hora_fin" id="h_fin" required class="form-control" value="<?php echo $hora_fin; ?>" readonly>
                            </div>
                        </div>
                        <div id="alerta-horario-editar" class="small fw-bold mt-2" style="display: none; color: #c0392b;"><i class="fas fa-exclamation-triangle me-1"></i> Fuera de horario</div>
                        <div class="form-group mt-3 pt-3 border-top">
                            <label>Motivo:</label>
                            <textarea name="motivo" id="motivo" class="form-control" rows="2" required <?php echo $cita['estado'] != 'PROGRAMADA' ? 'readonly' : ''; ?>><?php echo htmlspecialchars($cita['motivo'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; color: #2c3e50;"><i class="fas fa-list-ul"></i> Tratamiento</h3>
                    <div class="category-label">1. Diagnóstico</div>
                    <div class="treatment-grid">
                        <div class="t-btn" data-nombre="Consulta / Valoración" onclick="seleccionarTratamiento(this, 'Consulta / Valoración', 15)"><span>Consulta</span><b>15m</b></div>
                        <div class="t-btn" data-nombre="Urgencia / Dolor Agudo" onclick="seleccionarTratamiento(this, 'Urgencia / Dolor Agudo', 30)"><span>Urgencia</span><b>30m</b></div>
                    </div>
                    <div class="category-label">2. Higiene</div>
                    <div class="treatment-grid">
                        <div class="t-btn" data-nombre="Limpieza Dental (Profilaxis)" onclick="seleccionarTratamiento(this, 'Limpieza Dental (Profilaxis)', 30)"><span>Limpieza</span><b>30m</b></div>
                        <div class="t-btn" data-nombre="Blanqueamiento Dental" onclick="seleccionarTratamiento(this, 'Blanqueamiento Dental', 60)"><span>Blanqueamiento</span><b>60m</b></div>
                    </div>
                    <div class="category-label">3. Operatoria</div>
                    <div class="treatment-grid">
                        <div class="t-btn" data-nombre="Curación Simple" onclick="seleccionarTratamiento(this, 'Curación Simple', 30)"><span>Simple</span><b>30m</b></div>
                        <div class="t-btn" data-nombre="Curación Media" onclick="seleccionarTratamiento(this, 'Curación Media', 45)"><span>Media</span><b>45m</b></div>
                        <div class="t-btn" data-nombre="Curación Compleja" onclick="seleccionarTratamiento(this, 'Curación Compleja', 60)"><span>Compleja</span><b>60m</b></div>
                    </div>
                    <div class="category-label">4. Extracciones</div>
                    <div class="treatment-grid">
                        <div class="t-btn" data-nombre="Extracción de Incisivos" onclick="seleccionarTratamiento(this, 'Extracción de Incisivos', 30)"><span>Incisivos</span><b>30m</b></div>
                        <div class="t-btn" data-nombre="Extracción de Molares" onclick="seleccionarTratamiento(this, 'Extracción de Molares', 60)"><span>Molares</span><b>60m</b></div>
                    </div>
                    <?php if ($cita['estado'] == 'PROGRAMADA'): ?>
                    <div class="mt-4">
                        <button type="submit" class="btn w-100 py-3" style="background-color: #1E6F78; color: white; border: none;"><i class="fas fa-save"></i> GUARDAR CAMBIOS</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2();
    var motivoActual = "<?php echo addslashes($cita['motivo'] ?? ''); ?>";
    $(".t-btn[data-nombre='" + motivoActual.replace(/'/g, "\\'") + "']").addClass('active');
});
var hInicio = document.getElementById('h_ini');
var hFin = document.getElementById('h_fin');
var motivoTxt = document.getElementById('motivo');
var estadoCita = "<?php echo $cita['estado']; ?>";
var minutosSeleccionados = 0;

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

function enRangoHorario(horaStr) {
    if (!horaStr) return true;
    var parts = horaStr.split(':');
    var min = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
    return (min >= 510 && min <= 750) || (min >= 930 && min <= 1110);
}
function validarHorarioMostrar() {
    var fuera = !enRangoHorario(hInicio.value) || !enRangoHorario(hFin.value);
    var alerta = document.getElementById('alerta-horario-editar');
    if (fuera && (hInicio.value || hFin.value)) {
        if (alerta) alerta.style.display = 'block';
        if (hInicio) hInicio.style.borderColor = '#c0392b';
    } else {
        if (alerta) alerta.style.display = 'none';
        if (hInicio) hInicio.style.borderColor = '';
    }
}

function seleccionarTratamiento(elemento, nombre, minutos) {
    if (estadoCita !== 'PROGRAMADA') return;
    document.querySelectorAll('.t-btn').forEach(function(el) { el.classList.remove('active'); });
    elemento.classList.add('active');
    minutosSeleccionados = minutos;
    motivoTxt.value = nombre;
    calcularHoraFin();
}
function calcularHoraFin() {
    if (minutosSeleccionados === 0 || !hInicio.value) return;
    var d = new Date("2000-01-01T" + hInicio.value + ":00");
    d.setMinutes(d.getMinutes() + minutosSeleccionados);
    hFin.value = String(d.getHours()).padStart(2, '0') + ":" + String(d.getMinutes()).padStart(2, '0');
    validarHorarioMostrar();
}
hInicio.addEventListener('change', function() {
    if (document.querySelector('.t-btn.active')) calcularHoraFin();
    validarHorarioMostrar();
});
setInterval(validarHorarioMostrar, 500);

document.getElementById('formEditar').addEventListener('submit', function(e) {
    if (!enRangoHorario(hInicio.value) || !enRangoHorario(hFin.value)) {
        e.preventDefault();
        alert('La hora debe estar dentro del horario de atención: 8:30 a.m.–12:30 p.m. o 3:30–6:30 p.m.');
        return false;
    }
});
</script>
</body>
</html>
