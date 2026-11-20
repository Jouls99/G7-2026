<?php
session_start();
require_once 'conexion.php';

$error = '';
$mensaje = '';
if (empty($_SESSION['csrf_recuperar'])) {
    $_SESSION['csrf_recuperar'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_recuperar'], $_POST['csrf'] ?? '')) {
        $error = 'La solicitud expiró. Actualizá la página e intentá nuevamente.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $codigo === '') {
            $error = 'Ingresá el correo del ADMIN y el código de recuperación.';
        } elseif (strlen($password) < 8) {
            $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
        } elseif ($password !== $confirmar) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            $stmt = $conn->prepare("SELECT idUsuario, tokenRecuperacion FROM usuario WHERE email=? AND rol='ADMIN' AND estado=1 LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $hashIngresado = hash('sha256', $codigo);
            if (!$admin || empty($admin['tokenRecuperacion']) || !hash_equals($admin['tokenRecuperacion'], $hashIngresado)) {
                $error = 'El correo o el código de recuperación no son válidos.';
            } else {
                $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
                $idAdmin = (int)$admin['idUsuario'];
                $actualizar = $conn->prepare('UPDATE usuario SET passwordHash=?, tokenRecuperacion=NULL, tokenExpiracion=NULL WHERE idUsuario=? AND rol=\'ADMIN\'');
                $actualizar->bind_param('si', $nuevoHash, $idAdmin);
                if ($actualizar->execute() && $actualizar->affected_rows === 1) {
                    $mensaje = 'La contraseña se actualizó correctamente. El código de recuperación ya no puede volver a utilizarse.';
                    unset($_SESSION['csrf_recuperar']);
                } else {
                    $error = 'No se pudo actualizar la contraseña. Intentá nuevamente.';
                }
                $actualizar->close();
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
    <title>Recuperar contraseña - Kapykuá</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
<header class="auth-banner">Recuperar contraseña</header>
<main class="auth-card">
    <h1>Recuperar acceso</h1>
    <p class="auth-intro">Ingresá el correo del ADMIN, el código de recuperación y una nueva contraseña.</p>
    <?php if ($error !== ''): ?><p class="auth-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
    <?php if ($mensaje !== ''): ?>
        <p class="mensaje exito"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="admin-actions"><a href="index.php">Volver al inicio de sesión</a></p>
    <?php else: ?>
        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf_recuperar'], ENT_QUOTES, 'UTF-8'); ?>">
            <label>Correo del ADMIN</label><input type="email" name="email" required autocomplete="username">
            <label>Código de recuperación</label><input type="text" name="codigo" required autocomplete="off">
            <label>Nueva contraseña (mínimo 8 caracteres)</label><input type="password" name="password" required minlength="8" autocomplete="new-password">
            <label>Confirmar nueva contraseña</label><input type="password" name="confirmar" required minlength="8" autocomplete="new-password">
            <button class="auth-submit">Restablecer contraseña</button>
        </form>
        <p class="admin-actions"><a href="index.php">Volver al inicio de sesión</a></p>
    <?php endif; ?>
</main>
</body>
</html>
