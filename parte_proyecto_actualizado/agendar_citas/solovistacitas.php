<?php
session_start();
require_once("db_config.php");

$sql = "SELECT * FROM vw_agenda ORDER BY fecha_hora_inicio";
$citas = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Citas Programadas</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
<?php $sidebar_base = '../'; $sidebar_carpeta = 'agendar_citas'; require_once __DIR__ . '/../panel/sidebar.php'; ?>
<div class="dashboard-main">
<h2>📅 Citas Programadas</h2>
<table>
    <tr>
        <th>Paciente</th>
        <th>CI</th>
        <th>Odontólogo</th>
        <th>Fecha y Hora</th>
        <th>Estado</th>
        <th>Motivo</th>
    </tr>

    <?php foreach ($citas as $c) { ?>
    <tr>
        <td><?= $c['paciente'] ?></td>
        <td><?= $c['ci_paciente'] ?></td>
        <td><?= $c['odontologo'] ?></td>
        <td><?= $c['fecha_hora_inicio'] ?></td>
        <td><?= $c['estado'] ?></td>
        <td><?= $c['motivo'] ?></td>
    </tr>
    <?php } ?>
</table>
</div>

</body>
</html>
