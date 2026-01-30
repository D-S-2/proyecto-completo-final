<?php
// Iniciar sesión al principio del script
session_start();

// Configuración de conexión a la base de datos
$servidor = "localhost";
$usuario = "root";
$contraseña = "";
$baseDeDatos = "clinica_odontologica";

// Establecer conexión
$conexion = mysqli_connect($servidor, $usuario, $contraseña, $baseDeDatos);

// Verificar conexión
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Inicializar variables
$error = '';

// Procesar formulario cuando se envía
if (isset($_POST['ingresar'])) {
    // Validar y sanitizar datos
    $nombreUsuario = trim($_POST['nombre'] ?? '');
    $password = $_POST['contraseña'] ?? '';

    // Validaciones básicas
    if (empty($nombreUsuario) || empty($password)) {
        $error = "Por favor, completa todos los campos";
    } else {
        // Usar sentencias preparadas para prevenir SQL injection
        $consulta = "SELECT * FROM vista_login WHERE usuario = ? LIMIT 1";
        $stmt = mysqli_prepare($conexion, $consulta);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $nombreUsuario);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);
            if ($fila = mysqli_fetch_assoc($resultado)) {
                // Verificar contraseña
                if ($password === $fila['password_hash']) {
                    // Inicio de sesión exitoso
                    $_SESSION['usuario'] = $fila['usuario'];
                    $_SESSION['NombreUsuario'] = $fila['NombreUsuario'];
                    $_SESSION['rol'] = $fila['rol'];
                    $_SESSION['autenticado'] = true;
                    header("Location: Inicio.php");
                    exit();
                } else {
                    $error = "Nombre o contraseña incorrectos";
                }
            } else {
                $error = "Nombre o contraseña incorrectos";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Error en la consulta";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Dr. Muelitas</title>
    <link rel="stylesheet" href="proyecto.css">
    <style>
        /* Estilos adicionales específicos para el formulario */
        body.formulario {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e6f7f7 0%, #f0f9f9 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .volver {
            position: absolute;
            top: 20px;
            left: 20px;
            text-decoration: none;
            color: #2c7873;
            font-weight: 600;
            padding: 10px 20px;
            border: 2px solid #2c7873;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .volver:hover {
            background-color: #2c7873;
            color: white;
        }
        .form {
            background-color: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }
        .form h1 {
            color: #2c7873;
            margin-bottom: 30px;
            font-size: 1.8rem;
            border-bottom: 3px solid #a3de83;
            padding-bottom: 15px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        label {
            text-align: left;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="password"] {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: #2c7873;
            outline: none;
        }
        .botones {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        button[name="ingresar"] {
            background-color: #2c7873;
            color: white;
        }
        button[name="ingresar"]:hover {
            background-color: #1f5c57;
            transform: translateY(-2px);
        }
        button[type="button"] {
            background-color: #f0f0f0;
            color: #333;
        }
        button[type="button"]:hover {
            background-color: #e0e0e0;
        }
        .error {
            color: #d9534f;
            background-color: #fdf7f7;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 0.9rem;
        }
        @media (max-width: 480px) {
            .form {
                padding: 30px 20px;
            }
            .botones {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="formulario">
    <a href="Index.php" class="volver">← Inicio</a>
    <div class="form">
        <h1>Bienvenido al Dr. Muelitas</h1>
        <!-- Mostrar mensaje de error si existe -->
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form action="" method="post">
            <div>
                <label for="nombre">Nombre de usuario</label>
                <input type="text" name="nombre" id="nombre" placeholder="Ingresa tu nombre" value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required>
            </div>
            <div>
                <label for="contraseña">Contraseña</label>
                <input type="password" name="contraseña" id="contraseña" placeholder="Ingresa tu contraseña" required>
            </div>
            <div class="botones">
                <button type="submit" name="ingresar">Ingresar</button>
                <button type="button" onclick="window.location.href='Index.php'">Cancelar</button>
            </div>
        </form>
    </div>
    <script>
        // Validación del lado del cliente
        document.querySelector('form').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const password = document.getElementById('contraseña').value;
            if (!nombre || !password) {
                e.preventDefault();
                alert('Por favor, completa todos los campos');
                return false;
            }
            if (nombre.length < 3) {
                e.preventDefault();
                alert('El nombre debe tener al menos 3 caracteres');
                return false;
            }
            return true;
        });

        // Limpiar mensaje de error al empezar a escribir
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                const errorDiv = document.querySelector('.error');
                if (errorDiv) {
                    errorDiv.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>