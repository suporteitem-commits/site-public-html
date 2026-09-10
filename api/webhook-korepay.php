<?php
/**
 * Webhook para receber notificações de pagamento da KorePay
 * e sincronizar com Utmify
 */

require_once 'UtmifyClient.php';

// Log inicio
error_log("🔔 [WEBHOOK] Recebido postback na API");

// Ler corpo
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("❌ [WEBHOOK] JSON invalido");
    http_response_code(400);
    exit;
}

// Log dos dados recebidos (útil para debug inicial)
error_log("📦 [WEBHOOK] Dados: " . substr($input, 0, 500));

// Verificar Transaction ID (KorePay geralmente envia 'id' ou 'transactionId')
$transactionId = $data['id'] ?? $data['transactionId'] ?? null;
$status = $data['status'] ?? null;

if (!$transactionId || !$status) {
    error_log("⚠️ [WEBHOOK] ID ou Status não encontrados");
    http_response_code(400); // Bad Request
    exit;
}

// Mapear status
$isPaid = in_array(strtolower($status), ['paid', 'completed', 'approved']);

if ($isPaid) {
    error_log("✅ [WEBHOOK] Pagamento APROVADO: $transactionId");

    // Preparar dados para Utmify
    // O Webhook da KorePay deve enviar os dados do cliente e produto?
    // Se não enviar, pode ser um problema pois Utmify exige.
    // Assumindo que o webhook envia structure similar à transação.

    // Tentar extrair dados do cliente do payload do webhook
    $customerName = $data['customer']['name'] ?? 'Cliente';
    $customerEmail = $data['customer']['email'] ?? 'email@naoinformado.com';
    $customerCpf = $data['customer']['document']['number'] ?? $data['customer']['document'] ?? '';

    // Tentar extrair UTMs (KorePay suporta metadata?)
    // Se a KorePay não devolver os metadata/custom_fields que enviamos (se enviamos),
    // a Utmify pode ficar sem UTM na conversão se não tivermos DB.
    // Tentar ler de metadata se existir
    // Tentar extrair UTMs
    // NOTA: Como enviamos metadata como JSON STRING para KorePay, precisamos decodificar aqui
    $rawMetadata = $data['metadata'] ?? null;
    $metadata = [];

    if (is_string($rawMetadata)) {
        // Tentar decodificar string JSON
        $decoded = json_decode($rawMetadata, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $metadata = $decoded;
        }
    } elseif (is_array($rawMetadata)) {
        $metadata = $rawMetadata;
    }

    // Fallback: tentar ler de customId se metadata falhar
    if (empty($metadata) && !empty($data['customId'])) {
        $decoded = json_decode($data['customId'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $metadata = $decoded;
        }
    }

    // Montar payload
    $utmifyPayload = [
        'amount' => isset($data['amount']) ? $data['amount'] / 100 : 0, // KorePay usa centavos geralmente
        'name' => $customerName,
        'email' => $customerEmail,
        'cpf' => $customerCpf,
        'product' => 'Mounjaro (via Webhook)', // Tentar melhorar se tiver items

        // UTMs - se não vier no webhook, infelizmente perderemos a origem na conversão
        // a menos que usamos a mesma strategy de "Pix Generated" que já enviou UTMs.
        // A Utmify deve ser capaz de atribuir se o orderId for o mesmo.
        'utm_source' => $metadata['utm_source'] ?? null,
        'utm_campaign' => $metadata['utm_campaign'] ?? null,
    ];

    try {
        error_log("🚀 [WEBHOOK] Enviando status PAID para Utmify...");

        // Importante: status 'paid'
        $payload = UtmifyClient::buildPayload($utmifyPayload, 'paid', $transactionId);

        // enviar approvedDate
        $payload['approvedDate'] = gmdate('Y-m-d H:i:s');

        $res = UtmifyClient::sendOrder($payload);
        error_log("🏁 [WEBHOOK] Resultado Utmify: " . json_encode($res));

    } catch (Exception $e) {
        error_log("❌ [WEBHOOK] Erro ao integrar Utmify: " . $e->getMessage());
    }

} else {
    error_log("ℹ️ [WEBHOOK] Status ignorado: $status");
}

http_response_code(200);
echo json_encode(['success' => true]);
