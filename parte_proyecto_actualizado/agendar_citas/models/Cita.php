<?php
require_once __DIR__ . '/../config/db.php';

class Cita {
    private $conn;
    private $table = "citas";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function listarParaCalendario($id_odontologo = null) {
        $sql = "SELECT 
                    c.id_cita, 
                    c.fecha_hora_inicio as start, 
                    c.fecha_hora_fin as end, 
                    CONCAT(p.nombres, ' ', p.apellido_paterno) as title,
                    c.estado,
                    c.motivo
                FROM citas c
                INNER JOIN pacientes p ON c.id_paciente = p.id_paciente";
        if ($id_odontologo) { $sql .= " WHERE c.id_odontologo = :odo"; }
        $sql .= " ORDER BY c.fecha_hora_inicio ASC";
        $stmt = $this->conn->prepare($sql);
        if ($id_odontologo) { $stmt->bindParam(":odo", $id_odontologo, PDO::PARAM_INT); }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT c.*, p.ci, p.nombres as nombre_paciente, p.apellido_paterno
                  FROM " . $this->table . " c
                  INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                  WHERE c.id_cita = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['nombre_paciente'])) {
            $row['departamento'] = isset($row['departamento']) ? $row['departamento'] : '';
        }
        return $row;
    }

    public function verificarDisponibilidad($id_odontologo, $id_paciente, $inicio, $fin, $id_cita_excluir = null) {
        $timestamp = strtotime($inicio);
        $diaSemana = date('w', $timestamp);
        $horaCita = date('H:i', $timestamp);

        if ($diaSemana == 0) return false;

        $esMañana = ($horaCita >= '08:30' && $horaCita < '12:30');
        $esTarde = ($horaCita >= '15:30' && $horaCita < '18:30');
        if (!$esMañana && !$esTarde) return false;

        $sql = "SELECT COUNT(*) as total FROM " . $this->table . " 
            WHERE estado NOT IN ('CANCELADA') 
            AND (
                (id_odontologo = :odo AND (fecha_hora_inicio < :fin AND fecha_hora_fin > :inicio))
                OR 
                (id_paciente = :pac AND (fecha_hora_inicio < :fin AND fecha_hora_fin > :inicio))
            )";
        if ($id_cita_excluir) { $sql .= " AND id_cita != :exc"; }

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":odo", $id_odontologo, PDO::PARAM_INT);
        $stmt->bindParam(":pac", $id_paciente, PDO::PARAM_INT);
        $stmt->bindParam(":inicio", $inicio);
        $stmt->bindParam(":fin", $fin);
        if ($id_cita_excluir) { $stmt->bindParam(":exc", $id_cita_excluir, PDO::PARAM_INT); }
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($resultado['total'] == 0);
    }

    public function crear($datos) {
        $query = "INSERT INTO " . $this->table . " 
                  (id_paciente, id_odontologo, fecha_hora_inicio, fecha_hora_fin, motivo, estado, creada_por) 
                  VALUES (:pac, :odoc, :inicio, :fin, :motivo, 'PROGRAMADA', :creador)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":pac", $datos['id_paciente'], PDO::PARAM_INT);
        $stmt->bindParam(":odoc", $datos['id_odontologo'], PDO::PARAM_INT);
        $stmt->bindParam(":inicio", $datos['inicio']);
        $stmt->bindParam(":fin", $datos['fin']);
        $stmt->bindParam(":motivo", $datos['motivo']);
        $uid = $datos['id_usuario'] ?? null;
        $stmt->bindValue(":creador", $uid, $uid === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function actualizar($datos) {
        $citaActual = $this->obtenerPorId($datos['id_cita']);
        if (!$citaActual || $citaActual['estado'] != 'PROGRAMADA') return false;

        $query = "UPDATE " . $this->table . " SET 
                  id_paciente = :pac, id_odontologo = :odoc, 
                  fecha_hora_inicio = :inicio, fecha_hora_fin = :fin, motivo = :motivo
                  WHERE id_cita = :id AND estado = 'PROGRAMADA'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":pac", $datos['id_paciente'], PDO::PARAM_INT);
        $stmt->bindParam(":odoc", $datos['id_odontologo'], PDO::PARAM_INT);
        $stmt->bindParam(":inicio", $datos['inicio']);
        $stmt->bindParam(":fin", $datos['fin']);
        $stmt->bindParam(":motivo", $datos['motivo']);
        $stmt->bindParam(":id", $datos['id_cita'], PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function mover($id_cita, $inicio, $fin) {
        $query = "UPDATE " . $this->table . " SET fecha_hora_inicio = :inicio, fecha_hora_fin = :fin 
                  WHERE id_cita = :id AND estado = 'PROGRAMADA'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":inicio", $inicio);
        $stmt->bindParam(":fin", $fin);
        $stmt->bindParam(":id", $id_cita, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function cancelar($id_cita, $id_usuario = null) {
        $query = "UPDATE " . $this->table . " SET estado = 'CANCELADA'";
        if ($id_usuario) { $query .= ", creada_por = :usuario"; }
        $query .= " WHERE id_cita = :id AND estado = 'PROGRAMADA'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id_cita, PDO::PARAM_INT);
        if ($id_usuario) { $stmt->bindParam(":usuario", $id_usuario, PDO::PARAM_INT); }
        return $stmt->execute();
    }

    public function marcarNoAsistio($id_cita) {
        $query = "UPDATE " . $this->table . " SET estado = 'NO_ASISTIO' 
                  WHERE id_cita = :id AND estado = 'PROGRAMADA'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id_cita, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function marcarAtendida($id_cita) {
        $query = "UPDATE " . $this->table . " SET estado = 'ATENDIDA' 
                  WHERE id_cita = :id AND estado = 'PROGRAMADA'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id_cita, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function obtenerCitasDelDia($fecha, $id_odontologo = null) {
        $sql = "SELECT c.id_cita, c.fecha_hora_inicio, c.fecha_hora_fin, c.estado, c.motivo,
                CONCAT(p.nombres, ' ', p.apellido_paterno) as paciente, p.ci, p.telefono,
                CONCAT(u.nombres, ' ', u.apellidos) as odontologo
                FROM citas c
                INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                INNER JOIN odontologos o ON c.id_odontologo = o.id_odontologo
                INNER JOIN usuarios u ON o.id_usuario = u.id_usuario
                WHERE DATE(c.fecha_hora_inicio) = :fecha";
        if ($id_odontologo) { $sql .= " AND c.id_odontologo = :odo"; }
        $sql .= " ORDER BY c.fecha_hora_inicio ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":fecha", $fecha);
        if ($id_odontologo) { $stmt->bindParam(":odo", $id_odontologo, PDO::PARAM_INT); }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) { $r['departamento'] = isset($r['departamento']) ? $r['departamento'] : ''; }
        return $rows;
    }
}
