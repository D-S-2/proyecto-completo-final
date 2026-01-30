<?php
session_start();
$sidebar_base = '../../';
$sidebar_carpeta = 'agendar_citas';

require_once __DIR__ . '/../models/Odontologo.php';
require_once __DIR__ . '/../models/Paciente.php';

$odoModel = new Odontologo();
$pacModel = new Paciente();
$doctores = $odoModel->listarTodos();
$pacientes = $pacModel->listarTodos();
$fecha_hoy = date('Y-m-d');

$rol = $_SESSION['rol'] ?? '';
$es_odontologo = ($rol === 'DOCTOR');
$id_odontologo_logueado = null;
if ($es_odontologo) {
    $id_odontologo_logueado = $odoModel->getIdByUsuario($_SESSION['usuario'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario de Citas - Dr. Muelitas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/citas.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .form-container-inline { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.08); margin-bottom: 30px; border-top: 6px solid #1E6F78; }
        .treatment-section { background: #fcfcfc; border: 1px solid #edf0f2; border-radius: 8px; padding: 15px; max-height: 400px; overflow-y: auto; }
        .category-header { font-size: 0.7rem; font-weight: 800; color: #7f8c8d; text-transform: uppercase; margin-bottom: 8px; border-bottom: 1px solid #eee; }
        .treatment-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 8px; margin-bottom: 15px; }
        .t-btn-mini { background: #fff; border: 1px solid #dce1e5; border-radius: 5px; padding: 8px 10px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: #34495e; }
        .t-btn-mini.active { background: #1E6F78; color: white; border-color: #1E6F78; }
        
        /* Botones del calendario FullCalendar */
        .fc-button-primary {
            background-color: #1E6F78 !important;
            border-color: #1E6F78 !important;
            color: white !important;
        }
        .fc-button-primary:hover {
            background-color: #1A5A63 !important;
            border-color: #1A5A63 !important;
        }
        .fc-button-primary:active, .fc-button-primary.fc-button-active {
            background-color: #155A62 !important;
            border-color: #155A62 !important;
        }
        .fc-today-button {
            background-color: #1E6F78 !important;
            border-color: #1E6F78 !important;
            color: white !important;
        }
        .fc-today-button:hover {
            background-color: #1A5A63 !important;
            border-color: #1A5A63 !important;
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../../panel/sidebar.php'; ?>
<div class="dashboard-main">

<?php
$error_msg = '';
$success_msg = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'ocupado':
            $error_msg = 'No se puede agendar la cita: el odontólogo ya tiene otra cita en ese horario. Elija otro horario u otro odontólogo. Gracias.';
            break;
        case 'hora_invalida':
            $error_msg = 'La hora de fin debe ser posterior a la hora de inicio. Revise los horarios. Gracias.';
            break;
        case 'fecha_pasada':
            $error_msg = 'No es posible agendar citas en fechas u horas pasadas. Gracias.';
            break;
        case '1':
        default:
            $error_msg = 'Ocurrió un error al guardar la cita. Intente de nuevo. Gracias.'; 
            break;
    }
}
if (isset($_GET['ok'])) {
    switch ($_GET['ok']) {
        case 'cancelada':
            $success_msg = '✅ Cita cancelada exitosamente.';
            break;
        case 'marcada como no asistió':
            $success_msg = '✅ Cita marcada como "No Asistió".';
            break;
        case 'marcada como atendida':
            $success_msg = '✅ Cita marcada como "Atendida" - ¡Paciente asistió!';
            break;
        case 'creado':
            $success_msg = '✅ Cita agendada exitosamente.';
            break;
        case 'editado':
            $success_msg = '✅ Cita actualizada exitosamente.';
            break;
        default:
            $success_msg = '✅ Operación realizada exitosamente.';
            break;
    }
}
?>
<?php if ($error_msg): ?>
    <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3 shadow-sm" role="alert">
        <strong><i class="fas fa-exclamation-circle me-2"></i>Aviso:</strong> <?php echo htmlspecialchars($error_msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>
<?php if ($success_msg): ?>
    <div class="alert alert-success alert-dismissible fade show mx-3 mt-3 shadow-sm" role="alert">
        <strong><i class="fas fa-check-circle me-2"></i>Éxito:</strong> <?php echo htmlspecialchars($success_msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>

    <div class="page-header">
        <h1><i class="far fa-calendar-alt"></i> Panel de Gestión de Citas</h1>
        <div class="d-flex align-items-center gap-3">
            <span id="hora-actual" class="badge bg-dark fs-6 px-3 py-2" style="font-family: monospace;">
                <i class="fas fa-clock me-1"></i> <span id="hora-actual-texto">--:--:--</span>
            </span>
            <small class="text-muted">Horario: 8:30 a.m.–12:30 p.m. / 3:30–6:30 p.m.</small>
        </div>
    </div>

    <?php if (!$es_odontologo): ?>
    <div class="form-container-inline">
        <form action="../controllers/citaController.php" method="POST" id="formCita">
            <div style="display: grid; grid-template-columns: 1fr 1.8fr; gap: 30px;">
                <div>
                    <h4 style="margin-bottom: 15px; color: #1E6F78;"><i class="fas fa-user-edit"></i> Datos de la Cita</h4>
                    <div class="form-group">
                        <label>Paciente:</label>
                        <select name="id_paciente" class="form-control select2" required style="width: 100%;">
                            <option value="">Buscar paciente...</option>
                            <?php foreach ($pacientes as $p): ?>
                                <option value="<?php echo $p['id_paciente']; ?>">
                                    <?php echo htmlspecialchars(($p['ci'] ?? '') . ' - ' . $p['nombres'] . ' ' . $p['apellido_paterno']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Odontólogo:</label>
                        <select name="id_odontologo" id="form_id_odontologo" class="form-control select2" required style="width: 100%;">
                            <?php foreach ($doctores as $d): ?>
                                <option value="<?php echo $d['id_odontologo']; ?>">Dr. <?php echo htmlspecialchars($d['nombres'] . ' ' . $d['apellidos']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 15px; border: 1px solid #eee;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div class="form-group">
                                <label>Fecha:</label>
                                <input type="date" name="fecha" id="form_fecha" class="form-control" value="<?php echo $fecha_hoy; ?>" min="<?php echo $fecha_hoy; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Inicio:</label>
                                <input type="time" name="hora_inicio" id="form_inicio" class="form-control" value="08:30" required>
                            </div>
                        </div>
                        <div class="form-group" style="margin-top: 10px;">
                            <label>Fin estimado:</label>
                            <input type="time" name="hora_fin" id="form_fin" class="form-control" readonly style="background: #e9ecef;">
                        </div>
                        <div id="alerta-horario-calendario" class="small fw-bold mt-2" style="display: none; color: #c0392b;"><i class="fas fa-exclamation-triangle me-1"></i> Fuera de horario</div>
                        <div class="form-group" style="margin-top: 10px;">
                            <label>Motivo:</label>
                            <textarea name="motivo" id="form_motivo" class="form-control" rows="2" required readonly placeholder="Seleccione tratamiento..."></textarea>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 style="margin-bottom: 15px; color: #1E6F78;"><i class="fas fa-tooth"></i> Seleccione Tratamiento</h4>
                    <div class="treatment-section">
                        <div class="category-header">1. Diagnóstico y Urgencias</div>
                        <div class="treatment-grid">
                            <div class="t-btn-mini" onclick="setTratamiento(this, 'Consulta / Valoración', 15)"><span>Consulta</span><b>15m</b></div>
                            <div class="t-btn-mini" onclick="setTratamiento(this, 'Urgencia / Dolor Agudo', 30)"><span>Urgencia</span><b>30m</b></div>
                        </div>
                        <div class="category-header">2. Higiene y Estética</div>
                        <div class="treatment-grid">
                            <div class="t-btn-mini" onclick="setTratamiento(this, 'Limpieza Dental', 30)"><span>Limpieza</span><b>30m</b></div>
                            <div class="t-btn-mini" onclick="setTratamiento(this, 'Blanqueamiento', 60)"><span>Blanqueamiento</span><b>60m</b></div>
                        </div>
                        <div class="category-header">3. Operatoria</div>
                        <div class="treatment-grid">
                            <div class="t-btn-mini" onclick="setTratamiento(this, 'Curación Simple', 30)"><span>Simple</span><b>30m</b></div>
                            <div class="t-btn-mini" onclick="setTratamiento(this, 'Curación Media', 45)"><span>Media</span><b>45m</b></div>
                            <div class="t-btn-mini" onclick="setTratamiento(this, 'Curación Compleja', 60)"><span>Compleja</span><b>60m</b></div>
                        </div>
                        <div class="category-header">4. Extracciones</div>
                        <div class="treatment-grid">
                            <div class="t-btn-mini" onclick="setTratamiento(this, 'Extracción Incisivos', 30)"><span>Incisivos</span><b>30m</b></div>
                            <div class="t-btn-mini" onclick="setTratamiento(this, 'Extracción Molares', 60)"><span>Molares</span><b>60m</b></div>
                        </div>
                        <div class="category-header">5. Ortodoncia</div>
                        <div class="treatment-grid">
                            <div class="t-btn-mini" onclick="setTratamiento(this, 'Control Ortodoncia', 20)"><span>Control</span><b>20m</b></div>
                        </div>
                    </div>
                    <button type="submit" class="btn w-100 mt-3" style="background-color: #1E6F78; color: white; border: none; height: 45px;">
                        <i class="fas fa-calendar-plus"></i> AGENDAR CITA
                    </button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <?php if ($es_odontologo): ?>
                    <span class="text-muted"><i class="fas fa-user-md me-1"></i>Su agenda</span>
                <?php else: ?>
                    <label class="mb-0"><b>Filtro:</b></label>
                    <select id="filtroDoctor" class="form-control" style="width: 220px;" onchange="filtrarCalendario()">
                        <option value="">Todos los Doctores</option>
                        <?php foreach ($doctores as $d): ?>
                            <option value="<?php echo $d['id_odontologo']; ?>">Dr. <?php echo htmlspecialchars($d['nombres'] . ' ' . $d['apellidos']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <?php if (!$es_odontologo): ?>
            <a href="agenda_dia.php" class="btn-primary" style="background:#7f8c8d; text-decoration:none;"><i class="fas fa-list"></i> Lista Diaria</a>
            <?php endif; ?>
        </div>
        <div id="calendar"></div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js"></script>
<script>
    var calendar;
    let minutosSeleccionados = 0;
    var idOdontologoLogueado = <?php echo $id_odontologo_logueado !== null ? (int)$id_odontologo_logueado : 'null'; ?>;
    var esOdontologo = <?php echo $es_odontologo ? 'true' : 'false'; ?>;
    $(document).ready(function() {
        if (!esOdontologo) $('.select2').select2();
        initCalendar();
        if (!esOdontologo) validarHoraInput();
    });
    $('#form_fecha').on('change', function() { validarHoraInput(); calcularHoraFin(); });
    $('#form_inicio').on('change', function() {
        var fechaSel = $('#form_fecha').val();
        var horaSel = $(this).val();
        if (horaSel && fechaSel === '<?php echo $fecha_hoy; ?>') {
            var ahora = new Date();
            var hoyStr = ahora.getFullYear() + '-' + String(ahora.getMonth()+1).padStart(2,'0') + '-' + String(ahora.getDate()).padStart(2,'0');
            if (fechaSel === hoyStr) {
                var [h, m] = horaSel.split(':').map(Number);
                var minActual = ahora.getHours() * 60 + ahora.getMinutes();
                if (h * 60 + m < minActual) {
                    alert("⚠️ No puedes seleccionar una hora pasada.");
                    $(this).val(String(ahora.getHours()).padStart(2,'0') + ':' + String(ahora.getMinutes()).padStart(2,'0'));
                }
            }
        }
        calcularHoraFin();
    });
    function validarHoraInput() {
        var fechaInput = document.getElementById('form_fecha').value;
        var hoy = new Date().toISOString().split('T')[0];
        if (fechaInput === hoy) {
            var ahora = new Date();
            document.getElementById('form_inicio').min = String(ahora.getHours()).padStart(2,'0') + ':' + String(ahora.getMinutes()).padStart(2,'0');
        } else {
            document.getElementById('form_inicio').removeAttribute('min');
        }
    }
    function setTratamiento(elemento, nombre, minutos) {
        $('.t-btn-mini').removeClass('active');
        $(elemento).addClass('active');
        minutosSeleccionados = minutos;
        $('#form_motivo').val(nombre);
        calcularHoraFin();
    }
    function calcularHoraFin() {
        var hInicio = $('#form_inicio').val();
        if (minutosSeleccionados === 0 || !hInicio) return;
        var d = new Date("2000-01-01T" + hInicio + ":00");
        d.setMinutes(d.getMinutes() + minutosSeleccionados);
        $('#form_fin').val(String(d.getHours()).padStart(2,'0') + ":" + String(d.getMinutes()).padStart(2,'0'));
        if (typeof validarHorarioMostrar === 'function') validarHorarioMostrar();
    }
    function initCalendar() {
        var calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            slotMinTime: '08:00:00',
            slotMaxTime: '19:00:00',
            allDaySlot: false,
            hiddenDays: [0],
            height: 650,
            slotEventOverlap: false,
            editable: !esOdontologo,
            scrollTime: '<?php echo date("H:i:s"); ?>',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
            events: function(info, success, failure) {
                var doctorId = esOdontologo ? (idOdontologoLogueado || '') : ($('#filtroDoctor').val() || '');
                fetch('../controllers/citaController.php?accion=listar&id_odontologo=' + doctorId)
                    .then(function(r) { return r.json(); })
                    .then(function(data) { success(data); });
            },
            dateClick: function(info) {
                if (esOdontologo) return;
                var clickDateTime = new Date(info.dateStr);
                var ahora = new Date();
                if (info.view.type === 'dayGridMonth') {
                    var clickSoloDia = new Date(info.dateStr + "T23:59:59");
                    if (clickSoloDia < ahora) { alert("⚠️ No puedes agendar en días pasados."); return; }
                } else {
                    if (clickDateTime < ahora) { alert("⚠️ Esa hora ya pasó."); return; }
                }
                var fecha = info.dateStr.split('T')[0];
                var hora = info.dateStr.split('T')[1] ? info.dateStr.split('T')[1].substring(0, 5) : "08:30";
                $('#form_fecha').val(fecha);
                $('#form_inicio').val(hora);
                validarHoraInput();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                calcularHoraFin();
            },
            eventClick: function(info) {
                if (esOdontologo) {
                    window.location.href = 'ver.php?id=' + info.event.id;
                } else {
                    window.location.href = 'editar.php?id=' + info.event.id;
                }
            }
        });
        calendar.render();
    }
    function filtrarCalendario() {
        if (esOdontologo) return;
        calendar.refetchEvents();
        var val = $('#filtroDoctor').val();
        if (val) $('#form_id_odontologo').val(val).trigger('change');
    }

    // Hora actual de la máquina (se actualiza cada segundo)
    function actualizarHora() {
        var now = new Date();
        var h = String(now.getHours()).padStart(2, '0');
        var m = String(now.getMinutes()).padStart(2, '0');
        var s = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('hora-actual-texto').textContent = h + ':' + m + ':' + s;
    }
    actualizarHora();
    setInterval(actualizarHora, 1000);

    // Horario: 8:30–12:30 y 15:30–18:30 (en minutos desde medianoche)
    function enRangoHorario(horaStr) {
        if (!horaStr) return true;
        var parts = horaStr.split(':');
        var min = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
        return (min >= 510 && min <= 750) || (min >= 930 && min <= 1110);
    }
    function validarHorarioMostrar() {
        var hInicio = $('#form_inicio').val();
        var hFin = $('#form_fin').val();
        var fuera = !enRangoHorario(hInicio) || !enRangoHorario(hFin);
        var alerta = document.getElementById('alerta-horario-calendario');
        var inp = document.getElementById('form_inicio');
        if (fuera && (hInicio || hFin)) {
            alerta.style.display = 'block';
            inp.style.borderColor = '#c0392b';
        } else {
            alerta.style.display = 'none';
            inp.style.borderColor = '';
        }
    }
    $('#form_inicio').on('change', validarHorarioMostrar);
    $('#form_fin').on('change', validarHorarioMostrar);
    setInterval(validarHorarioMostrar, 500);
    $('#formCita').on('submit', function(e) {
        var hInicio = $('#form_inicio').val();
        var hFin = $('#form_fin').val();
        if (!enRangoHorario(hInicio) || !enRangoHorario(hFin)) {
            e.preventDefault();
            alert('La hora debe estar dentro del horario de atención: 8:30 a.m.–12:30 p.m. o 3:30–6:30 p.m.');
            return false;
        }
    });
</script>
</body>
</html>
