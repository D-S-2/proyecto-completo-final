<?php
require_once __DIR__ . '/../config/db.php';

class Odontologo {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function listarTodos() {
        $query = "SELECT o.id_odontologo, o.especialidad, u.nombres, u.apellidos 
                  FROM odontologos o
                  INNER JOIN usuarios u ON o.id_usuario = u.id_usuario
                  WHERE u.activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener id_odontologo por usuario (login). Para rol DOCTOR.
     */
    public function getIdByUsuario($usuario) {
        if (empty($usuario)) return null;
        $query = "SELECT o.id_odontologo 
                  FROM odontologos o
                  INNER JOIN usuarios u ON o.id_usuario = u.id_usuario
                  WHERE u.usuario = ? AND u.activo = 1
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$usuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id_odontologo'] : null;
    }

    /**
     * Obtener datos del odontólogo por ID
     */
    public function obtenerPorId($id_odontologo) {
        $query = "SELECT o.id_odontologo, o.especialidad, u.nombres, u.apellidos, u.usuario
                  FROM odontologos o
                  INNER JOIN usuarios u ON o.id_usuario = u.id_usuario
                  WHERE o.id_odontologo = ? AND u.activo = 1
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id_odontologo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
