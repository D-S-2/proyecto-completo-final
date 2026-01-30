<?php
/**
 * BACKEND DE GESTIÓN DE PACIENTES - PROCLINIC
 */

ob_start(); 
header('Content-Type: application/json; charset=utf-8');

require 'db.php';

$accion = $_GET['accion'] ?? '';

try {
    // 1. LISTAR PACIENTES
    if ($accion === 'listar') {
        $sql = "SELECT * FROM pacientes ORDER BY id_paciente DESC";
        $stmt = $pdo->query($sql);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (ob_get_length()) ob_clean(); 
        echo json_encode($resultados, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. OBTENER UN SOLO PACIENTE
    if ($accion === 'obtener') {
        $id = intval($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE id_paciente = ?");
        $stmt->execute([$id]);
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$paciente) throw new Exception("Paciente no encontrado.");

        if (ob_get_length()) ob_clean();
        echo json_encode($paciente, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3. HISTORIAL CLÍNICO (NUEVO)
    if ($accion === 'historial') {
        $id = intval($_GET['id'] ?? 0);
        
        // Consulta: atenciones del paciente (aunque no tengan detalle_atencion/servicios)
        $sql = "SELECT 
                    a.id_atencion,
                    a.fecha_atencion, 
                    a.diagnostico, 
                    COALESCE(GROUP_CONCAT(DISTINCT s.nombre SEPARATOR ', '), 'Consulta') AS servicios,
                    COALESCE(vt.total_servicios, 0) AS total_servicios
                FROM atenciones a
                INNER JOIN citas c ON a.id_cita = c.id_cita
                LEFT JOIN vw_atencion_total vt ON a.id_atencion = vt.id_atencion
                LEFT JOIN detalle_atencion da ON a.id_atencion = da.id_atencion
                LEFT JOIN servicios s ON da.id_servicio = s.id_servicio
                WHERE c.id_paciente = ?
                GROUP BY a.id_atencion
                ORDER BY a.fecha_atencion DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (ob_get_length()) ob_clean();
        echo json_encode($historial, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 4. GUARDAR (CREAR O ACTUALIZAR)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $json = file_get_contents('php://input');
        $input = json_decode($json, true);

        if (!$input) throw new Exception('No se recibieron datos válidos.');

        // Extracción y limpieza
        $id_paciente  = $input['id_paciente'] ?? '';
        $ci           = trim($input['ci'] ?? '');
        $departamento = trim($input['departamento'] ?? '');
        $nombres      = mb_convert_case(trim($input['nombres'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $ap_paterno   = mb_convert_case(trim($input['apellido_paterno'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $ap_materno   = mb_convert_case(trim($input['apellido_materno'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $telefono     = trim($input['telefono'] ?? '');
        $fecha_nac    = $input['fecha_nacimiento'] ?? '';
        $sexo         = $input['sexo'] ?? '';
        $direccion    = trim($input['direccion'] ?? '');

        // --- VALIDACIONES DE SEGURIDAD ---

        // Validar CI (Mínimo 7 dígitos para compatibilidad)
        if (!preg_match("/^[0-9]{7,}$/", $ci)) {
            echo json_encode(['success' => false, 'message' => 'El CI debe tener al menos 7 dígitos.']);
            exit;
        }

        // Validar Nombres y Apellidos (Letras y espacios)
        $regexLetras = "/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/";
        if (!preg_match($regexLetras, $nombres) || !preg_match($regexLetras, $ap_paterno)) {
            echo json_encode(['success' => false, 'message' => 'Los nombres y apellidos solo deben contener letras.']);
            exit;
        }

        // Validar Teléfono (Bolivia: inicia con 6 o 7 y tiene 8 dígitos)
        if (!preg_match("/^[67][0-9]{7}$/", $telefono)) {
            echo json_encode(['success' => false, 'message' => 'Teléfono inválido (debe tener 8 dígitos y empezar con 6 o 7).']);
            exit;
        }

        // Validar Fecha (No futura)
        if (!empty($fecha_nac) && $fecha_nac > date('Y-m-d')) {
            echo json_encode(['success' => false, 'message' => 'La fecha de nacimiento no puede ser futura.']);
            exit;
        }

        $params = [$ci, $departamento, $nombres, $ap_paterno, $ap_materno, $fecha_nac ?: null, $sexo, $direccion, $telefono];

        if (empty($id_paciente)) {
            $sql = "INSERT INTO pacientes (ci, departamento, nombres, apellido_paterno, apellido_materno, fecha_nacimiento, sexo, direccion, telefono) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $msg = "Paciente registrado con éxito.";
        } else {
            $sql = "UPDATE pacientes SET ci=?, departamento=?, nombres=?, apellido_paterno=?, apellido_materno=?, fecha_nacimiento=?, sexo=?, direccion=?, telefono=? WHERE id_paciente=?";
            $params[] = $id_paciente;
            $msg = "Datos actualizados correctamente.";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => true, 'message' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    }

} catch (PDOException $e) {
    if (ob_get_length()) ob_clean();
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'Error: El CI ya se encuentra registrado.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error de Base de Datos.']);
    }
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

ob_end_flush();