<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Configurações da API FreePay
define('FREEPAY_API_URL', 'https://api.freepaybrasil.com/v1/payment-transaction/create');
define('FREEPAY_SECRET_KEY', 'sk_live_pTd32CMaqwH8j0Bcxe1plVm6vKpGhL4K');
define('FREEPAY_PUBLIC_KEY', 'pk_live_ogMINe8u1dXDBs0vqtNDMdWPzWffuiEa');

// Detectar URL base automaticamente
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$baseUrl = $protocol . '://' . $host . $scriptPath;
define('FREEPAY_POSTBACK_URL', rtrim($baseUrl, '/') . '/webhook.php');

// Receber parâmetros
$amount = isset($_GET['amount']) ? intval($_GET['amount']) : 8749; // R$ 87,49
$cpf = isset($_GET['cpf']) ? preg_replace('/\D/', '', $_GET['cpf']) : '';
$email = isset($_GET['email']) ? filter_var($_GET['email'], FILTER_SANITIZE_EMAIL) : '';

// Validações básicas
if (empty($cpf) || strlen($cpf) !== 11) {
    echo json_encode(['success' => false, 'error' => 'CPF inválido']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'E-mail inválido']);
    exit;
}

// Gerar nome fictício baseado no CPF
$nomeCliente = 'Cliente ' . substr($cpf, 0, 3);

// Gerar telefone fictício
// Gerar telefone fictício (A FreePay exige um telefone no formato DDI+DDD+Número)
// Usando um número fictício com DDI 55 (Brasil) e DDD 11 (São Paulo)
$phoneCliente = '5511999999999';

// Obter IP do cliente
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// Preparar payload para a API FreePay
$payload = [
    'payment_method' => 'pix',
    'amount' => $amount,
    'postback_url' => FREEPAY_POSTBACK_URL,
    'customer' => [
        'name' => $nomeCliente,
        'email' => $email,
        'phone' => '+' . $phoneCliente,
        'document' => [
            'type' => 'cpf',
            'number' => $cpf
        ]
    ],
    'items' => [
        [
            'title' => 'Relatório CPF Completo',
            'unit_price' => $amount,
            'quantity' => 1,
            'tangible' => false,
            'external_ref' => 'CPF_' . uniqid()
        ]
    ],
    'metadata' => [
        'provider_name' => 'Consulta CPF',
        'cpf' => $cpf
    ],
    'ip' => $clientIp
];

// Autenticação Basic Auth
$authString = base64_encode(FREEPAY_PUBLIC_KEY . ':' . FREEPAY_SECRET_KEY);

// Fazer requisição para a API FreePay
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => FREEPAY_API_URL,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . $authString
    ],
]);

$response = curl_exec($curl);
$error = curl_error($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);



if ($error) {
    echo json_encode(['success' => false, 'error' => 'Erro na comunicação: ' . $error]);
    exit;
}

$responseData = json_decode($response, true);

if ($http_code >= 400) {
    $errorMessage = $responseData['message'] ?? 'Erro desconhecido';
    echo json_encode(['success' => false, 'error' => $errorMessage]);
    exit;
}

// Extrair os dados necessários da resposta da FreePay
$transactionId = $responseData['data']['id'] ?? null;
$pixCopyPaste = $responseData['data']['pix']['qr_code'] ?? null;
$qrCodeUrl = $responseData['data']['pix']['url'] ?? null;

// Validar resposta
if (empty($pixCopyPaste) && empty($qrCodeUrl)) {
    echo json_encode(['success' => false, 'error' => 'Dados do PIX não retornados']);
    exit;
}

// Retornar sucesso
echo json_encode([
    'success' => true,
    'transaction_id' => $transactionId,
    'pix_code' => $pixCopyPaste,
    'qr_code_url' => $qrCodeUrl
]);
