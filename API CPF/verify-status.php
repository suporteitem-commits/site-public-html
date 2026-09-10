<?php
declare(strict_types=1);

// Garantir saída JSON apenas
ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

// Ler transactionId da query string
$transactionId = isset($_GET['transactionId']) ? trim((string)$_GET['transactionId']) : '';
if ($transactionId === '') {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'missing_transaction_id',
        'message' => 'Informe ?transactionId=...',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Configurações da API FreePay (usando as mesmas credenciais do generate-pix.php)
// Em um sistema real, estas deveriam ser lidas de um arquivo de configuração ou variáveis de ambiente
$SECRET_KEY = 'sk_live_pTd32CMaqwH8j0Bcxe1plVm6vKpGhL4K';
$PUBLIC_KEY = 'pk_live_ogMINe8u1dXDBs0vqtNDMdWPzWffuiEa';
$FREEPAY_API_URL = 'https://api.freepaybrasil.com/v1/payment-transaction/info';

if (empty($SECRET_KEY) || empty($PUBLIC_KEY)) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'missing_credentials',
        'message' => 'Credenciais da API não configuradas.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$url = $FREEPAY_API_URL . '/' . rawurlencode($transactionId);

// Autenticação Basic Auth
$authString = base64_encode($PUBLIC_KEY . ':' . $SECRET_KEY);

// Inicializar cURL
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Authorization: Basic ' . $authString,
    ],
]);

$response = curl_exec($curl);
$curlErr = curl_error($curl);
$httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($response === false) {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'error' => 'curl_error',
        'message' => $curlErr ?: 'Falha ao executar requisição cURL',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Se FreePay retornar código de erro HTTP, encaminhar como JSON
if ($httpCode < 200 || $httpCode >= 300) {
    http_response_code($httpCode);
    $asJson = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($asJson)) {
        echo json_encode($asJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode([
            'ok' => false,
            'error' => 'freepay_http_error',
            'http_code' => $httpCode,
            'message' => 'Resposta não-JSON da FreePay',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    exit;
}

// Validar resposta JSON da FreePay
$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'error' => 'invalid_json_from_freepay',
        'message' => 'A FreePay não retornou JSON válido.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Retornar resposta da FreePay
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
