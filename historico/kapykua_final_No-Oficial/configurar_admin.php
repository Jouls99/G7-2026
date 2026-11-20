<?php
session_start();
require_once 'conexion.php';

$err = '';
$codigoRecuperacion = '';
$creado = false;

if (empty($_SESSION['csrf_admin_setup'])) {
    $_SESSION['csrf_admin_setup'] = bin2hex(random_bytes(32));
}

$res = $conn->query("SELECT COUNT(*) AS total FROM usuario WHERE rol='ADMIN'");
$yaConfigurado = $res && (int)$res->fetch_assoc()['total'] > 0;

if ($yaConfigurado) {
    http_response_code(403);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_admin_setup'], $_POST['csrf'] ?? '')) {
        $err = 'La solicitud expiró. Actualizá la página e intentá nuevamente.';
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Completá el nombre y un correo válido.';
        } elseif (strlen($password) < 8) {
            $err = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($password !== $confirmar) {
            $err = 'Las contraseñas no coinciden.';
        } else {
            // Se vuelve a comprobar antes de crear para evitar un segundo ADMIN por acceso simultáneo.
            $check = $conn->query("SELECT COUNT(*) AS total FROM usuario WHERE rol='ADMIN'");
            $hayAdmin = $check && (int)$check->fetch_assoc()['total'] > 0;

            if ($hayAdmin) {
                $yaConfigurado = true;
                http_response_code(403);
            } else {
                $hashPassword = password_hash($password, PASSWORD_DEFAULT);
                $codigoRecuperacion = strtoupper(bin2hex(random_bytes(8)));
                $hashCodigo = hash('sha256', $codigoRecuperacion);

                $stmt = $conn->prepare("INSERT INTO usuario (nombre, telefono, email, passwordHash, rol, estado, tokenRecuperacion, tokenExpiracion) VALUES (?, ?, ?, ?, 'ADMIN', 1, ?, NULL)");
                $stmt->bind_param('sssss', $nombre, $telefono, $email, $hashPassword, $hashCodigo);

                if ($stmt->execute()) {
                    $creado = true;
                    // El código en texto plano solo se muestra en esta respuesta.
                    unset($_SESSION['csrf_admin_setup']);
                } else {
                    $err = ($stmt->errno === 1062)
                        ? 'Ese correo ya está registrado. Utilizá otro correo.'
                        : 'No se pudo crear el administrador. Revisá la base de datos.';
                }
                $stmt->close();
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración inicial - Kapykuá</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
<header class="auth-banner">Configuración inicial</header>
<main class="auth-card">
    <?php if ($yaConfigurado): ?>
        <h1>Configuración completada</h1>
        <p class="auth-intro">Ya existe un usuario ADMIN. La configuración inicial está bloqueada.</p>
        <p class="admin-actions"><a href="index.php">Volver al inicio de sesión</a></p>
    <?php elseif ($creado): ?>
        <h1>Administrador creado</h1>
        <p class="auth-intro">Guardá este código de recuperación en un lugar seguro. No volverá a mostrarse.</p>
        <div class="auth-error" style="background:#fff4d6;color:#503900;font-size:22px;font-weight:bold;letter-spacing:2px;text-align:center;overflow-wrap:anywhere;"><?php echo htmlspecialchars($codigoRecuperacion, ENT_QUOTES, 'UTF-8'); ?></div>
        <p>Este código permite establecer una nueva contraseña si olvidás la actual. Si lo perdés, esta versión de prueba no dispone de otro mecanismo de recuperación.</p>
        <p class="admin-actions"><a href="index.php">Ir al inicio de sesión</a></p>
    <?php else: ?>
        <h1>Crear administrador</h1>
        <p class="auth-intro">Esta instalación todavía no tiene un administrador. Creá la primera cuenta para comenzar.</p>
        <?php if ($err !== ''): ?><p class="auth-error"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf_admin_setup'], ENT_QUOTES, 'UTF-8'); ?>">
            <label>Nombre</label><input type="text" name="nombre" required maxlength="100">
            <label>Teléfono</label><input type="text" name="telefono" maxlength="30">
            <label>Correo electrónico</label><input type="email" name="email" required maxlength="150" autocomplete="email">
            <label>Contraseña (mínimo 8 caracteres)</label><input type="password" name="password" required minlength="8" autocomplete="new-password">
            <label>Confirmar contraseña</label><input type="password" name="confirmar" required minlength="8" autocomplete="new-password">
            <button class="auth-submit">Crear ADMIN</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
