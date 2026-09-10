<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if (!isset($_GET['cpf'])) {
    echo json_encode(['error' => 'CPF não informado']);
    exit;
}

$cpf = preg_replace('/\D/', '', $_GET['cpf']);

if (strlen($cpf) !== 11) {
    echo json_encode(['error' => 'CPF inválido']);
    exit;
}

// Chave de usuário da API
$userKey = '1da00e5758d36202cc77bc44d10e928f';
$apiUrl = "https://apela-api.tech/?user={$userKey}&cpf={$cpf}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['error' => 'Erro na consulta: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['error' => 'Serviço temporariamente indisponível']);
    exit;
}

// Retorna o JSON puro da API
echo $response;
?>
