<?php
/**
 * Endpoint para verificar status de pagamento PIX via Gateway KorePay
 * URL: /check-payment-status/{transactionId}
 * ou: /api/check-payment-status.php?transactionId={transactionId}
 */

// Configurações CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Credenciais KorePay
define('KOREPAY_PUBLIC_KEY', 'pk_OTmQuLsiMWWsBrlLIJH8kulghxBFLSoh8VEN3tMKDHmD1RzM');
define('KOREPAY_SECRET_KEY', 'sk_W16KWbMCRvUlh669P8fLNoG3DU-H12LJIv2Q1Dwl1ylBs6pQ');
define('KOREPAY_API_URL', 'https://api.korepay.com.br/v1/transactions');

// Extrair transaction_id da URL ou query string
$transactionId = '';

// Tentar extrair da URL (formato: /check-payment-status/35432833)
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$pathParts = explode('/', trim(parse_url($requestUri, PHP_URL_PATH), '/'));

// Procurar transaction_id na URL
foreach ($pathParts as $index => $part) {
    if ($part === 'check-payment-status' && isset($pathParts[$index + 1])) {
        $transactionId = $pathParts[$index + 1];
        break;
    }
}

// Se não encontrou na URL, tentar query string
if (empty($transactionId)) {
    $transactionId = isset($_GET['transactionId']) ? trim($_GET['transactionId']) : '';
}

// Validar transaction_id
if (empty($transactionId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Transaction ID não fornecido',
        'message' => 'Por favor, informe o transaction_id na URL ou query string',
        'example' => '/check-payment-status/35432833 ou ?transactionId=35432833'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Log para debug
error_log("🔍 [KOREPAY STATUS] Verificando status do pagamento: " . $transactionId);

// Se for transação simulada, retornar status pending
if (strpos($transactionId, 'MOCK_') === 0) {
    error_log("🧪 [MOCK] Verificando status de transação simulada");
    echo json_encode([
        'success' => true,
        'transaction_id' => $transactionId,
        'status' => 'pending',
        'amount' => 0,
        'pix_code' => '',
        'timestamp' => '',
        'paid' => false,
        'message' => 'Aguardando pagamento (modo simulação)',
        'simulation_mode' => true
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Construir URL da API KorePay
$apiUrl = KOREPAY_API_URL . '/' . rawurlencode($transactionId);

// Criar autenticação Basic Auth (publicKey:secretKey)
$authString = base64_encode(KOREPAY_PUBLIC_KEY . ':' . KOREPAY_SECRET_KEY);

// Fazer requisição para API KorePay
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Authorization: Basic ' . $authString
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Verificar erros do cURL
if ($response === false || !empty($curlError)) {
    error_log("❌ [KOREPAY STATUS] Erro cURL: " . $curlError);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro na comunicação com gateway',
        'message' => 'Não foi possível conectar ao gateway de pagamento. Tente novamente mais tarde.',
        'details' => $curlError
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar código HTTP
if ($httpCode < 200 || $httpCode >= 300) {
    error_log("❌ [KOREPAY STATUS] HTTP Error: " . $httpCode);
    error_log("❌ [KOREPAY STATUS] Resposta: " . substr($response, 0, 500));
    
    // Tentar decodificar resposta de erro
    $errorData = json_decode($response, true);
    $errorMessage = 'Erro ao verificar status do pagamento';
    
    if (is_array($errorData) && isset($errorData['message'])) {
        $errorMessage = $errorData['message'];
    } elseif (is_array($errorData) && isset($errorData['error'])) {
        $errorMessage = $errorData['error'];
    }
    
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'error' => 'Erro no gateway',
        'message' => $errorMessage,
        'http_code' => $httpCode,
        'korepay_response' => $errorData
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Decodificar resposta JSON
$korepayResponse = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("❌ [KOREPAY STATUS] Erro ao decodificar JSON: " . json_last_error_msg());
    error_log("❌ [KOREPAY STATUS] Resposta recebida: " . substr($response, 0, 500));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Resposta inválida do gateway',
        'message' => 'O gateway retornou uma resposta inválida'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Log da resposta
error_log("✅ [KOREPAY STATUS] Resposta recebida: " . strlen($response) . " bytes");
error_log("📋 [KOREPAY STATUS] Status HTTP: " . $httpCode);

// Mapear status da KorePay para nosso formato
// KorePay pode usar: waiting_payment, paid, expired, cancelled
$korepayStatus = strtolower($korepayResponse['status'] ?? 'pending');

// Mapear para formato padrão
$statusMap = [
    'waiting_payment' => 'pending',
    'pending' => 'pending',
    'paid' => 'completed',
    'expired' => 'failed',
    'cancelled' => 'failed',
    'completed' => 'completed',
    'approved' => 'completed'
];

$mappedStatus = $statusMap[$korepayStatus] ?? 'pending';

// Extrair dados do pagamento
$amount = isset($korepayResponse['amount']) ? ($korepayResponse['amount'] / 100) : 0; // Converter centavos para reais

// Campo correto é 'qrcode' (minúsculo) dentro de 'pix'
$pixData = $korepayResponse['pix'] ?? [];
$pixCode = $pixData['qrcode'] ?? '';

// Verificar se foi pago (pode ter campo paidAt ou status paid)
$paidAt = $korepayResponse['paidAt'] ?? $korepayResponse['paid_at'] ?? null;
$isPaid = ($mappedStatus === 'completed') || !empty($paidAt);

error_log("📋 [STATUS] Status KorePay: {$korepayStatus} → Mapeado: {$mappedStatus}");
error_log("📋 [STATUS] Amount: R$ {$amount}");
error_log("📋 [STATUS] Paid: " . ($isPaid ? 'SIM' : 'NÃO'));

// Formatar resposta padronizada
$responseData = [
    'success' => true,
    'transaction_id' => $transactionId,
    'status' => $mappedStatus,
    'amount' => $amount,
    'pix_code' => $pixCode,
    'timestamp' => $korepayResponse['createdAt'] ?? $korepayResponse['created_at'] ?? '',
    'paid' => $isPaid,
    'paid_at' => $paidAt,
    'message' => $isPaid ? 'Pagamento concluído' : 'Aguardando pagamento',
    'korepay_status' => $korepayStatus,
    'korepay_raw' => $korepayResponse  // Incluir resposta completa para debug (opcional)
];

// Retornar resposta
http_response_code(200);
echo json_encode($responseData, JSON_UNESCAPED_UNICODE);
exit;

