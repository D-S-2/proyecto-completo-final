<?php
session_start();
$sidebar_base = '../../';
$sidebar_carpeta = 'agendar_citas';

require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Odontologo.php';

$pacientes = (new Paciente())->listarTodos();
$doctores = (new Odontologo())->listarTodos();
$fecha_predeterminada = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$hora_inicio = isset($_GET['hora']) ? $_GET['hora'] : '08:30';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Cita - Dr. Muelitas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/citas.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .clinical-form { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); border-top: 6px solid #3498db; }
        .section-title { color: #2c3e50; border-bottom: 2px solid #f0f2f5; padding-bottom: 10px; margin-bottom: 25px; font-size: 1.1rem; text-transform: uppercase; }
        .time-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; }
        .category-label { font-size: 0.7rem; font-weight: 800; color: #95a5a6; margin-top: 12px; margin-bottom: 5px; text-transform: uppercase; }
        .treatment-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px; }
        .t-btn { background: #fff; border: 1px solid #dcdcdc; border-radius: 4px; padding: 8px 10px; cursor: pointer; text-align: left; display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: #2c3e50; }
        .t-btn:hover { background: #f1f8ff; border-color: #3498db; }
        .t-btn.active { background-color: #3498db; color: white; border-color: #2980b9; font-weight: 600; }
        .t-time-badge { font-size: 0.7rem; background: #eee; padding: 2px 6px; border-radius: 10px; color: #555; }
        .t-btn.active .t-time-badge { background: rgba(255,255,255,0.2); color: white; }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../../panel/sidebar.php'; ?>
<div class="dashboard-main">
    <div class="page-header">
        <h1><i class="fas fa-calendar-plus"></i> Agendar Cita</h1>
        <div class="d-flex align-items-center gap-3">
            <span id="hora-actual" class="badge bg-dark fs-6 px-3 py-2" style="font-family: monospace;">
                <i class="fas fa-clock me-1"></i> <span id="hora-actual-texto">--:--:--</span>
            </span>
            <small class="text-muted">Horario: 8:30 a.m.–12:30 p.m. / 3:30–6:30 p.m.</small>
            <a href="calendario.php" class="btn-primary" style="background-color: #7f8c8d; text-decoration: none;"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
    </div>

    <div style="max-width: 1100px; margin: 0 auto;">
        <form action="../controllers/citaController.php" method="POST" class="clinical-form" id="formCita">
            <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 40px;">
                <div>
                    <h3 class="section-title"><i class="fas fa-user-injured"></i> Datos</h3>
                    <div class="form-group">
                        <label>Seleccionar Paciente:</label>
                        <select name="id_paciente" class="form-control select2" required style="width: 100%;">
                            <option value="">Buscar paciente...</option>
                            <?php foreach ($pacientes as $p): ?>
                                <option value="<?php echo $p['id_paciente']; ?>">
                                    <?php echo htmlspecialchars(($p['ci'] ?? '') . ' - ' . $p['nombres'] . ' ' . $p['apellido_paterno']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-top: 20px;">
                        <label>Odontólogo:</label>
                        <select name="id_odontologo" class="form-control select2" required style="width: 100%;">
                            <?php foreach ($doctores as $d): ?>
                                <option value="<?php echo $d['id_odontologo']; ?>">Dr. <?php echo htmlspecialchars($d['nombres'] . ' ' . $d['apellidos']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px; border: 1px solid #eee;">
                        <div class="form-group">
                            <label>Fecha:</label>
                            <input type="date" name="fecha" id="fecha" required class="form-control" value="<?php echo $fecha_predeterminada; ?>" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="time-grid">
                            <div class="form-group">
                                <label>Inicio:</label>
                                <input type="time" name="hora_inicio" id="hora_inicio" required class="form-control" value="<?php echo $hora_inicio; ?>" step="300">
                            </div>
                            <div class="form-group">
                                <label>Fin (Auto):</label>
                                <input type="time" name="hora_fin" id="hora_fin" required class="form-control" value="" readonly style="background-color: #e9ecef;">
                            </div>
                        </div>
                        <small id="error-time" style="color: #e74c3c; display: none; margin-top: 10px; font-weight: bold;"></small>
                        <div id="alerta-horario-nueva" class="small fw-bold mt-2" style="display: none; color: #c0392b;"><i class="fas fa-exclamation-triangle me-1"></i> Fuera de horario</div>
                        <div class="form-group" style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px;">
                            <label>Motivo:</label>
                            <textarea name="motivo" id="motivo" class="form-control" rows="2" required placeholder="Seleccione un tratamiento..."></textarea>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="section-title"><i class="fas fa-list-ul"></i> Tratamientos</h3>
                    <div class="category-label">1. Diagnóstico y Urgencias</div>
                    <div class="treatment-grid">
                        <div class="t-btn" onclick="seleccionarTratamiento(this, 'Consulta / Valoración', 15)"><span>Consulta</span> <span class="t-time-badge">15m</span></div>
                        <div class="t-btn" onclick="seleccionarTratamiento(this, 'Urgencia / Dolor Agudo', 30)"><span>Urgencia</span> <span class="t-time-badge">30m</span></div>
                    </div>
                    <div class="category-label">2. Higiene y Estética</div>
                    <div class="treatment-grid">
                        <div class="t-btn" onclick="seleccionarTratamiento(this, 'Limpieza Dental (Profilaxis)', 30)"><span>Limpieza</span> <span class="t-time-badge">30m</span></div>
                        <div class="t-btn" onclick="seleccionarTratamiento(this, 'Blanqueamiento Dental', 60)"><span>Blanqueamiento</span> <span class="t-time-badge">60m</span></div>
                    </div>
                    <div class="category-label">3. Operatoria / Curaciones</div>
                    <div class="treatment-grid">
                        <div class="t-btn" onclick="seleccionarTratamiento(this, 'Curación Simple', 30)"><span>Curación Simple</span> <span class="t-time-badge">30m</span></div>
                        <div class="t-btn" onclick="seleccionarTratamiento(this, 'Curación Media', 45)"><span>Curación Media</span> <span class="t-time-badge">45m</span></div>
                        <div class="t-btn" onclick="seleccionarTratamiento(this, 'Curación Compleja', 60)"><span>Curación Comp.</span> <span class="t-time-badge">60m</span></div>
                    </div>
                    <div class="category-label">4. Cirugía / Extracciones</div>
                    <div class="treatment-grid">
                        <div class="t-btn" onclick="seleccionarTratamiento(this, 'Extracción de Incisivos', 30)"><span>Incisivos</span> <span class="t-time-badge">30m</span></div>
                        <div class="t-btn" onclick="seleccionarTratamiento(this, 'Extracción de Caninos', 45)"><span>Caninos</span> <span class="t-time-badge">45m</span></div>
                        <div class="t-btn" onclick="seleccionarTratamiento(this, 'Extracción de Premolares', 45)"><span>Premolares</span> <span class="t-time-badge">45m</span></div>
                        <div class="t-btn" onclick="seleccionarTratamiento(this, 'Extracción de Molares', 60)"><span>Molares</span> <span class="t-time-badge">60m</span></div>
                    </div>
                    <div class="category-label">5. Endodoncia</div>
                    <div class="treatment-grid">
                        <div class="t-btn" onclick="seleccionarTratamiento(this, 'Tratamiento de Conducto', 60)"><span>Trat. Conducto</span> <span class="t-time-badge">60m</span></div>
                    </div>
                    <div class="category-label">6. Ortodoncia</div>
                    <div class="treatment-grid">
                        <div class="t-btn" onclick="seleccionarTratamiento(this, 'Servicio de Brackets - Tipo 1', 20)"><span>Brackets T1</span> <span class="t-time-badge">20m</span></div>
                        <div class="t-btn" onclick="seleccionarTratamiento(this, 'Servicio de Brackets - Tipo 2', 20)"><span>Brackets T2</span> <span class="t-time-badge">20m</span></div>
                        <div class="t-btn" onclick="seleccionarTratamiento(this, 'Servicio de Brackets - Tipo 3', 20)"><span>Brackets T3</span> <span class="t-time-badge">20m</span></div>
                    </div>
                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn w-100 py-3" style="background-color: #1E6F78; color: white; border: none;">
                            <i class="fas fa-check"></i> AGENDAR CITA
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() { $('.select2').select2(); });
    var hInicio = document.getElementById('hora_inicio');
    var hFin = document.getElementById('hora_fin');
    var motivoTxt = document.getElementById('motivo');
    var errorMsg = document.getElementById('error-time');
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
        var alerta = document.getElementById('alerta-horario-nueva');
        if (fuera && (hInicio.value || hFin.value)) {
            if (alerta) { alerta.style.display = 'block'; }
            hInicio.style.borderColor = '#c0392b';
        } else {
            if (alerta) { alerta.style.display = 'none'; }
            hInicio.style.borderColor = '';
        }
    }

    function seleccionarTratamiento(elemento, nombreTratamiento, minutos) {
        document.querySelectorAll('.t-btn').forEach(function(el) { el.classList.remove('active'); });
        elemento.classList.add('active');
        minutosSeleccionados = minutos;
        motivoTxt.value = nombreTratamiento;
        calcularHoraFin();
    }
    function calcularHoraFin() {
        if (minutosSeleccionados === 0 || !hInicio.value) return;
        var fechaBase = new Date("2000-01-01T" + hInicio.value + ":00");
        fechaBase.setMinutes(fechaBase.getMinutes() + minutosSeleccionados);
        hFin.value = String(fechaBase.getHours()).padStart(2, '0') + ":" + String(fechaBase.getMinutes()).padStart(2, '0');
        validarHorarioMostrar();
    }
    hInicio.addEventListener('change', function() { calcularHoraFin(); validarHorarioMostrar(); });
    hFin.addEventListener('change', validarHorarioMostrar);
    setInterval(validarHorarioMostrar, 500);

    document.getElementById('formCita').addEventListener('submit', function(e) {
        if (!enRangoHorario(hInicio.value) || !enRangoHorario(hFin.value)) {
            e.preventDefault();
            alert('La hora debe estar dentro del horario de atención: 8:30 a.m.–12:30 p.m. o 3:30–6:30 p.m.');
            return false;
        }
    });
</script>
</body>
</html>
