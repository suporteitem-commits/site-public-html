<?php
/**
 * Mock API para farmácias por IP
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
    "success" => true,
    "data" => [
        "pharmacies" => [],
        "location" => [
            "city" => "São Paulo",
            "region" => "SP"
        ]
    ]
];

http_response_code(200);
echo json_encode($mockData);
exit;



