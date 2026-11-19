<?php
session_start();
require_once __DIR__ . "/conexion.php";
if (empty($_SESSION["idUsuario"])) { http_response_code(401); exit("Debés iniciar sesión."); }
$idDiseno = filter_input(INPUT_GET, "idDiseno", FILTER_VALIDATE_INT);
if (!$idDiseno || $idDiseno < 1) { http_response_code(400); exit("Archivo inválido."); }
$stmt = $conn->prepare("SELECT archivo FROM diseño WHERE idDiseño = ? LIMIT 1");
$stmt->bind_param("i", $idDiseno); $stmt->execute(); $fila = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$fila || empty($fila["archivo"])) { http_response_code(404); exit("No se encontró el archivo."); }
$base = realpath(__DIR__ . "/uploads/disenos");
$ruta = realpath(__DIR__ . "/" . $fila["archivo"]);
if (!$base || !$ruta || strpos($ruta, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($ruta)) { http_response_code(404); exit("El archivo no está disponible."); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);
if (!in_array($mime, ["image/jpeg", "image/png", "image/webp", "application/pdf"], true)) { http_response_code(415); exit("Tipo de archivo no permitido."); }
header("X-Content-Type-Options: nosniff"); header("Content-Type: " . $mime); header("Content-Length: " . filesize($ruta));
header("Content-Disposition: attachment; filename=\"archivo-diseno-" . $idDiseno . "\""); readfile($ruta); exit;
