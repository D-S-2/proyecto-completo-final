<?php
session_start();
$sidebar_base = '';
$sidebar_carpeta = 'panel';
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=clinica_odontologica;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

/* ====== GUARDAR / EDITAR ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar') {
    header('Content-Type: application/json');

    $id_cita = $_POST['id_cita'];
    $diagnostico = trim($_POST['diagnostico']);
    $observaciones = trim($_POST['observaciones']);

    $existe = $pdo->prepare("SELECT id_atencion FROM atenciones WHERE id_cita=?");
    $existe->execute([$id_cita]);

    if ($existe->fetch()) {
        $pdo->prepare(
            "UPDATE atenciones 
             SET diagnostico=?, observaciones=? 
             WHERE id_cita=?"
        )->execute([$diagnostico, $observaciones, $id_cita]);
    } else {
        $pdo->prepare(
            "INSERT INTO atenciones (id_cita, diagnostico, observaciones, fecha_atencion)
             VALUES (?,?,?,NOW())"
        )->execute([$id_cita, $diagnostico, $observaciones]);

        $pdo->prepare(
            "UPDATE citas SET estado='ATENDIDA' WHERE id_cita=?"
        )->execute([$id_cita]);
    }

    echo json_encode(['ok'=>true]);
    exit;
}

/* ====== OBTENER ID_ODONTOLOGO DEL USUARIO LOGUEADO (si es DOCTOR) ====== */
$id_odontologo_filtro = null;
$rol = $_SESSION['rol'] ?? '';
if ($rol === 'DOCTOR') {
    $usuario = $_SESSION['usuario'] ?? '';
    if (!empty($usuario)) {
        $stmt = $pdo->prepare("
            SELECT o.id_odontologo 
            FROM odontologos o
            INNER JOIN usuarios u ON o.id_usuario = u.id_usuario
            WHERE u.usuario = ? AND u.activo = 1
            LIMIT 1
        ");
        $stmt->execute([$usuario]);
        $row = $stmt->fetch();
        if ($row) {
            $id_odontologo_filtro = (int)$row['id_odontologo'];
        }
    }
}

/* ====== CITAS ====== */
$sqlNuevas = "
    SELECT c.id_cita,
           CONCAT(p.nombres,' ',p.apellido_paterno,' ',IFNULL(p.apellido_materno,'')) paciente,
           p.ci,
           DATE_FORMAT(c.fecha_hora_inicio,'%d/%m/%Y') fecha,
           DATE_FORMAT(c.fecha_hora_inicio,'%H:%i') hora,
           c.motivo
    FROM citas c
    JOIN pacientes p ON p.id_paciente=c.id_paciente
    WHERE c.estado='PROGRAMADA'";
if ($id_odontologo_filtro !== null) {
    $sqlNuevas .= " AND c.id_odontologo = " . (int)$id_odontologo_filtro;
}
$sqlNuevas .= " ORDER BY c.fecha_hora_inicio ASC";
$citasNuevas = $pdo->query($sqlNuevas)->fetchAll();

$sqlEditables = "
    SELECT c.id_cita,
           CONCAT(p.nombres,' ',p.apellido_paterno) paciente,
           DATE_FORMAT(c.fecha_hora_inicio,'%d/%m/%Y %H:%i') fecha,
           a.diagnostico,
           a.observaciones
    FROM atenciones a
    JOIN citas c ON c.id_cita=a.id_cita
    JOIN pacientes p ON p.id_paciente=c.id_paciente";
if ($id_odontologo_filtro !== null) {
    $sqlEditables .= " WHERE c.id_odontologo = " . (int)$id_odontologo_filtro;
}
$sqlEditables .= " ORDER BY c.fecha_hora_inicio DESC";
$citasEditables = $pdo->query($sqlEditables)->fetchAll();

$map = [];
foreach ($citasEditables as $c) {
    $map[$c['id_cita']] = $c;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atención Odontológica | Dr. Muelitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #006d77;
            --primary-light: #83c5be;
            --primary-dark: #004445;
            --accent: #e0f2f1;
            --success: #2a9d8f;
        }
        body { font-family: 'Inter', sans-serif; background: #f0f4f8; }
        .page-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid var(--primary);
        }
        .page-header h1 {
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
        }
        .card-atencion {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-atencion:hover { box-shadow: 0 8px 28px rgba(0,109,119,0.12); }
        .card-atencion .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            font-weight: 600;
            padding: 1rem 1.25rem;
            border: none;
        }
        .card-atencion .card-body { padding: 1.5rem; }
        .form-label { font-weight: 600; color: #374151; }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 0.6rem 0.9rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(0,109,119,0.15);
        }
        textarea.form-control { min-height: 100px; resize: vertical; }
        .btn-guardar {
            background: linear-gradient(135deg, var(--success) 0%, var(--primary-dark) 100%);
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-guardar:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(42,157,143,0.4);
        }
        .lista-citas {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .lista-citas .cita-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            margin-bottom: 0.5rem;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .lista-citas .cita-item:hover {
            background: var(--accent);
            border-color: var(--primary-light);
            transform: translateX(4px);
        }
        .lista-citas .cita-item.seleccionada {
            background: var(--accent);
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(0,109,119,0.2);
        }
        .cita-item .cita-info { flex: 1; }
        .cita-item .cita-paciente { font-weight: 600; color: #111; }
        .cita-item .cita-fecha { font-size: 0.85rem; color: #6b7280; }
        .cita-item .cita-motivo { font-size: 0.8rem; color: #9ca3af; margin-top: 2px; }
        .cita-item .cita-badge {
            background: var(--primary);
            color: #fff;
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
            font-weight: 600;
        }
        .lista-vacia {
            text-align: center;
            padding: 2rem;
            color: #9ca3af;
            background: #f9fafb;
            border-radius: 12px;
            border: 1px dashed #e5e7eb;
        }
        .lista-vacia i { font-size: 2.5rem; margin-bottom: 0.5rem; opacity: 0.6; }
        .titulo-lista {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .titulo-lista .badge { font-size: 0.75rem; }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/sidebar.php'; ?>
<div class="dashboard-main">
    <div class="container-fluid py-4 px-4">
        <div class="page-header">
            <h1><i class="bi bi-heart-pulse-fill me-2"></i>Atención Odontológica</h1>
            <p class="text-muted mb-0 small">Registre diagnósticos y observaciones de las citas atendidas</p>
        </div>

        <div class="row g-4">
            <!-- Nueva atención + Lista de citas pendientes -->
            <div class="col-lg-6">
                <div class="card card-atencion h-100">
                    <div class="card-header">
                        <i class="bi bi-plus-circle me-2"></i> Nueva atención
                    </div>
                    <div class="card-body">
                        <label class="form-label">Seleccionar cita para atender</label>
                        <select id="citaNueva" class="form-select mb-4">
                            <option value="">-- Seleccione una cita --</option>
                            <?php foreach ($citasNuevas as $c): ?>
                                <option value="<?= (int)$c['id_cita'] ?>" data-paciente="<?= htmlspecialchars($c['paciente']) ?>" data-fecha="<?= htmlspecialchars($c['fecha']) ?> <?= htmlspecialchars($c['hora']) ?>">
                                    #<?= $c['id_cita'] ?> - <?= htmlspecialchars($c['paciente']) ?> - <?= $c['fecha'] ?> <?= $c['hora'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <p class="titulo-lista">
                            <i class="bi bi-calendar-check"></i> Citas pendientes de atención
                            <span class="badge bg-primary ms-2"><?= count($citasNuevas) ?></span>
                        </p>
                        <?php if (empty($citasNuevas)): ?>
                            <div class="lista-vacia">
                                <i class="bi bi-calendar-x"></i>
                                <div>No hay citas nuevas sin atender</div>
                            </div>
                        <?php else: ?>
                            <ul class="lista-citas" id="listaCitasNuevas">
                                <?php foreach ($citasNuevas as $c): ?>
                                    <li class="cita-item" data-id="<?= (int)$c['id_cita'] ?>" role="button">
                                        <div class="cita-info">
                                            <div class="cita-paciente"><?= htmlspecialchars($c['paciente']) ?></div>
                                            <div class="cita-fecha"><i class="bi bi-clock me-1"></i><?= $c['fecha'] ?> · <?= $c['hora'] ?></div>
                                            <?php if (!empty($c['motivo'])): ?>
                                                <div class="cita-motivo"><?= htmlspecialchars(mb_substr($c['motivo'], 0, 50)) ?><?= mb_strlen($c['motivo']) > 50 ? '…' : '' ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="cita-badge">#<?= $c['id_cita'] ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Editar atención ya registrada (solo ADMIN) -->
            <div class="col-lg-6">
                <?php if (($_SESSION['rol'] ?? '') === 'ADMIN'): ?>
                <div class="card card-atencion h-100">
                    <div class="card-header">
                        <i class="bi bi-pencil-square me-2"></i> Editar atención ya registrada
                    </div>
                    <div class="card-body">
                        <label class="form-label">Seleccionar cita atendida</label>
                        <select id="citaEditar" class="form-select">
                            <option value="">-- Seleccione --</option>
                            <?php foreach ($citasEditables as $c): ?>
                                <option value="<?= (int)$c['id_cita'] ?>">
                                    #<?= $c['id_cita'] ?> - <?= htmlspecialchars($c['paciente']) ?> - <?= $c['fecha'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Diagnóstico y observaciones -->
                <div class="card card-atencion mt-4">
                    <div class="card-header">
                        <i class="bi bi-file-medical me-2"></i> Diagnóstico y observaciones
                    </div>
                    <div class="card-body">
                        <label class="form-label">Diagnóstico</label>
                        <textarea id="diagnostico" class="form-control mb-3" placeholder="Describa el diagnóstico..."></textarea>
                        <label class="form-label">Observaciones</label>
                        <textarea id="observaciones" class="form-control mb-4" placeholder="Notas adicionales (opcional)..."></textarea>
                        <button type="button" id="guardarBtn" class="btn btn-guardar">
                            <i class="bi bi-check-lg me-2"></i>Guardar atención
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const atenciones = <?= json_encode($map) ?>;
const esAdmin = <?= json_encode(($_SESSION['rol'] ?? '') === 'ADMIN') ?>;

const citaNueva = document.getElementById('citaNueva');
const citaEditar = document.getElementById('citaEditar');
const diagnostico = document.getElementById('diagnostico');
const observaciones = document.getElementById('observaciones');

function limpiarTodo() {
    citaNueva.value = '';
    if (citaEditar) citaEditar.value = '';
    diagnostico.value = '';
    observaciones.value = '';
    document.querySelectorAll('.lista-citas .cita-item.seleccionada').forEach(el => el.classList.remove('seleccionada'));
}

citaNueva.addEventListener('change', () => {
    if (citaNueva.value) {
        if (citaEditar) citaEditar.value = '';
        diagnostico.value = '';
        observaciones.value = '';
        document.querySelectorAll('.lista-citas .cita-item.seleccionada').forEach(el => el.classList.remove('seleccionada'));
        const item = document.querySelector('.lista-citas .cita-item[data-id="' + citaNueva.value + '"]');
        if (item) item.classList.add('seleccionada');
    }
});

if (citaEditar) {
    citaEditar.addEventListener('change', () => {
        diagnostico.value = '';
        observaciones.value = '';
        citaNueva.value = '';
        document.querySelectorAll('.lista-citas .cita-item.seleccionada').forEach(el => el.classList.remove('seleccionada'));
        const id = citaEditar.value;
        if (atenciones[id]) {
            diagnostico.value = atenciones[id].diagnostico || '';
            observaciones.value = atenciones[id].observaciones || '';
        }
    });
}

document.querySelectorAll('.lista-citas .cita-item').forEach(item => {
    item.addEventListener('click', () => {
        const id = item.getAttribute('data-id');
        if (!id) return;
        citaEditar.value = '';
        citaNueva.value = id;
        diagnostico.value = '';
        observaciones.value = '';
        document.querySelectorAll('.lista-citas .cita-item.seleccionada').forEach(el => el.classList.remove('seleccionada'));
        item.classList.add('seleccionada');
    });
});

document.getElementById('guardarBtn').addEventListener('click', () => {
    const id = (citaEditar && citaEditar.value) || citaNueva.value;
    if (!id || !diagnostico.value.trim()) {
        alert('Seleccione una cita y escriba el diagnóstico.');
        return;
    }
    const f = new FormData();
    f.append('accion', 'guardar');
    f.append('id_cita', id);
    f.append('diagnostico', diagnostico.value);
    f.append('observaciones', observaciones.value);
    fetch('', { method: 'POST', body: f })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                limpiarTodo();
                alert('Atención guardada correctamente.');
                window.location.reload();
            }
        });
});
</script>
</body>
</html>
