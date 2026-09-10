<?php
/**
 * Script de teste para diagnosticar problemas com a API CPF
 * Acesse: /api/test-cpf-api.php?cpf=11653188812
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste API CPF - Diagnóstico</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }
        .success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Teste de Diagnóstico - API CPF</h1>
        
        <?php
        $cpf = isset($_GET['cpf']) ? preg_replace('/\D/', '', $_GET['cpf']) : '11653188812';
        
        echo "<div class='test-section'>";
        echo "<h2>1. Informações do Teste</h2>";
        echo "<p><strong>CPF testado:</strong> " . htmlspecialchars($cpf) . "</p>";
        echo "<p><strong>URL da API:</strong> <code>/api/consultar-cpf?cpf={$cpf}</code></p>";
        echo "</div>";
        
        // Teste 1: Verificar se cURL está disponível
        echo "<div class='test-section " . (function_exists('curl_init') ? 'success' : 'error') . "'>";
        echo "<h2>2. Verificação do cURL</h2>";
        if (function_exists('curl_init')) {
            echo "<p>✅ cURL está disponível</p>";
            $curlVersion = curl_version();
            echo "<p><strong>Versão do cURL:</strong> " . $curlVersion['version'] . "</p>";
        } else {
            echo "<p>❌ cURL NÃO está disponível. Instale a extensão cURL do PHP.</p>";
        }
        echo "</div>";
        
        // Teste 2: Testar conexão com a API
        if (function_exists('curl_init')) {
            echo "<div class='test-section'>";
            echo "<h2>3. Teste de Conexão com API Externa</h2>";
            
            $userKey = '1da00e5758d36202cc77bc44d10e928f';
            $apiUrl = "https://apela-api.tech/?user=" . urlencode($userKey) . "&cpf=" . urlencode($cpf);
            
            echo "<p><strong>URL da API Externa:</strong> <code>" . htmlspecialchars($apiUrl) . "</code></p>";
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]);
            
            $startTime = microtime(true);
            $response = curl_exec($ch);
            $endTime = microtime(true);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);
            
            $responseTime = round(($endTime - $startTime) * 1000, 2);
            
            if ($response === false || !empty($curlError)) {
                echo "<div class='error'>";
                echo "<p>❌ <strong>Erro na conexão:</strong></p>";
                echo "<p><strong>Código do erro:</strong> {$curlErrno}</p>";
                echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($curlError) . "</p>";
                echo "</div>";
            } else {
                echo "<div class='success'>";
                echo "<p>✅ <strong>Conexão estabelecida com sucesso!</strong></p>";
                echo "<p><strong>Código HTTP:</strong> {$httpCode}</p>";
                echo "<p><strong>Tempo de resposta:</strong> {$responseTime}ms</p>";
                echo "</div>";
                
                if ($httpCode !== 200) {
                    echo "<div class='warning'>";
                    echo "<p>⚠️ <strong>Atenção:</strong> A API retornou código HTTP {$httpCode} (esperado: 200)</p>";
                    echo "</div>";
                }
                
                echo "<h3>Resposta da API:</h3>";
                echo "<pre>" . htmlspecialchars(substr($response, 0, 2000)) . "</pre>";
                
                // Tentar decodificar JSON
                $jsonData = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    echo "<div class='success'>";
                    echo "<p>✅ Resposta é um JSON válido</p>";
                    echo "<h3>Dados decodificados:</h3>";
                    echo "<pre>" . htmlspecialchars(json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
                    echo "</div>";
                } else {
                    echo "<div class='error'>";
                    echo "<p>❌ Resposta NÃO é um JSON válido</p>";
                    echo "<p><strong>Erro:</strong> " . json_last_error_msg() . "</p>";
                    echo "</div>";
                }
            }
            echo "</div>";
            
            // Teste 3: Testar o endpoint local
            echo "<div class='test-section'>";
            echo "<h2>4. Teste do Endpoint Local (/api/consultar-cpf)</h2>";
            
            $localUrl = "/api/consultar-cpf?cpf=" . urlencode($cpf);
            $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                       "://" . $_SERVER['HTTP_HOST'] . $localUrl;
            
            echo "<p><strong>URL local:</strong> <code>" . htmlspecialchars($fullUrl) . "</code></p>";
            
            $ch2 = curl_init();
            curl_setopt_array($ch2, [
                CURLOPT_URL => $fullUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);
            
            $startTime2 = microtime(true);
            $response2 = curl_exec($ch2);
            $endTime2 = microtime(true);
            $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            $curlError2 = curl_error($ch2);
            curl_close($ch2);
            
            $responseTime2 = round(($endTime2 - $startTime2) * 1000, 2);
            
            if ($response2 === false || !empty($curlError2)) {
                echo "<div class='error'>";
                echo "<p>❌ <strong>Erro ao acessar endpoint local:</strong></p>";
                echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($curlError2) . "</p>";
                echo "</div>";
            } else {
                echo "<div class='success'>";
                echo "<p>✅ <strong>Endpoint local acessível!</strong></p>";
                echo "<p><strong>Código HTTP:</strong> {$httpCode2}</p>";
                echo "<p><strong>Tempo de resposta:</strong> {$responseTime2}ms</p>";
                echo "</div>";
                
                echo "<h3>Resposta do endpoint local:</h3>";
                echo "<pre>" . htmlspecialchars(substr($response2, 0, 2000)) . "</pre>";
                
                // Tentar decodificar JSON
                $jsonData2 = json_decode($response2, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    echo "<div class='success'>";
                    echo "<p>✅ Resposta é um JSON válido</p>";
                    if (isset($jsonData2['success']) && $jsonData2['success'] === false) {
                        echo "<div class='error'>";
                        echo "<p>❌ <strong>API retornou erro:</strong></p>";
                        echo "<p><strong>Erro:</strong> " . htmlspecialchars($jsonData2['error'] ?? 'Desconhecido') . "</p>";
                        echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($jsonData2['message'] ?? 'Sem mensagem') . "</p>";
                        if (isset($jsonData2['details'])) {
                            echo "<p><strong>Detalhes:</strong> " . htmlspecialchars($jsonData2['details']) . "</p>";
                        }
                        echo "</div>";
                    } else {
                        echo "<h3>Dados normalizados:</h3>";
                        echo "<pre>" . htmlspecialchars(json_encode($jsonData2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
                    }
                    echo "</div>";
                } else {
                    echo "<div class='error'>";
                    echo "<p>❌ Resposta NÃO é um JSON válido</p>";
                    echo "<p><strong>Erro:</strong> " . json_last_error_msg() . "</p>";
                    echo "</div>";
                }
            }
            echo "</div>";
        }
        
        // Teste 4: Informações do servidor
        echo "<div class='test-section'>";
        echo "<h2>5. Informações do Servidor</h2>";
        echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
        echo "<p><strong>Servidor:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido') . "</p>";
        echo "<p><strong>Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Desconhecido') . "</p>";
        echo "<p><strong>Protocolo:</strong> " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'HTTPS' : 'HTTP') . "</p>";
        echo "</div>";
        ?>
        
        <div class="test-section">
            <h2>6. Como usar este teste</h2>
            <p>Para testar com outro CPF, adicione o parâmetro na URL:</p>
            <p><code>?cpf=11653188812</code></p>
            <p><strong>Exemplo:</strong> <code>/api/test-cpf-api.php?cpf=11653188812</code></p>
        </div>
    </div>
</body>
</html>

