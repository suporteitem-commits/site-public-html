<?php
declare(strict_types=1);

// Evitar vazamento de warnings/HTML em respostas JSON
ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

// Aceitar apenas POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'error' => 'method_not_allowed',
        'allowed' => 'POST',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Ler corpo bruto e tentar decodificar como JSON
$rawBody = file_get_contents('php://input');
if ($rawBody === false || $rawBody === '') {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'empty_body',
        'message' => 'Corpo da requisição está vazio.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$payload = json_decode($rawBody, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'invalid_json',
        'message' => 'Corpo da requisição não é JSON válido.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Extrair campos esperados do webhook FreePay
// A FreePay envia campos em PascalCase (Id, Status, PaidAt, Amount, etc.)
// Mas também aceitamos snake_case para compatibilidade
$transactionId = isset($payload['Id']) ? (string) $payload['Id'] : (isset($payload['id']) ? (string) $payload['id'] : '');
$status = isset($payload['Status']) ? (string) $payload['Status'] : (isset($payload['status']) ? (string) $payload['status'] : '');
$paidAt = isset($payload['PaidAt']) ? (string) $payload['PaidAt'] : (isset($payload['paid_at']) ? (string) $payload['paid_at'] : null);
$amount = isset($payload['Amount']) ? (int) $payload['Amount'] : (isset($payload['amount']) ? (int) $payload['amount'] : 0);
$paymentMethod = isset($payload['PaymentMethod']) ? (string) $payload['PaymentMethod'] : (isset($payload['payment_method']) ? (string) $payload['payment_method'] : '');

// URL destino para encaminhar o webhook recebido (ajuste conforme a necessidade do seu sistema)
// Mantendo o encaminhamento para o domínio do projeto anterior como referência
$FORWARD_URL = 'https://lista-cobranca.site/payment/webhookis.php'; 

// Montar payload para encaminhamento
$forwardData = [
    'transactionId' => $transactionId,
    'status' => $status,
    'paidAt' => $paidAt,
    'amount' => $amount,
    'paymentMethod' => $paymentMethod,
    'source' => 'freepay_webhook',
    'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    'remoteAddr' => $_SERVER['REMOTE_ADDR'] ?? null,
    'rawPayload' => $payload
];

// Enviar via cURL como JSON
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $FORWARD_URL,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode($forwardData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    CURLOPT_TIMEOUT => 20,
]);

$forwardResponse = curl_exec($ch);
$forwardError = curl_error($ch);
$forwardCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($forwardResponse === false) {
    // Mesmo se falhar o encaminhamento, aceitar o webhook
    error_log("Falha ao encaminhar webhook: " . $forwardError);
}

// Responder ao FreePay que o webhook foi recebido com sucesso
echo json_encode([
    'ok' => true,
    'message' => 'Webhook recebido com sucesso',
    'transaction_id' => $transactionId,
    'status' => $status,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
