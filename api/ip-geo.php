<?php
/**
 * Mock API para geolocalização por IP
 * Retorna dados mockados conforme esperado pelo frontend
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Dados mockados
$mockData = [
    "city" => "São Paulo",
    "region" => "SP",
    "regionName" => "São Paulo",
    "country" => "BR",
    "lat" => -23.5505,
    "lon" => -46.6333
];

http_response_code(200);
echo json_encode($mockData);
exit;



