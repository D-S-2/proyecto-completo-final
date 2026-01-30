<?php
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
    die('Error de conexión: ' . $e->getMessage());
}

$sql = "
SELECT 
    a.id_atencion,
    c.id_cita,
    CONCAT(p.nombres,' ',p.apellido_paterno) AS paciente,
    a.diagnostico,
    a.observaciones,
    DATE_FORMAT(a.fecha_atencion,'%d/%m/%Y %H:%i') AS fecha_atencion
FROM atenciones a
JOIN citas c ON c.id_cita = a.id_cita
JOIN pacientes p ON p.id_paciente = c.id_paciente
ORDER BY a.fecha_atencion DESC
";

$atenciones = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Historial de Atenciones</title>
<style>
body{font-family:Arial;background:#f4f6f8;padding:20px}
h2{text-align:center}
table{width:100%;border-collapse:collapse;background:#fff}
th,td{padding:10px;border:1px solid #ccc;text-align:left}
th{background:#1976d2;color:#fff}
tr:nth-child(even){background:#f2f2f2}
.container{max-width:1000px;margin:auto}
</style>
</head>
<body>

<div class="container">
<h2>Historial de Atenciones</h2>

<a href="atencion_odontologia.php" style="float: right; margin-top: -40px; margin-right: 20px; text-decoration: none; color: #337ab7; font-size: 16px;">
    <i class="fa-solid fa-clock-rotate-left"></i>VOLVER</a>


<table>
<thead>
<tr>
<th>ID Atención</th>
<th>ID Cita</th>
<th>Paciente</th>
<th>Diagnóstico</th>
<th>Observaciones</th>
<th>Fecha Atención</th>
</tr>
</thead>
<tbody>
<?php if(count($atenciones) > 0): ?>
<?php foreach($atenciones as $a): ?>
<tr>
<td><?= $a['id_atencion'] ?></td>
<td><?= $a['id_cita'] ?></td>
<td><?= htmlspecialchars($a['paciente']) ?></td>
<td><?= nl2br(htmlspecialchars($a['diagnostico'])) ?></td>
<td><?= nl2br(htmlspecialchars($a['observaciones'])) ?></td>
<td><?= $a['fecha_atencion'] ?></td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="6">No hay atenciones registradas</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

</body>
</html>
