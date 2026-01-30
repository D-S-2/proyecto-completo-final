<?php
session_start();
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
body{font-family:Arial;background:#f4f6f8}
h2{text-align:center;margin-bottom:1.5rem}
.dashboard-main .container{
    max-width:1100px;
    margin:0 auto;
    background:#fff;
    padding:24px;
    border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    box-sizing:border-box
    
}
table{width:100%;border-collapse:collapse;background:#fff}
th,td{padding:12px 14px;border:1px solid #ddd;text-align:left}
th{background:#1E6F78;color:#fff;font-weight:600}   
tr:nth-child(even){background:#f8f9fa}
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
<?php require_once __DIR__ . '/sidebar.php'; ?>
<div class="dashboard-main">
<div class="container">
<h2>Historial de Atenciones</h2>


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
</div>

</body>
</html>
