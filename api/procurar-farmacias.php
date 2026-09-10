<?php
/**
 * API para buscar farmácias próximas por CEP ou endereço
 * Endpoint: /api/procurar-farmacias?address=CEP&radius=15000
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Obter parâmetros
$address = isset($_GET['address']) ? trim($_GET['address']) : '';
$radius = isset($_GET['radius']) ? intval($_GET['radius']) : 15000; // Raio em metros (padrão 15km)

if (empty($address)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Endereço ou CEP não fornecido',
        'message' => 'Por favor, informe o endereço ou CEP na requisição'
    ]);
    exit;
}

// Remover formatação do CEP (apenas números)
$cep = preg_replace('/\D/', '', $address);

// Se for um CEP válido (8 dígitos), buscar coordenadas via ViaCEP
if (strlen($cep) === 8) {
    try {
        // Buscar dados do CEP via ViaCEP
        $viaCepUrl = "https://viacep.com.br/ws/{$cep}/json/";
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $viaCepUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $cepResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $cepData = json_decode($cepResponse, true);
            if ($cepData && !isset($cepData['erro'])) {
                // Montar endereço completo para busca
                $address = "{$cepData['logradouro']}, {$cepData['bairro']}, {$cepData['localidade']} - {$cepData['uf']}, {$cep}";
                error_log("📍 [FARMACIAS] CEP encontrado: {$cepData['localidade']} - {$cepData['uf']}");
            }
        }
    } catch (Exception $e) {
        error_log("❌ [FARMACIAS] Erro ao buscar CEP: " . $e->getMessage());
    }
}

// Buscar farmácias próximas
try {
    $pharmacies = buscarFarmaciasProximas($address, $radius);
    
    if (empty($pharmacies)) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => [
                'pharmacies' => [],
                'message' => 'Nenhuma farmácia encontrada próxima ao endereço informado'
            ]
        ]);
        exit;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'pharmacies' => $pharmacies,
            'count' => count($pharmacies),
            'address' => $address
        ]
    ]);
    
} catch (Exception $e) {
    error_log("❌ [FARMACIAS] Erro: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar farmácias',
        'message' => $e->getMessage()
    ]);
}

/**
 * Buscar farmácias próximas ao endereço
 */
function buscarFarmaciasProximas($address, $radius) {
    // Lista de farmácias credenciadas por região (mock data)
    // Em produção, você pode integrar com Google Places API ou outra API de farmácias
    
    $farmaciasCredenciadas = obterFarmaciasCredenciadas();
    
    // Se tiver Google Places API Key configurada, usar ela
    $googleApiKey = getenv('GOOGLE_PLACES_API_KEY') ?: '';
    
    if (!empty($googleApiKey)) {
        return buscarViaGooglePlaces($address, $radius, $googleApiKey);
    }
    
    // Caso contrário, retornar farmácias mockadas baseadas na região
    return filtrarFarmaciasPorRegiao($address, $farmaciasCredenciadas);
}

/**
 * Buscar via Google Places API
 */
