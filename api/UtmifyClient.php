<?php
/**
 * UtmifyClient - Cliente para integração com API de Rastreamento Utmify
 * 
 * @author Backend Specialist Agent
 * @version 1.0
 */

class UtmifyClient
{
    // Configurações
    private const API_URL = 'https://api.utmify.com.br/api-credentials/orders';

    // ⚠️ SUBSTITUA PELO SEU TOKEN REAL DA UTMIFY
    // Você pode obter em: Integrações > Webhooks > Credenciais de API
    private const API_TOKEN = 'Ojm2k6unJVlk1NYttiCPYwoQprAgBiu9J9XN'; // Ex: KVRxalfMiBfm8Rm1nP5YxfwYzArNsA0VLeWC

    /**
     * Envia um evento de pedido para a Utmify
     * 
     * @param array $orderData Dados do pedido formatados conforme documentação
     * @return array Resposta da API
     */
    public static function sendOrder(array $orderData)
    {
        // Validar token
        if (self::API_TOKEN === 'SEU_TOKEN_AQUI') {
            error_log("❌ [UTMIFY] Token de API não configurado. Edite api/UtmifyClient.php");
            return ['success' => false, 'error' => 'Token não configurado'];
        }

        // Headers obrigatórios
        $headers = [
            'Content-Type: application/json',
            'x-api-token: ' . self::API_TOKEN
        ];

        // Inicializar cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($orderData),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true // Em produção manter true
        ]);

        // Executar
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Logging
        if ($error) {
            error_log("❌ [UTMIFY] Erro de conexão: " . $error);
            return ['success' => false, 'error' => $error];
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            error_log("✅ [UTMIFY] Pedido enviado com sucesso (Status: $httpCode)");
            return ['success' => true, 'response' => $responseData];
        } else {
            error_log("⚠️ [UTMIFY] Erro API ($httpCode): " . substr($response, 0, 200));
            return ['success' => false, 'http_code' => $httpCode, 'response' => $responseData];
        }
    }

    /**
     * Constrói o payload padrão a partir dos dados do pagamento
     * 
     * @param array $paymentData Dados brutos do pagamento
     * @param string $status 'waiting_payment' | 'paid' | 'refused' | 'refunded'
     * @param string $transactionId ID da transação
     * @return array Payload formatado
     */
    public static function buildPayload(array $paymentData, string $status, string $transactionId)
    {
        $now = gmdate('Y-m-d H:i:s'); // UTC

        // Converter valor para centavos
        $amount = isset($paymentData['amount']) ? (float) $paymentData['amount'] : 0;
        $amountCents = (int) round($amount * 100);

        // Produto padrão
        $productName = $paymentData['product'] ?? 'Produto Desconhecido';

        return [
            'orderId' => $transactionId,
            'platform' => 'Propria', // Nome da sua plataforma
            'paymentMethod' => 'pix',
            'status' => $status,
            'createdAt' => $paymentData['created_at'] ?? $now,
            'approvedDate' => ($status === 'paid') ? $now : null,
            'refundedAt' => null,
            'customer' => [
                'name' => $paymentData['name'] ?? 'Cliente',
                'email' => $paymentData['email'] ?? 'email@exemplo.com',
                'phone' => $paymentData['phone'] ?? null,
                'document' => preg_replace('/\D/', '', $paymentData['cpf'] ?? ''),
                'country' => 'BR',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ],
            'products' => [
                [
                    'id' => 'PROD-001', // ID fixo ou dinâmico
                    'name' => $productName,
                    'planId' => null,
                    'planName' => null,
                    'quantity' => 1,
                    'priceInCents' => $amountCents
                ]
            ],
            'trackingParameters' => [
                'src' => $paymentData['utm_src'] ?? null,
                'sck' => $paymentData['utm_sck'] ?? null,
                'utm_source' => $paymentData['utm_source'] ?? null,
                'utm_campaign' => $paymentData['utm_campaign'] ?? null,
                'utm_medium' => $paymentData['utm_medium'] ?? null,
                'utm_content' => $paymentData['utm_content'] ?? null,
                'utm_term' => $paymentData['utm_term'] ?? null
            ],
            'commission' => [
                'totalPriceInCents' => $amountCents,
                'gatewayFeeInCents' => 0, // Ajuste conforme necessário
                'userCommissionInCents' => $amountCents // Assumindo comissão total
            ],
            'isTest' => false // Mude para true se quiser testar sem salvar
        ];
    }
}
?>