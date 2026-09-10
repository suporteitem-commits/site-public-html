<?php
/**
 * Endpoint para processar pagamento Mounjaro via Gateway KorePay
 * Gera PIX e retorna código e QR Code
 * Integração com Utmify para rastreamento
 */

require_once 'UtmifyClient.php'; // Importar cliente Utmify

// Configurações CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Log para debug (remover em produção se necessário)
error_log("🔍 [KOREPAY DEBUG] REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'NOT SET'));
error_log("🔍 [KOREPAY DEBUG] HTTP_X_HTTP_METHOD_OVERRIDE: " . ($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? 'NOT SET'));
error_log("🔍 [KOREPAY DEBUG] REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET'));

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Detectar método HTTP (alguns servidores podem usar variáveis diferentes)
$httpMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
    $httpMethod = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
}

// Ler dados do corpo da requisição primeiro (php://input só pode ser lido uma vez)
$input = file_get_contents('php://input');

// Se houver dados POST, considerar como POST mesmo que o método seja GET (alguns servidores fazem isso)
$hasPostData = !empty($_POST) || !empty($input);

// Aceitar POST ou se houver dados no corpo (para casos de rewrite)
if ($httpMethod !== 'POST' && !$hasPostData) {
    error_log("❌ [KOREPAY] Método HTTP incorreto: " . $httpMethod);
    error_log("❌ [KOREPAY] Tem dados POST: " . ($hasPostData ? 'SIM' : 'NÃO'));
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido',
        'message' => 'Este endpoint aceita apenas requisições POST',
        'debug' => [
            'received_method' => $httpMethod,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'NOT SET',
            'has_post_data' => $hasPostData,
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'NOT SET'
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Credenciais KorePay
define('KOREPAY_PUBLIC_KEY', 'pk_OTmQuLsiMWWsBrlLIJH8kulghxBFLSoh8VEN3tMKDHmD1RzM');
define('KOREPAY_SECRET_KEY', 'sk_W16KWbMCRvUlh669P8fLNoG3DU-H12LJIv2Q1Dwl1ylBs6pQ');
define('KOREPAY_API_URL', 'https://api.korepay.com.br/v1/transactions');

// Usar dados do corpo da requisição já lidos anteriormente
$paymentData = json_decode($input, true);

// Validar JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'JSON inválido',
        'message' => 'O corpo da requisição não é um JSON válido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Log para debug
error_log("💳 [KOREPAY] Processando pagamento para: " . ($paymentData['name'] ?? 'N/A'));
error_log("💰 [KOREPAY] Valor: R$ " . ($paymentData['amount'] ?? 0));

// Validar dados obrigatórios
$errors = [];

if (empty($paymentData['name'])) {
    $errors[] = 'Nome do cliente é obrigatório';
}

if (empty($paymentData['cpf'])) {
    $errors[] = 'CPF é obrigatório';
}

if (empty($paymentData['email'])) {
    $errors[] = 'Email é obrigatório';
}

if (empty($paymentData['amount']) || !is_numeric($paymentData['amount']) || $paymentData['amount'] <= 0) {
    $errors[] = 'Valor do pagamento é obrigatório e deve ser maior que zero';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Dados inválidos',
        'message' => implode(', ', $errors),
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Extrair e limpar dados
$customerName = trim($paymentData['name']);
$customerEmail = filter_var($paymentData['email'], FILTER_SANITIZE_EMAIL);

// Validar email
if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Email inválido',
        'message' => 'O email fornecido não é válido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Limpar CPF (remover formatação)
$customerCpf = preg_replace('/\D/', '', $paymentData['cpf']);

// Validar CPF (11 dígitos)
if (strlen($customerCpf) !== 11) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'CPF inválido',
        'message' => 'CPF deve conter 11 dígitos'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Limpar telefone (remover caracteres especiais) - pode ser null se não fornecido
$customerPhone = null;
if (isset($paymentData['phone']) && !empty($paymentData['phone'])) {
    $customerPhone = preg_replace('/\D/', '', $paymentData['phone']);
    // Se o telefone não tiver DDI, adicionar 55 (Brasil)
    if (strlen($customerPhone) === 11) {
        $customerPhone = '55' . $customerPhone;
    }
    // Validar tamanho mínimo (DDI + DDD + número)
    if (strlen($customerPhone) < 12) {
        $customerPhone = null; // Telefone inválido, usar null
    }
}

// Converter valor para centavos (garantir que seja inteiro)
$amountReais = floatval($paymentData['amount']);
$amountCents = intval(round($amountReais * 100));

// Validar que o valor em centavos é válido
if ($amountCents <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Valor inválido',
        'message' => 'O valor do pagamento deve ser maior que zero',
        'debug' => [
            'amount_reais' => $amountReais,
            'amount_cents' => $amountCents
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Log do valor convertido para debug
error_log("💰 [KOREPAY] Valor convertido: R$ {$amountReais} = {$amountCents} centavos");

// Obter dosagem (padrão: 5mg)
$dosage = isset($paymentData['dosage']) ? $paymentData['dosage'] : '5mg';

// Obter nome do produto
$productName = isset($paymentData['product']) ? $paymentData['product'] : "Monjaro {$dosage}";

// Extrair parâmetros de rastreamento (UTM)
$trackingParams = [
    'utm_source' => $paymentData['utm_source'] ?? null,
    'utm_campaign' => $paymentData['utm_campaign'] ?? null,
    'utm_medium' => $paymentData['utm_medium'] ?? null,
    'utm_content' => $paymentData['utm_content'] ?? null,
    'utm_term' => $paymentData['utm_term'] ?? null,
    'utm_src' => $paymentData['src'] ?? null,
    'utm_sck' => $paymentData['sck'] ?? null
];

// Detectar URL base para postback (opcional)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$postbackUrl = $protocol . '://' . $host . '/api/webhook-korepay.php';

// Preparar payload para API KorePay (formato correto conforme documentação)
$korepayPayload = [
    'amount' => $amountCents,
    'paymentMethod' => 'pix',
    'items' => [
        [
            'title' => $productName,
            'unitPrice' => $amountCents,
            'quantity' => 1,
            'tangible' => false
        ]
    ],
    'customer' => [
        'name' => $customerName,
        'email' => $customerEmail,
        'document' => [
            'type' => 'cpf',  // minúsculo conforme API
            'number' => $customerCpf
        ]
    ]
];

// Adicionar phone apenas se fornecido (opcional)
if (!empty($customerPhone)) {
    $korepayPayload['customer']['phone'] = $customerPhone;
}

// Adicionar metadados para recuperar no webhook (preservar UTMs)
// Adicionar metadados como STRING (exigencia KorePay: metadata deve ser string)
$metadataJson = json_encode([
    'utm_source' => $trackingParams['utm_source'] ?? '',
    'utm_campaign' => $trackingParams['utm_campaign'] ?? '',
    'utm_medium' => $trackingParams['utm_medium'] ?? '',
    'utm_content' => $trackingParams['utm_content'] ?? '',
    'utm_term' => $trackingParams['utm_term'] ?? '',
    'src' => $trackingParams['utm_src'] ?? '',
    'sck' => $trackingParams['utm_sck'] ?? ''
]);

$korepayPayload['metadata'] = $metadataJson;

// Fallback: tentar passar via customId caso metadata não volte
$korepayPayload['customId'] = substr($metadataJson, 0, 255); // Limitar tamanho se necessario

// Adicionar postbackUrl apenas se necessário (opcional)
if (!empty($postbackUrl)) {
    $korepayPayload['postbackUrl'] = $postbackUrl;
}

// Log do payload
error_log("📦 [KOREPAY] Payload: " . json_encode($korepayPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

// Criar autenticação Basic Auth (publicKey:secretKey)
$authString = base64_encode(KOREPAY_PUBLIC_KEY . ':' . KOREPAY_SECRET_KEY);

// Fazer requisição para API KorePay
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => KOREPAY_API_URL,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($korepayPayload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
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
    error_log("❌ [KOREPAY] Erro cURL: " . $curlError);
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
    error_log("❌ [KOREPAY] HTTP Error: " . $httpCode);
    error_log("❌ [KOREPAY] Resposta: " . substr($response, 0, 500));

    // Tentar decodificar resposta de erro
    $errorData = json_decode($response, true);
    $errorMessage = 'Erro ao processar pagamento';

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
    error_log("❌ [KOREPAY] Erro ao decodificar JSON: " . json_last_error_msg());
    error_log("❌ [KOREPAY] Resposta recebida: " . substr($response, 0, 500));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Resposta inválida do gateway',
        'message' => 'O gateway retornou uma resposta inválida'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Log da resposta
error_log("✅ [KOREPAY] Resposta recebida: " . strlen($response) . " bytes");
error_log("📋 [KOREPAY] Status: " . $httpCode);

// Extrair dados da resposta
$transactionId = $korepayResponse['id'] ?? null;
$pixData = $korepayResponse['pix'] ?? [];
$pixCode = $pixData['qrcode'] ?? '';
$expiresAt = $pixData['expirationDate'] ?? $korepayResponse['createdAt'] ?? '';

// Validar se o código PIX foi retornado
if (empty($pixCode)) {
    error_log("❌ [KOREPAY] Código PIX não encontrado na resposta");
    error_log("📋 [KOREPAY] Resposta completa: " . json_encode($korepayResponse, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Código PIX não gerado',
        'message' => 'O gateway não retornou o código PIX. Tente novamente.',
        'korepay_response' => $korepayResponse
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Gerar URL do QR Code
$pixQrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=2&data=' . urlencode($pixCode);

error_log("✅ [KOREPAY] PIX gerado com sucesso: " . $transactionId);
error_log("📋 [KOREPAY] Código PIX: " . substr($pixCode, 0, 50) . "...");
error_log("📋 [KOREPAY] QR Code URL: " . $pixQrCodeUrl);
error_log("📋 [KOREPAY] Expira em: " . $expiresAt);

// ==============================================================================
// INTEGRACAO UTMIFY - PIX GERADO
// ==============================================================================
try {
    $utmifyPayload = array_merge($paymentData, $trackingParams, [
        'amount' => $amountReais,
        'created_at' => gmdate('Y-m-d H:i:s'), // UTC Agora
        'name' => $customerName,
        'email' => $customerEmail,
        'cpf' => $customerCpf,
        'phone' => $customerPhone,
        'product' => $productName
    ]);

    $payload = UtmifyClient::buildPayload($utmifyPayload, 'waiting_payment', $transactionId);

    // Envia evento em background (ou síncrono, já que PHP tem limitações de threads)
    // Para não travar a resposta do usuário, idealmente seria via queue, mas aqui faremos direto com timeout baixo
    error_log("Enviando evento de PIX CRIADO para Utmify...");
    UtmifyClient::sendOrder($payload);

} catch (Exception $e) {
    error_log("❌ [UTMIFY] Erro ao enviar evento: " . $e->getMessage());
    // Não interromper o fluxo principal se falhar o tracking
}
// ==============================================================================

// Formatar resposta para o frontend
$responseData = [
    'success' => true,
    'transaction_id' => $transactionId,
    'pix_code' => $pixCode,
    'pix_qrcode' => $pixQrCodeUrl,
    'amount' => $amountReais,
    'status' => 'pending',
    'expires_at' => $expiresAt,
    'message' => 'PIX gerado com sucesso via KorePay'
];

// Retornar resposta
http_response_code(200);
echo json_encode($responseData, JSON_UNESCAPED_UNICODE);
exit;

