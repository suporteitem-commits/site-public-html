<?php
/**
 * Script de debug para verificar a estrutura da resposta da API de CPF
 * Acesse: https://seu-dominio.com/api/debug-cpf.php?cpf=11653188812
 */

header('Content-Type: text/html; charset=utf-8');

$cpf = isset($_GET['cpf']) ? preg_replace('/\D/', '', $_GET['cpf']) : '11653188812';
$API_TOKEN = "e3bd2312d93dca38d2003095196a09c2";
$API_URL = "https://apidecpf.site/api-v1/consultas.php?cpf={$cpf}&token={$API_TOKEN}";

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Debug API CPF</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <h1>🔍 Debug da API de CPF</h1>
    
    <div class="box">
        <h2>1. Informações da Requisição</h2>
        <p><strong>CPF:</strong> <?php echo $cpf; ?></p>
        <p><strong>URL da API:</strong> <code><?php echo htmlspecialchars($API_URL); ?></code></p>
    </div>
    
    <div class="box">
        <h2>2. Resposta da API</h2>
        <?php
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0'
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
                $data = json_decode($response, true);
                if ($data) {
                    echo "<h3>Estrutura da Resposta (JSON):</h3>";
                    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                    
                    echo "<h3>Chaves Disponíveis:</h3>";
                    echo "<pre>" . print_r(array_keys_recursive($data), true) . "</pre>";
                    
                    echo "<h3>Mapeamento de Campos:</h3>";
                    echo "<ul>";
                    echo "<li><strong>Nome:</strong> " . (isset($data['nome']) ? htmlspecialchars($data['nome']) : 'NÃO ENCONTRADO') . "</li>";
                    echo "<li><strong>Nome (Result.NomePessoaFisica):</strong> " . (isset($data['Result']['NomePessoaFisica']) ? htmlspecialchars($data['Result']['NomePessoaFisica']) : 'NÃO ENCONTRADO') . "</li>";
                    echo "<li><strong>Mãe:</strong> " . (isset($data['mae']) ? htmlspecialchars($data['mae']) : 'NÃO ENCONTRADO') . "</li>";
                    echo "<li><strong>Mãe (Result.NomeMae):</strong> " . (isset($data['Result']['NomeMae']) ? htmlspecialchars($data['Result']['NomeMae']) : 'NÃO ENCONTRADO') . "</li>";
                    echo "<li><strong>Nascimento:</strong> " . (isset($data['nascimento']) ? htmlspecialchars($data['nascimento']) : 'NÃO ENCONTRADO') . "</li>";
                    echo "<li><strong>Nascimento (Result.DataNascimento):</strong> " . (isset($data['Result']['DataNascimento']) ? htmlspecialchars($data['Result']['DataNascimento']) : 'NÃO ENCONTRADO') . "</li>";
                    echo "<li><strong>Sexo:</strong> " . (isset($data['sexo']) ? htmlspecialchars($data['sexo']) : 'NÃO ENCONTRADO') . "</li>";
                    echo "<li><strong>Localização:</strong> " . (isset($data['localizacao']) ? htmlspecialchars($data['localizacao']) : 'NÃO ENCONTRADO') . "</li>";
                    echo "</ul>";
                } else {
                    echo "<p class='error'>❌ Resposta não é JSON válido</p>";
                    echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre>";
                }
            } else {
                echo "<p class='error'>❌ API retornou erro HTTP $httpCode</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
        }
        
        function array_keys_recursive($array, $prefix = '') {
            $keys = [];
            foreach ($array as $key => $value) {
                $fullKey = $prefix ? "$prefix.$key" : $key;
                $keys[] = $fullKey;
                if (is_array($value)) {
                    $keys = array_merge($keys, array_keys_recursive($value, $fullKey));
                }
            }
            return $keys;
        }
        ?>
    </div>
    
    <div class="box">
        <h2>3. Teste do Proxy</h2>
        <?php
        $proxyUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/api/consultar-cpf?cpf=' . $cpf;
        echo "<p><strong>URL do Proxy:</strong> <code>$proxyUrl</code></p>";
        
        $ch2 = curl_init();
        curl_setopt_array($ch2, [
            CURLOPT_URL => $proxyUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ]);
        
        $proxyResponse = curl_exec($ch2);
        $proxyHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);
        
        if ($proxyHttpCode === 200) {
            $proxyData = json_decode($proxyResponse, true);
            echo "<p class='success'>✅ Proxy funcionando!</p>";
            echo "<pre>" . json_encode($proxyData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        } else {
            echo "<p class='error'>❌ Proxy retornou erro HTTP $proxyHttpCode</p>";
            echo "<pre>" . htmlspecialchars($proxyResponse) . "</pre>";
        }
        ?>
    </div>
</body>
</html>



