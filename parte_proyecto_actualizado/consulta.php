<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta Médica</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1 class="titulo">Consulta Médica</h1>
    <form action="" method="post">
        <div class="fila">
            <div class="columna">
                <label for="idpaciente">ID Paciente:</label>
                <input type="text" id="idpaciente" name="idpaciente" placeholder="Ingrese ID Paciente">
            </div>
            <div class="columna">
                <label for="nombre">Nombre del Paciente:</label>
                <input type="text" id="nombre" name="nombre" placeholder="Ingrese nombre del paciente">
            </div>
            <div class="columna">
                <label for="idcita">ID Cita:</label>
                <input type="text" id="idcita" name="idcita" placeholder="Ingrese ID Cita">
            </div>
        </div>
        <div class="diagnostico">
            <div class="border">
                <label for="diagnostico">Diagnóstico</label>
                <textarea id="diagnostico" name="diagnostico"></textarea>
            </div>
        </div>
        <div class="observacion">
            <div class="border">
                <label for="observacion">Observación</label>
                <textarea id="observacion" name="observacion"></textarea>
            </div>
        </div>
        <button type="submit" name="guardar">Guardar</button>
    </form>
</body>
</html>

<?php
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "clinica_odontologica");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Verificar si se envió el formulario
if (isset($_POST['guardar'])) {
    // Obtener los datos del formulario
    $idcita = $_POST['idcita'];
    $diagnostico = $_POST['diagnostico'];
    $observacion = $_POST['observacion'];

    // Insertar los datos en la tabla atenciones
    $sql = "INSERT INTO atenciones (id_cita, diagnostico, observaciones) VALUES ('$idcita', '$diagnostico', '$observacion')";
    $resultado = $conexion->query($sql);

    if ($resultado) {
        $id_atencion = $conexion->insert_id;
        echo "Datos guardados correctamente. ID Atención: $id_atencion";
    } else {
        echo "Error: " . $conexion->error;
    }
}
?>
