<?php
/**
 * Proxy para corrigir requisições multipart/form-data com charset ISO-8859-1
 * Envia os dados corretamente para o webhook do n8n.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // Mude para 1 apenas em desenvolvimento
ini_set('log_errors', 1);

$N8N_URL = "https://n8n.impulsemarketing.com.br/webhook/pneucamara";

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: *");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$postFields = [];

// === TRATAMENTO DE CAMPOS TEXTO (CORRIGE ISO-8859-1) ===
foreach ($_POST as $key => $value) {
    if (is_string($value)) {
        $detected = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1'], true);
        if ($detected === 'ISO-8859-1') {
            $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        } elseif ($detected === false || !mb_check_encoding($value, 'UTF-8')) {
            // Tentativa de correção caso tenha vindo corrompido
            $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        }
    }
    $postFields[$key] = $value;
}

// === TRATAMENTO DE ARQUIVOS ===
if (!empty($_FILES)) {
    foreach ($_FILES as $key => $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(["error" => "Upload error on field '$key': " . $file['error']]);
            exit;
        }
        if (is_uploaded_file($file['tmp_name'])) {
            $postFields[$key] = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
        }
    }
}

// === cURL ===
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $N8N_URL,
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_HTTPHEADER => [
        "Authorization: " . ($_SERVER['HTTP_AUTHORIZATION'] ?? ""),
        "Accept-Encoding: gzip, deflate",
        "User-Agent: PHP-Proxy/1.0",
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_SSL_VERIFYPEER => true, // Segurança SSL
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

if ($curlError) {
    http_response_code(500);
    error_log("cURL Error: $curlError");
    echo json_encode(["error" => "Proxy error: $curlError"]);
} else {
    http_response_code($httpCode);
    // Preserva Content-Type do n8n
    echo $response;
}
?>