<?php
/**
 * Arquivo de teste simples para verificar se PHP está funcionando na pasta api/
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'message' => 'PHP está funcionando na pasta api/',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido',
    'php_version' => phpversion(),
    'file_path' => __FILE__,
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

