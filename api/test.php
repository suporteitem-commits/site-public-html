<?php
/**
 * Script de teste para verificar se a API está funcionando
 * Acesse: https://seu-dominio.com/api/test.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de API - CPF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔍 Teste de Configuração da API de CPF</h1>
    
    <div class="test-box">
        <h2>1. Verificações do PHP</h2>
        <?php
        $checks = [];
        
        // Verificar versão do PHP
        $phpVersion = phpversion();
        $checks['PHP Version'] = [
            'status' => version_compare($phpVersion, '7.0.0', '>='),
            'message' => "Versão: $phpVersion " . (version_compare($phpVersion, '7.0.0', '>=') ? '✅' : '❌ (Requer PHP 7.0+)')
        ];
        
        // Verificar cURL
        $checks['cURL'] = [
            'status' => function_exists('curl_version'),
            'message' => function_exists('curl_version') ? '✅ Habilitado' : '❌ NÃO habilitado'
        ];
        
        // Verificar JSON
        $checks['JSON'] = [
            'status' => function_exists('json_encode'),
            'message' => function_exists('json_encode') ? '✅ Habilitado' : '❌ NÃO habilitado'
        ];
        
        // Verificar arquivo da API
        $apiFile = __DIR__ . '/consultar-cpf.php';
        $checks['Arquivo API'] = [
            'status' => file_exists($apiFile),
            'message' => file_exists($apiFile) ? '✅ Existe' : '❌ NÃO encontrado'
        ];
        
        // Verificar .htaccess
        $htaccessFile = dirname(__DIR__) . '/.htaccess';
        $checks['.htaccess'] = [
            'status' => file_exists($htaccessFile),
            'message' => file_exists($htaccessFile) ? '✅ Existe' : '⚠️ Não encontrado (pode ser normal)'
        ];
        
        foreach ($checks as $name => $check) {
            $class = $check['status'] ? 'success' : 'error';
            echo "<p><strong>$name:</strong> <span class='$class'>{$check['message']}</span></p>";
        }
        ?>
    </div>
    
    <div class="test-box">
        <h2>2. Teste da API de CPF</h2>
        <p>Testando consulta de CPF...</p>
        <?php
        $testCpf = '11653188812';
        $apiUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/api/consultar-cpf?cpf=' . $testCpf;
        
        echo "<p><strong>URL testada:</strong> <code>$apiUrl</code></p>";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            echo "<p class='error'>❌ Erro cURL: $curlError</p>";
        } else {
            echo "<p class='success'>✅ Requisição enviada com sucesso</p>";
            echo "<p><strong>Código HTTP:</strong> $httpCode</p>";
            
            if ($httpCode === 200) {
                echo "<p class='success'>✅ API respondeu com sucesso!</p>";
                $data = json_decode($response, true);
                if ($data) {
                    echo "<h3>Resposta da API:</h3>";
                    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                } else {
                    echo "<p class='warning'>⚠️ Resposta não é JSON válido</p>";
                    echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
                }
            } else {
                echo "<p class='error'>❌ API retornou erro HTTP $httpCode</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
        }
        ?>
    </div>
    
    <div class="test-box">
        <h2>3. Informações do Servidor</h2>
        <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido'; ?></p>
        <p><strong>Document Root:</strong> <code><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Desconhecido'; ?></code></p>
        <p><strong>Script Path:</strong> <code><?php echo __FILE__; ?></code></p>
    </div>
    
    <div class="test-box">
        <h2>4. Próximos Passos</h2>
        <ol>
            <li>Se todos os testes passaram ✅, a API está funcionando!</li>
            <li>Se houver erros ❌, verifique:
                <ul>
                    <li>Se o arquivo <code>api/consultar-cpf.php</code> foi enviado corretamente</li>
                    <li>Se o cURL está habilitado no PHP</li>
                    <li>Se o token da API está correto no arquivo PHP</li>
                    <li>Os logs de erro do servidor</li>
                </ul>
            </li>
            <li>Teste a página de validação:
                <code>/validar-dados/?cpf=11653188812</code>
            </li>
        </ol>
    </div>
    
    <p style="text-align: center; margin-top: 30px; color: #666;">
        <small>Este arquivo pode ser removido após os testes</small>
    </p>
</body>
</html>



