# 🚀 Integração Utmify - Instruções

A integração com a Utmify foi implementada no backend. Agora você precisa configurar o seu Token de API para que os eventos sejam enviados corretamente.

## 1. Configurar Token

1. Abra o arquivo `api/UtmifyClient.php`.
2. Localize a linha:
   ```php
   private const API_TOKEN = 'SEU_TOKEN_AQUI';
   ```
3. Substitua `'SEU_TOKEN_AQUI'` pela sua credencial da Utmify (obtida em Integrações > Webhooks > Credenciais de API).

## 2. Como Funciona

### Pix Gerado (Waiting Payment)
Quando o usuário gera um PIX no arquivo `api/processar_pagamento_mounjaro.php`:
1. O sistema captura os parâmetros UTM (utm_source, etc) enviados pelo frontend.
2. Envia imediatamente um evento para Utmify com status `waiting_payment`.
3. Anexa os parametos UTM como `metadata` na transação do gateway (KorePay) para uso posterior.

### Pix Pago (Paid)
O sistema foi configurado para usar um Webhook: `api/webhook-korepay.php`.
1. Quando a KorePay confirma o pagamento, ela chama este arquivo.
2. O arquivo lê o status `paid` (ou `completed`).
3. Recupera os parâmetros UTM que salvamos nos `metadata`.
4. Envia o evento de `approved/paid` para a Utmify.

## 3. Testando

### Localmente
Para testar o evento de **Pix Gerado**, basta tentar gerar um PIX na interface. Verifique o log do PHP (`error_log`) para ver:
`✅ [UTMIFY] Pedido enviado com sucesso`

Para testar o evento de **Pix Pago** localmente, você não receberá o webhook da KorePay (pois localhost não é acessível externamente). Você pode simular enviando um POST para seu webhook local:

```bash
curl -X POST http://localhost:8000/api/webhook-korepay.php \
-H "Content-Type: application/json" \
-d '{
    "id": "TRANSACAO_TESTE_123",
    "status": "paid",
    "amount": 10000,
    "customer": {
        "name": "Teste",
        "email": "teste@email.com",
        "document": "12345678900"
    },
    "metadata": {
        "utm_source": "teste_source"
    }
}'
```

### Em Produção
Certifique-se de que os arquivos `api/UtmifyClient.php` e `api/webhook-korepay.php` foram enviados para o servidor.
