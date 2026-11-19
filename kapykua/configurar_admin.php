<?php
session_start(); require_once "conexion.php";
// Usar una sola vez. Clave de configuración inicial: KAPYKUA-V5-INICIO-2026
$claveSetup = "KAPYKUA-V5-INICIO-2026";
$err=""; $ok=false;
$yaConfigurado = (int)($conn->query("SELECT COUNT(*) AS total FROM usuario WHERE rol='ADMIN'")->fetch_assoc()["total"] ?? 0) > 0;
if ($yaConfigurado) { http_response_code(403); }
if (!$yaConfigurado && $_SERVER["REQUEST_METHOD"] === "POST") {
 if (!hash_equals($claveSetup, $_POST["clave_setup"] ?? "")) $err="Clave de configuración incorrecta.";
 elseif (strlen($_POST["password"] ?? "") < 8) $err="La contraseña debe tener al menos 8 caracteres.";
 elseif (!filter_var($_POST["email"] ?? "", FILTER_VALIDATE_EMAIL)) $err="Ingresá un correo válido.";
 else {
  $nombre=trim($_POST["nombre"]??"Administrador"); $email=trim($_POST["email"]); $hash=password_hash($_POST["password"],PASSWORD_DEFAULT);
  $r=$conn->query("SELECT idUsuario FROM usuario WHERE rol='ADMIN' ORDER BY idUsuario LIMIT 1"); $admin=$r?$r->fetch_assoc():null;
  if($admin){$id=(int)$admin["idUsuario"]; $st=$conn->prepare("UPDATE usuario SET nombre=?,email=?,passwordHash=?,estado=1,rol='ADMIN' WHERE idUsuario=?");$st->bind_param("sssi",$nombre,$email,$hash,$id);}
  else{$st=$conn->prepare("INSERT INTO usuario(nombre,email,passwordHash,rol,estado) VALUES(?,?,?,'ADMIN',1)");$st->bind_param("sss",$nombre,$email,$hash);}
  if($st->execute()){$ok=true;}else{$err="No se pudo guardar: ".$st->error;} $st->close();
 }
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><title>Configurar administrador</title><link rel="stylesheet" href="style.css"><style>body{font-family:Arial;background:#f7eef1;padding:30px}.box{max-width:480px;margin:auto;background:white;padding:28px;border-radius:16px}.box input{display:block;width:100%;padding:10px;margin:8px 0 16px}</style></head><body><form class="box" method="post"><h2>Configurar administrador</h2><?php if($yaConfigurado): ?><p>La configuración inicial ya fue realizada. Esta pantalla está bloqueada.</p><p><a href="index.php">Ir al inicio de sesión</a></p><?php else: ?><p>Actualiza el administrador existente sin eliminarlo ni romper los pedidos asociados.</p><?php if($ok): ?><p>Administrador configurado. <a href="index.php">Iniciar sesión</a></p><?php else: ?><?php if($err)echo '<p>'.htmlspecialchars($err).'</p>'; ?><label>Clave de configuración</label><input name="clave_setup" required><label>Nombre</label><input name="nombre" value="Administrador" required><label>Correo</label><input type="email" name="email" required><label>Nueva contraseña (mínimo 8 caracteres)</label><input type="password" name="password" minlength="8" required><button>Guardar administrador</button><?php endif; ?><?php endif; ?></form></body></html>