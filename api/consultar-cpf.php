<?php
/**
 * Proxy PHP para API de CPF - Nova Implementação
 * Usa apela-api.tech como provedor
 */

// Configurações CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Chave de usuário da API
$userKey = '1da00e5758d36202cc77bc44d10e928f';
$API_BASE_URL = "https://apela-api.tech/";

// Obter CPF da requisição
$cpf = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // GET: /api/consultar-cpf?cpf=11653188812
    $cpf = isset($_GET['cpf']) ? $_GET['cpf'] : '';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST: /api/consultar-cpf (com CPF no body JSON)
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $cpf = isset($data['cpf']) ? $data['cpf'] : '';
}

// Validar CPF
if (empty($cpf)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'CPF não fornecido',
        'message' => 'Por favor, informe o CPF na requisição'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Remover formatação do CPF (apenas números)
$cpf = preg_replace('/\D/', '', $cpf);

// Validar formato do CPF (11 dígitos)
if (strlen($cpf) !== 11) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'CPF inválido',
        'message' => 'CPF deve conter 11 dígitos'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Construir URL da API
$apiUrl = $API_BASE_URL . "?user=" . urlencode($userKey) . "&cpf=" . urlencode($cpf);

// Log para debug (opcional - remover em produção)
error_log("🔍 [CPF PROXY] Consultando API: " . $apiUrl);

// Fazer requisição para API de CPF
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false, // API apela-api.tech pode não ter certificado válido
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Verificar erros do cURL
if ($response === false || !empty($curlError)) {
    $errorDetails = $curlError ?: 'Erro desconhecido no cURL';
    error_log("❌ [CPF PROXY] Erro cURL: " . $errorDetails);
    error_log("❌ [CPF PROXY] URL tentada: " . $apiUrl);
    error_log("❌ [CPF PROXY] Código HTTP: " . $httpCode);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao consultar API de CPF',
        'message' => 'Não foi possível conectar à API externa. Verifique sua conexão ou tente novamente mais tarde.',
        'details' => $errorDetails,
        'url' => $apiUrl
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar código HTTP
if ($httpCode !== 200) {
    error_log("❌ [CPF PROXY] HTTP Error: " . $httpCode);
    error_log("❌ [CPF PROXY] Resposta recebida: " . substr($response, 0, 500));
    error_log("❌ [CPF PROXY] URL consultada: " . $apiUrl);
    
    // Tentar decodificar resposta para obter mensagem de erro
    $errorMessage = 'A API retornou um erro. Código: ' . $httpCode;
    if (!empty($response)) {
        $errorData = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($errorData['message'])) {
            $errorMessage = $errorData['message'];
        } elseif (json_last_error() === JSON_ERROR_NONE && isset($errorData['error'])) {
            $errorMessage = $errorData['error'];
        }
    }
    
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'error' => 'Erro na API de CPF',
        'message' => $errorMessage,
        'http_code' => $httpCode,
        'url' => $apiUrl
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar se a resposta é JSON válido
$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $jsonError = json_last_error_msg();
    $responsePreview = substr($response, 0, 500);
    error_log("❌ [CPF PROXY] Erro ao decodificar JSON: " . $jsonError);
    error_log("❌ [CPF PROXY] Resposta recebida (primeiros 500 chars): " . $responsePreview);
    error_log("❌ [CPF PROXY] Tamanho da resposta: " . strlen($response) . " bytes");
    error_log("❌ [CPF PROXY] URL consultada: " . $apiUrl);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Resposta inválida da API',
        'message' => 'A API retornou uma resposta em formato inválido. Tente novamente mais tarde.',
        'details' => $jsonError,
        'response_preview' => $responsePreview
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Log da estrutura recebida para debug
error_log("✅ [CPF PROXY] Resposta recebida: " . strlen($response) . " bytes");
error_log("📊 [CPF PROXY] Estrutura da resposta: " . json_encode(array_keys($data), JSON_UNESCAPED_UNICODE));

// Normalizar e padronizar a resposta da API apela-api.tech
// A API retorna: nome, nascimento, telefone, email, mae, etc.
$normalizedData = [];

// Função auxiliar para buscar valor em diferentes estruturas
function getValueFromData($data, $keys) {
    // Primeiro, tentar acessar diretamente na raiz
    foreach ($keys as $key) {
        if (isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null) {
            return $data[$key];
        }
    }
    
    // Depois, tentar dentro de Result
    if (isset($data['Result']) && is_array($data['Result'])) {
        foreach ($keys as $key) {
            if (isset($data['Result'][$key]) && $data['Result'][$key] !== '' && $data['Result'][$key] !== null) {
                return $data['Result'][$key];
            }
        }
    }
    
    // Tentar case-insensitive
    foreach ($keys as $key) {
        $keyLower = strtolower($key);
        foreach ($data as $dataKey => $dataValue) {
            if (strtolower($dataKey) === $keyLower && $dataValue !== '' && $dataValue !== null) {
                return $dataValue;
            }
        }
    }
    
    return '';
}

// Mapear campos da API apela-api.tech para formato padrão
// A API retorna: status, cpf, nome, nascimento, sexo, mae, requisicoes_restantes
$normalizedData['nome'] = getValueFromData($data, ['nome', 'Nome', 'name', 'full_name', 'nome_completo', 'NomePessoaFisica']);
$normalizedData['nascimento'] = getValueFromData($data, ['nascimento', 'DataNascimento', 'data_nascimento', 'birth_date', 'dataNascimento']);
$normalizedData['mae'] = getValueFromData($data, ['mae', 'nome_mae', 'NomeMae', 'mother_name', 'nomeMae', 'mae_nome']);
$normalizedData['pai'] = getValueFromData($data, ['pai', 'nome_pai', 'NomePai', 'father_name', 'nomePai', 'pai_nome']);
$normalizedData['sexo'] = getValueFromData($data, ['sexo', 'Sexo', 'gender', 'genero']);
$normalizedData['telefone'] = getValueFromData($data, ['telefone', 'Telefone', 'phone', 'telefone_principal']);
$normalizedData['email'] = getValueFromData($data, ['email', 'Email', 'email_address', 'email_principal']);
$normalizedData['localizacao'] = getValueFromData($data, ['localizacao', 'localização', 'municipio', 'MunicipioNascimento', 'municipio_nascimento', 'city', 'cidade']);
$normalizedData['escolaridade'] = getValueFromData($data, ['escolaridade', 'Escolaridade', 'education', 'escolaridade_nivel']);

// Converter formato de data de DD/MM/YYYY para YYYY-MM-DD se necessário
if (!empty($normalizedData['nascimento']) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $normalizedData['nascimento'])) {
    $parts = explode('/', $normalizedData['nascimento']);
    if (count($parts) === 3) {
        $normalizedData['nascimento'] = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
}

// Log dos dados normalizados
error_log("📊 [CPF PROXY] Dados normalizados: " . json_encode($normalizedData, JSON_UNESCAPED_UNICODE));

// Retornar resposta normalizada
http_response_code(200);
echo json_encode($normalizedData, JSON_UNESCAPED_UNICODE);
exit;
