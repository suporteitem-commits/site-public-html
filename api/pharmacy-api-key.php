<?php
/**
 * API para retornar chave da API de farmácias (mock)
 * Em produção, isso seria uma chave real do Google Places ou outra API
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Retornar chave da API (pode ser vazia se não usar Google Places)
// Se tiver Google Places API Key, configure via variável de ambiente
$apiKey = getenv('GOOGLE_PLACES_API_KEY') ?: '';

http_response_code(200);
echo json_encode([
    'success' => true,
    'api_key' => $apiKey,
    'message' => empty($apiKey) ? 'Usando farmácias mockadas (sem Google Places API)' : 'Chave API configurada'
]);



