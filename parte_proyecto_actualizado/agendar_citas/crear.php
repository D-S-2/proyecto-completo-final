<?php
session_start();
require_once("db_config.php");

// Pacientes
$pacientes = $conexion->query("SELECT * FROM pacientes")->fetchAll();
// Odontólogos
$odontologos = $conexion->query("
    SELECT o.id_odontologo, CONCAT(u.nombres,' ',u.apellidos) AS nombre
    FROM odontologos o
    JOIN usuarios u ON u.id_usuario = o.id_usuario
")->fetchAll();

if ($_POST) {
    $sql = "INSERT INTO citas
            (id_paciente, id_odontologo, fecha_hora_inicio, motivo, creada_por)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        $_POST['paciente'],
        $_POST['odontologo'],
        $_POST['fecha_hora'],
        $_POST['motivo'],
        1 // ID del recepcionista (simulado)
    ]);

    header("Location:listar.php");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Nueva Cita</title>
    <link rel="stylesheet" href="../agendar_citas/estilos.css">
</head>
<body>

<h2>➕ Agendar Cita</h2>

<form method="POST">
    <label>Paciente</label>
    <select name="paciente" required>
        <?php foreach ($pacientes as $p) { ?>
        <option value="<?= $p['id_paciente'] ?>">
            <?= $p['nombres']." ".$p['apellido_paterno'] ?>
        </option>
        <?php } ?>
    </select>

    <label>Odontólogo</label>
    <select name="odontologo" required>
        <?php foreach ($odontologos as $o) { ?>
        <option value="<?= $o['id_odontologo'] ?>">
            <?= $o['nombre'] ?>
        </option>
        <?php } ?>
    </select>

    <label>Fecha y Hora</label>
    <input type="datetime-local" name="fecha_hora" required>

    <label>Motivo</label>
    <textarea name="motivo"></textarea>

    <button type="submit" style="background-color: #1E6F78; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Guardar</button>
</form>
</div>

</body>
</html>
