<?php
session_start();
require_once __DIR__ . "/conexion.php";
if (empty($_SESSION["idUsuario"])) {
    http_response_code(401);
    exit("Debés iniciar sesión para acceder a este archivo.");
}
$idArchivo = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$idArchivo || $idArchivo < 1) {
    http_response_code(400);
    exit("Archivo inválido.");
}
$stmt = $conn->prepare("SELECT nombreOriginal, ruta, tipoMime FROM archivopedido WHERE idArchivo = ? LIMIT 1");
$stmt->bind_param("i", $idArchivo);
$stmt->execute();
$archivo = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$archivo) {
    http_response_code(404);
    exit("No se encontró el archivo solicitado.");
}
$base = realpath(__DIR__ . "/uploads/disenos");
$ruta = realpath(__DIR__ . "/" . $archivo["ruta"]);
if (!$base || !$ruta || strpos($ruta, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($ruta)) {
    http_response_code(404);
    exit("El archivo ya no está disponible.");
}
$permitidos = ["image/jpeg", "image/png", "image/webp", "application/pdf"];
$mime = in_array($archivo["tipoMime"], $permitidos, true) ? $archivo["tipoMime"] : "application/octet-stream";
$nombre = str_replace(["\r", "\n", '"'], "", basename($archivo["nombreOriginal"]));
$ver = isset($_GET["ver"]) && $_GET["ver"] === "1" && in_array($mime, ["image/jpeg", "image/png", "image/webp", "application/pdf"], true);
header("X-Content-Type-Options: nosniff");
header("Content-Type: " . $mime);
header("Content-Length: " . filesize($ruta));
header("Content-Disposition: " . ($ver ? "inline" : "attachment") . "; filename*=UTF-8''" . rawurlencode($nombre));
readfile($ruta);
exit;
