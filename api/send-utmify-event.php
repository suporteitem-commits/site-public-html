<?php
/**
 * Proxy para envio de eventos Utmify via Frontend
 * Recebe JSON do JS e encaminha para Utmify via Server-Side para evitar CORS
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Lidar com OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'UtmifyClient.php';

// Ler payload
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Payload inválido']);
    exit;
}

// Logs para debug
error_log("[UTMIFY-PROXY] Recebido evento: " . ($data['status'] ?? 'desconhecido'));

// Se o payload já vier pronto (estrutura da Utmify), enviamos direto
// Caso contrário, poderíamos usar o buildPayload, mas o frontend já está montando o JSON completo.
// Vamos assumir que o frontend envia o payload pronto ou os dados para montar.

// Verificando se é um payload pronto (tem 'orderId' e 'status')
if (isset($data['orderId']) && isset($data['status'])) {

    // Se for teste, remove flag isTest para produção se necessário, ou mantém
    // $data['isTest'] = false; 

    $result = UtmifyClient::sendOrder($data);

    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Estrutura de dados inválida. Esperado payload Utmify completo.']);
}
