<?php
/**
 * Proxy para enviar PDF para n8n corrigindo charset
 */

$N8N_URL = "https://n8n.impulsemarketing.com.br/webhook/pneucamara";

// Campos de texto
$postFields = [];
foreach ($_POST as $key => $value) {
    // Converte ISO-8859-1 para UTF-8
    $encoding = mb_detect_encoding($value, ['UTF-8','ISO-8859-1'], true);
    if ($encoding === 'ISO-8859-1') {
        $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
    }
    $postFields[$key] = $value;
}

// Arquivos
foreach ($_FILES as $key => $file) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        $postFields[$key] = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
    }
}

// cURL
$ch = curl_init($N8N_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: " . ($_SERVER['HTTP_AUTHORIZATION'] ?? ""),
    "User-Agent: PHP-Proxy/1.0"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(["error" => $curlError]);
} else {
    http_response_code($httpCode);
    echo $response;
}
