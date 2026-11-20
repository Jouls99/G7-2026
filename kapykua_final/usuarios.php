<?php
session_start();
require_once 'conexion.php';
if (empty($_SESSION['idUsuario'])) { header('Location: index.php'); exit; }
if (($_SESSION['rol'] ?? '') !== 'ADMIN') { http_response_code(403); exit('Acceso denegado.'); }
$msg=''; $error='';
if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD']==='POST') {
 if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) $error='La solicitud expiró. Actualizá la página e intentá nuevamente.';
 else {
  $accion=$_POST['accion']??''; $id=(int)($_POST['id']??0);
  if ($accion==='crear') {
   $nombre=trim($_POST['nombre']??''); $email=trim($_POST['email']??''); $telefono=trim($_POST['telefono']??''); $pass=$_POST['password']??'';
   if($nombre===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($pass)<8) $error='Completá los datos correctamente. La contraseña debe tener al menos 8 caracteres.';
   else { $hash=password_hash($pass,PASSWORD_DEFAULT); $st=$conn->prepare("INSERT INTO usuario(nombre,telefono,email,passwordHash,rol,estado) VALUES(?,?,?,?, 'EMPLEADO',1)"); $st->bind_param('ssss',$nombre,$telefono,$email,$hash); if($st->execute())$msg='Empleado creado correctamente.';else $error=($st->errno===1062?'Ese correo ya está registrado.':'No se pudo crear el usuario.');$st->close(); }
  } elseif($id>0 && $id!==(int)$_SESSION['idUsuario'] && $accion==='estado') {
   $st=$conn->prepare("UPDATE usuario SET estado=1-estado WHERE idUsuario=? AND rol='EMPLEADO'");$st->bind_param('i',$id);$st->execute();$msg=$st->affected_rows?'Estado actualizado.':'No se modificó la cuenta.';$st->close();
  } elseif($id>0 && $id!==(int)$_SESSION['idUsuario'] && $accion==='rol') {
   $rol=$_POST['rol']??'';if(in_array($rol,['ADMIN','EMPLEADO'],true)){$st=$conn->prepare('UPDATE usuario SET rol=? WHERE idUsuario=?');$st->bind_param('si',$rol,$id);$st->execute();$msg='Rol actualizado.';$st->close();}
  } elseif($id>0 && $accion==='reset') {
   $pass=$_POST['password']??'';if(strlen($pass)<8)$error='La contraseña temporal debe tener al menos 8 caracteres.';else{$hash=password_hash($pass,PASSWORD_DEFAULT);$st=$conn->prepare('UPDATE usuario SET passwordHash=? WHERE idUsuario=?');$st->bind_param('si',$hash,$id);$st->execute();$msg='Contraseña restablecida. Compartila con el usuario por un medio seguro.';$st->close();}
  }
 }
}
$res=$conn->query('SELECT idUsuario,nombre,email,rol,estado,fechaRegistro FROM usuario ORDER BY idUsuario');
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administración - Kapykuá</title><link rel="stylesheet" href="style.css"></head><body class="admin-page"><main class="admin-panel"><h1>Administración de usuarios</h1><p>Creá cuentas y administrá los permisos del equipo.</p><?php if($msg):?><p class="mensaje exito"><?php echo htmlspecialchars($msg);?></p><?php endif;?><?php if($error):?><p class="mensaje error"><?php echo htmlspecialchars($error);?></p><?php endif;?>
<h2>Crear empleado</h2><form method="post" class="admin-create"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']);?>"><input type="hidden" name="accion" value="crear"><label>Nombre <input name="nombre" required></label><label>Teléfono <input name="telefono"></label><label>Correo <input type="email" name="email" required></label><label>Contraseña inicial <input type="password" name="password" minlength="8" required></label><button>Crear empleado</button></form>
<h2>Usuarios registrados</h2><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><?php while($u=$res->fetch_assoc()):?><tr><td><?php echo htmlspecialchars($u['nombre']);?></td><td><?php echo htmlspecialchars($u['email']);?></td><td><?php echo htmlspecialchars($u['rol']);?></td><td><?php echo $u['estado']?'Activo':'Inactivo';?></td><td><?php if((int)$u['idUsuario']!==(int)$_SESSION['idUsuario']):?><form method="post"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']);?>"><input type="hidden" name="id" value="<?php echo (int)$u['idUsuario'];?>"><input type="hidden" name="accion" value="estado"><button><?php echo $u['estado']?'Desactivar':'Activar';?></button></form><form method="post"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']);?>"><input type="hidden" name="id" value="<?php echo (int)$u['idUsuario'];?>"><input type="hidden" name="accion" value="rol"><select name="rol"><option value="EMPLEADO" <?php echo $u['rol']==='EMPLEADO'?'selected':'';?>>EMPLEADO</option><option value="ADMIN" <?php echo $u['rol']==='ADMIN'?'selected':'';?>>ADMIN</option></select><button>Cambiar rol</button></form><?php endif;?><details><summary>Restablecer contraseña</summary><form method="post"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']);?>"><input type="hidden" name="id" value="<?php echo (int)$u['idUsuario'];?>"><input type="hidden" name="accion" value="reset"><input type="password" name="password" minlength="8" placeholder="Nueva contraseña temporal" required><button>Guardar</button></form></details></td></tr><?php endwhile;?></tbody></table></div><p class="admin-actions"><a href="index.php">← Volver a Kapykuá</a></p></main></body></html>
