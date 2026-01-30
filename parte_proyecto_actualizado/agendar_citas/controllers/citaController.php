<?php
session_start();
date_default_timezone_set('America/La_Paz');

require_once __DIR__ . '/../models/Cita.php';

$citaModel = new Cita();

// 1. API JSON (Calendario)
if (isset($_GET['accion']) && $_GET['accion'] == 'listar') {
    $id_filtro = null;
    if (($_SESSION['rol'] ?? '') === 'DOCTOR') {
        require_once __DIR__ . '/../models/Odontologo.php';
        $odoModel = new Odontologo();
        $id_filtro = $odoModel->getIdByUsuario($_SESSION['usuario'] ?? '');
        if ($id_filtro === null) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }
    }
    if ($id_filtro === null) {
        $id_filtro = isset($_GET['id_odontologo']) && $_GET['id_odontologo'] !== '' ? (int)$_GET['id_odontologo'] : null;
    }
    $citas = $citaModel->listarParaCalendario($id_filtro);
    $eventos = [];
    foreach ($citas as $cita) {
        $color = '#3498db';
        if ($cita['estado'] == 'ATENDIDA') $color = '#2ecc71';
        if ($cita['estado'] == 'CANCELADA') $color = '#e74c3c';
        if ($cita['estado'] == 'NO_ASISTIO') $color = '#95a5a6';
        $eventos[] = [
            'id' => $cita['id_cita'],
            'title' => $cita['title'],
            'start' => $cita['start'],
            'end' => $cita['end'],
            'color' => $color,
            'extendedProps' => ['estado' => $cita['estado'], 'motivo' => $cita['motivo']]
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($eventos);
    exit;
}

// 2. Mover cita (Drag & Drop) — no permitido para DOCTOR
if (isset($_POST['accion']) && $_POST['accion'] == 'mover') {
    if (($_SESSION['rol'] ?? '') === 'DOCTOR') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'No tiene permiso para modificar citas.']);
        exit;
    }
    $id = (int)$_POST['id_cita'];
    $inicio = $_POST['start'];
    $fin = $_POST['end'];
    if (strtotime($inicio) < time()) {
        echo json_encode(['status' => 'error', 'message' => 'No puedes mover una cita al pasado.']);
        exit;
    }
    $citaOriginal = $citaModel->obtenerPorId($id);
    if (!$citaOriginal) {
        echo json_encode(['status' => 'error', 'message' => 'Cita no encontrada.']);
        exit;
    }
    if ($citaModel->verificarDisponibilidad($citaOriginal['id_odontologo'], $citaOriginal['id_paciente'], $inicio, $fin, $id)) {
        if ($citaModel->mover($id, $inicio, $fin)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Solo citas programadas pueden moverse.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Horario ocupado.']);
    }
    exit;
}

// 3. Cambiar estado (cancelar / no_asistio / atendida) — no permitido para DOCTOR
if (isset($_GET['accion']) && in_array($_GET['accion'], ['cancelar', 'no_asistio', 'atendida'])) {
    if (($_SESSION['rol'] ?? '') === 'DOCTOR') {
        header('Location: ../citas/calendario.php?error=1');
        exit;
    }
    $id_cita = (int)($_GET['id'] ?? 0);
    $resultado = false;
    $msg = 'editado';
    if ($_GET['accion'] == 'cancelar') {
        $resultado = $citaModel->cancelar($id_cita, $_SESSION['id_usuario'] ?? null);
        $msg = 'cancelada';
    } elseif ($_GET['accion'] == 'no_asistio') {
        $resultado = $citaModel->marcarNoAsistio($id_cita);
        $msg = 'marcada como no asistió';
    } elseif ($_GET['accion'] == 'atendida') {
        $resultado = $citaModel->marcarAtendida($id_cita);
        $msg = 'marcada como atendida';
    }
    $base = dirname(dirname($_SERVER['SCRIPT_NAME']));
    if ($resultado) {
        header("Location: ../citas/calendario.php?ok=" . $msg);
    } else {
        header("Location: ../citas/calendario.php?error=1");
    }
    exit;
}

// 4. Guardar / Editar cita (POST) — no permitido para DOCTOR
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['accion'])) {
    if (($_SESSION['rol'] ?? '') === 'DOCTOR') {
        header("Location: ../citas/calendario.php?error=1");
        exit;
    }
    $inicio = $_POST['fecha'] . ' ' . $_POST['hora_inicio'];
    $fin = $_POST['fecha'] . ' ' . $_POST['hora_fin'];

    if (strtotime($inicio) < time()) {
        header("Location: ../citas/calendario.php?error=fecha_pasada");
        exit;
    }

    $id_odontologo = (int)$_POST['id_odontologo'];
    $id_paciente = (int)$_POST['id_paciente'];
    $id_cita = isset($_POST['id_cita']) && $_POST['id_cita'] !== '' ? (int)$_POST['id_cita'] : null;

    if (strtotime($fin) <= strtotime($inicio)) {
        header("Location: ../citas/calendario.php?error=hora_invalida");
        exit;
    }

    if (!$citaModel->verificarDisponibilidad($id_odontologo, $id_paciente, $inicio, $fin, $id_cita)) {
        header("Location: ../citas/calendario.php?error=ocupado");
        exit;
    }

    $datos = [
        'id_paciente' => $id_paciente,
        'id_odontologo' => $id_odontologo,
        'inicio' => $inicio,
        'fin' => $fin,
        'motivo' => trim($_POST['motivo']),
        'id_usuario' => $_SESSION['id_usuario'] ?? null
    ];

    if ($id_cita) {
        $datos['id_cita'] = $id_cita;
        if ($citaModel->actualizar($datos)) {
            header("Location: ../citas/calendario.php?ok=editado");
        } else {
            header("Location: ../citas/calendario.php?error=1");
        }
    } else {
        if ($citaModel->crear($datos)) {
            header("Location: ../citas/calendario.php?ok=creado");
        } else {
            header("Location: ../citas/calendario.php?error=1");
        }
    }
    exit;
}
