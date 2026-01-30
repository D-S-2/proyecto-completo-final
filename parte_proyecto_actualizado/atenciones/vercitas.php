<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>vercitas</title>
</head>
<body style="";>
    <h1 > VER CITAS</h1>
    <?php
    $dsn = 'mysql:host=localhost;dbname=clinica_odontologica;charset=utf8mb4';
    $usuario = 'root';
    $contraseña = '';

    try {
        $conexion = new PDO($dsn, $usuario, $contraseña);
        $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo 'Error de conexión: ' . $e->getMessage();
        exit;
    }

    // Ejecuta la consulta SQL
    $resultado = $conexion->query("SELECT id_odontologo,fecha_hora_inicio,fecha_hora_fin,estado,motivo FROM citas");

    // Muestra los datos
    echo "<table id='tabla' border='1'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th><input type='text' id='id_odontologo' placeholder='Id Odontologo'></th>";
    echo "<th><input type='text' id='fecha_hora_inicio' placeholder='Fecha Atención'></th>";
    echo "<th><input type='text' id='fecha_hora_fin' placeholder='Fecha Fin'></th>";
    echo "<th><input type='text' id='estado' placeholder='Estado'></th>";
    echo "<th><input type='text' id='motivo' placeholder='Motivo'></th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody id='tabla-body'>";

    while ($fila = $resultado->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $fila["id_odontologo"] . "</td>";
        echo "<td>" . $fila["fecha_hora_inicio"] . "</td>";
        echo "<td>" . $fila["fecha_hora_fin"] . "</td>";
        echo "<td>" . $fila["estado"] . "</td>";
        echo "<td>" . $fila["motivo"] . "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";

    // Cierra la conexión
    $conexion = null;
    ?>

    <script>
        const inputs = document.querySelectorAll('input');
        const tabla = document.getElementById('tabla-body');

        inputs.forEach(input => {
            input.addEventListener('keyup', () => {
                const idOdontologo = document.getElementById('id_odontologo').value.toLowerCase();
                const fechaHoraInicio = document.getElementById('fecha_hora_inicio').value.toLowerCase();
                const fechaHoraFin = document.getElementById('fecha_hora_fin').value.toLowerCase();
                const estado = document.getElementById('estado').value.toLowerCase();
                const motivo = document.getElementById('motivo').value.toLowerCase();

                const filas = tabla.getElementsByTagName('tr');

                for (let i = 0; i < filas.length; i++) {
                    const idOdontologoTd = filas[i].getElementsByTagName('td')[0].textContent.toLowerCase();
                    const fechaHoraInicioTd = filas[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                    const fechaHoraFinTd = filas[i].getElementsByTagName('td')[2].textContent.toLowerCase();
                    const estadoTd = filas[i].getElementsByTagName('td')[3].textContent.toLowerCase();
                    const motivoTd = filas[i].getElementsByTagName('td')[4].textContent.toLowerCase();

                    if (idOdontologoTd.includes(idOdontologo) && fechaHoraInicioTd.includes(fechaHoraInicio) && fechaHoraFinTd.includes(fechaHoraFin) && estadoTd.includes(estado) && motivoTd.includes(motivo)) {
                        filas[i].style.display = '';
                    } else {
                        filas[i].style.display = 'none';
                    }
                }
            });
        });
    </script>
</body>
</html>