function buscarViaGooglePlaces($address, $radius, $apiKey) {
    // Geocodificar endereço para obter coordenadas
    $geocodeUrl = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key={$apiKey}";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $geocodeUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $geocodeResponse = curl_exec($ch);
    $geocodeData = json_decode($geocodeResponse, true);
    curl_close($ch);
    
    if (!$geocodeData || $geocodeData['status'] !== 'OK' || empty($geocodeData['results'])) {
        throw new Exception('Não foi possível geocodificar o endereço');
    }
    
    $location = $geocodeData['results'][0]['geometry']['location'];
    $lat = $location['lat'];
    $lng = $location['lng'];
    
    // Buscar farmácias próximas
    $placesUrl = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location={$lat},{$lng}&radius={$radius}&type=pharmacy&keyword=farmácia&key={$apiKey}";
    
    $ch2 = curl_init();
    curl_setopt_array($ch2, [
        CURLOPT_URL => $placesUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $placesResponse = curl_exec($ch2);
    $placesData = json_decode($placesResponse, true);
    curl_close($ch2);
    
    if (!$placesData || $placesData['status'] !== 'OK') {
        throw new Exception('Erro ao buscar farmácias: ' . ($placesData['error_message'] ?? 'Erro desconhecido'));
    }
    
    $pharmacies = [];
    foreach ($placesData['results'] as $place) {
        $pharmacies[] = [
            'place_id' => $place['place_id'],
            'name' => $place['name'],
            'vicinity' => $place['vicinity'] ?? '',
            'formatted_address' => $place['formatted_address'] ?? '',
            'rating' => $place['rating'] ?? null,
            'geometry' => $place['geometry'] ?? null,
            'photo_urls' => isset($place['photos']) ? array_map(function($photo) use ($apiKey) {
                return "https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference={$photo['photo_reference']}&key={$apiKey}";
            }, array_slice($place['photos'], 0, 3)) : []
        ];
    }
    
    return $pharmacies;
}

/**
 * Filtrar farmácias mockadas por região
 */
function filtrarFarmaciasPorRegiao($address, $farmacias) {
    // Extrair cidade/estado do endereço
    $cidade = '';
    $estado = '';
    
    // Tentar extrair cidade e estado do endereço
    if (preg_match('/([A-Za-z\s]+)\s*-\s*([A-Z]{2})/i', $address, $matches)) {
        $cidade = trim($matches[1]);
        $estado = strtoupper($matches[2]);
    }
    
    // Filtrar farmácias da mesma cidade/estado
    $farmaciasFiltradas = [];
    foreach ($farmacias as $farmacia) {
        if (empty($cidade) || stripos($farmacia['cidade'], $cidade) !== false || 
            empty($estado) || $farmacia['estado'] === $estado) {
            $farmaciasFiltradas[] = [
                'place_id' => 'mock_' . uniqid(),
                'name' => $farmacia['nome'],
                'vicinity' => $farmacia['endereco'],
                'formatted_address' => "{$farmacia['endereco']}, {$farmacia['cidade']} - {$farmacia['estado']}",
                'rating' => 4.5,
                'geometry' => [
                    'location' => [
                        'lat' => $farmacia['lat'] ?? -23.5505,
                        'lng' => $farmacia['lng'] ?? -46.6333
                    ]
                ],
                'photo_urls' => []
            ];
        }
    }
    
    // Limitar a 5 farmácias
    return array_slice($farmaciasFiltradas, 0, 5);
}

/**
 * Obter lista de farmácias credenciadas (mock)
 * Em produção, isso viria de um banco de dados
 */
function obterFarmaciasCredenciadas() {
    return [
        // São Paulo - SP
        ['nome' => 'Farmácia Popular Central', 'endereco' => 'Rua Augusta, 123', 'cidade' => 'São Paulo', 'estado' => 'SP', 'lat' => -23.5505, 'lng' => -46.6333],
        ['nome' => 'Drogaria Saúde', 'endereco' => 'Av. Paulista, 1000', 'cidade' => 'São Paulo', 'estado' => 'SP', 'lat' => -23.5614, 'lng' => -46.6566],
        ['nome' => 'Farmácia Bem Estar', 'endereco' => 'Rua Consolação, 500', 'cidade' => 'São Paulo', 'estado' => 'SP', 'lat' => -23.5489, 'lng' => -46.6388],
        
        // Rio de Janeiro - RJ
        ['nome' => 'Farmácia Carioca', 'endereco' => 'Av. Atlântica, 200', 'cidade' => 'Rio de Janeiro', 'estado' => 'RJ', 'lat' => -22.9711, 'lng' => -43.1822],
        ['nome' => 'Drogaria Copacabana', 'endereco' => 'Rua Barata Ribeiro, 300', 'cidade' => 'Rio de Janeiro', 'estado' => 'RJ', 'lat' => -22.9707, 'lng' => -43.1865],
        
        // Belo Horizonte - MG
        ['nome' => 'Farmácia Mineira', 'endereco' => 'Av. Afonso Pena, 1000', 'cidade' => 'Belo Horizonte', 'estado' => 'MG', 'lat' => -19.9167, 'lng' => -43.9345],
        
        // Curitiba - PR
        ['nome' => 'Farmácia Paranaense', 'endereco' => 'Rua XV de Novembro, 500', 'cidade' => 'Curitiba', 'estado' => 'PR', 'lat' => -25.4284, 'lng' => -49.2733],
        
        // Porto Alegre - RS
        ['nome' => 'Drogaria Gaúcha', 'endereco' => 'Av. Borges de Medeiros, 200', 'cidade' => 'Porto Alegre', 'estado' => 'RS', 'lat' => -30.0346, 'lng' => -51.2177],
        
        // Brasília - DF
        ['nome' => 'Farmácia Federal', 'endereco' => 'SQN 305, Bloco A', 'cidade' => 'Brasília', 'estado' => 'DF', 'lat' => -15.7942, 'lng' => -47.8822],
        
        // Salvador - BA
        ['nome' => 'Farmácia Baiana', 'endereco' => 'Av. Sete de Setembro, 100', 'cidade' => 'Salvador', 'estado' => 'BA', 'lat' => -12.9714, 'lng' => -38.5014],
        
        // Recife - PE
        ['nome' => 'Drogaria Pernambucana', 'endereco' => 'Rua do Bom Jesus, 200', 'cidade' => 'Recife', 'estado' => 'PE', 'lat' => -8.0476, 'lng' => -34.8770],
        
        // Fortaleza - CE
        ['nome' => 'Farmácia Cearense', 'endereco' => 'Av. Beira Mar, 300', 'cidade' => 'Fortaleza', 'estado' => 'CE', 'lat' => -3.7172, 'lng' => -38.5433],
    ];
}



