Checkout API
A API de Pagamentos permite criar, consultar e estornar pagamentos de forma segura e eficiente. Ela foi desenvolvida para simplificar a integração de sistemas com o processamento de transações financeiras, oferecendo endpoints claros para a criação de novos pagamentos, consulta de detalhes de pagamentos existentes e processamento de estornos.

Como obter API Key
Para obter sua chave de API, consulte seu gestor.

Como obter o Store Code
Para obter o Store Code acesse o Painel Administrativo > Menu na lateral esquerda > Checkout Api e clique em "Criar" caso não tenha um.

Pagamentos
Endpoints para criação, consulta e estorno de pagamentos

Buscar pagamento
Obtém os detalhes de um pagamento específico pelo código.

Status de pagamento
approved: Transação aprovada com sucesso.
pending: Transação pendente de confirmação.
refunded: Transação estornada.
error: Houve um erro no processamento do pagamento.
Authorizations:
(ApiKeyAuthStoreCodeAuth)
path Parameters
paymentCode
required
string
Código do pagamento

Responses
200 Detalhes do pagamento
404 Pagamento não encontrado

get
/api/v1/payment/{paymentCode}
Request samples
PHPJavaScriptPython

Copy
$client = new \GuzzleHttp\Client();
$url = 'https://checkout.mangofy.com.br/api/v1/payment/AAAAAAAAAA';
$response = $client->get($url, [
    'headers' => [
        'Authorization' => '2980d79d0a69fb12a0a0ee3be31f4cb9fwhgjdhm0ertokhggoe4ydm1lrrg43f',
        'Store-Code'   => 'vstmz4tr',
        'Content-Type' => 'application/json',
        'Accept'       => 'application/json',
    ],
]);
$body = $response->getBody();
print_r(json_decode((string) $body));
Response samples
200404
Content type
application/json

Copy
Expand allCollapse all
{
"payment_code": "vpaj2qy09l",
"payment_method": "credit_card",
"payment_status": "approved",
"payment_amount": 500,
"sale_amount": 500,
"shipping_amount": 0,
"installments": 1,
"installment_amount": 500,
"card": {
"card_rejected": true,
"card_message": "string",
"card_flag": "visa",
"card_first_six_digits": "503143",
"card_last_four_digits": "6351",
"card_token": "8358c10af5464f7e3ba1aee829b0079f0aceaabc099b112d39e8b7e001325a10",
"card_soft_descriptor": "primeiraloja"
}
}
Gerar pagamento
Cria uma solicitação de pagamento com base nas informações fornecidas, como o valor da compra, moeda, método de pagamento e detalhes do comprador. A resposta inclui um ID único para o pagamento (PAYMENT_CODE), que pode ser utilizado para acompanhar o status da transação ou realizar outras operações, como estornos.

Authorizations:
(ApiKeyAuthStoreCodeAuth)
Request Body schema: application/json
required
store_code
required
string <= 100 characters
external_code	
string or null <= 100 characters
Identificador interno definido pela empresa integradora. Formato livre, utilizado para rastrear e referenciar transações em seu sistema.

payment_method
required
string
Enum: "credit_card" "billet" "pix"
payment_format
required
string
Enum: "regular" "orderbump" "upsell"
installments
required
integer [ 1 .. 12 ]
Número de parcelas.

payment_amount
required
integer [ 500 .. 2000000 ]
Valor total do pagamento em centavos.

shipping_amount	
integer or null [ 0 .. 2000000 ]
Valor total do frete em centavos.

postback_url	
string or null <= 1000 characters
URL para notificação.

items
required
Array of objects or null
customer
required
object
card	
object or null
Campo obrigatório quando o campo payment_method for credit_card.

pix	
object or null
Campo obrigatório quando o campo payment_method for pix.

billet	
object or null
Campo obrigatório quando o campo payment_method for billet.

shipping	
object or null
extra	
object or null
Responses
200 Pagamento criado com sucesso
4XX Erro na requisição

post
/api/v1/payment
Request samples
PayloadPHPJavaScriptPython
Content type
application/json

Copy
Expand allCollapse all
{
"store_code": "string",
"external_code": "string",
"payment_method": "credit_card",
"payment_format": "regular",
"installments": 1,
"payment_amount": 500,
"shipping_amount": 2000000,
"postback_url": "string",
"items": [
{}
],
"customer": {
"email": "user@example.com",
"name": "string",
"document": "stringstrin",
"phone": "string",
"ip": "string"
},
"card": {
"token": "string",
"number": "string",
"holder_name": "string",
"expiration_month": 1,
"expiration_year": 2024,
"cvv": "stri",
"soft_descriptor": "string"
},
"pix": {
"expires_in_days": 1
},
"billet": {
"expires_in_days": 1
},
"shipping": {
"street": "string",
"street_number": "string",
"complement": "string",
"neighborhood": "string",
"city": "string",
"state": "string",
"zip_code": "string",
"country": "string"
},
"extra": {
"userAgent": "string",
"browser": "string",
"os": "string",
"device": "string",
"browser_fingerprint": "string",
"cybersource_fingerprint": "string",
"seon_fingerprint": "string",
"url_referer": "string",
"url_full": "string",
"metadata": { }
}
}
Response samples
2004XX
Content type
application/json

Copy
Expand allCollapse all
{
"payment_code": "vpaj2qy09l",
"payment_method": "credit_card",
"payment_status": "approved",
"payment_amount": 500,
"sale_amount": 500,
"shipping_amount": 0,
"installments": 1,
"installment_amount": 500,
"card": {
"card_rejected": true,
"card_message": "string",
"card_flag": "visa",
"card_first_six_digits": "503143",
"card_last_four_digits": "6351",
"card_token": "8358c10af5464f7e3ba1aee829b0079f0aceaabc099b112d39e8b7e001325a10",
"card_soft_descriptor": "primeiraloja"
}
}
Devolver pagamento
Processa a devolução (estorno) de um pagamento previamente realizado. Ele recebe o paymentCode do pagamento original e realiza a devolução total do valor. Utilizado em casos de cancelamento de compra, reembolso ou disputas. Assim que for efetivada a devolução, será enviado um postback com o novo status refunded.

Authorizations:
(ApiKeyAuthStoreCodeAuth)
path Parameters
paymentCode
required
string
Responses
200 Cancelamento solicitado com sucesso
404 Pagamento não encontrado
409 Cancelamento não permitido

post
/api/v1/payment/refund/{paymentCode}
Request samples
PHPJavaScriptPython

Copy
$client = new \GuzzleHttp\Client();
$url = 'https://checkout.mangofy.com.br/api/v1/payment/refund/PPPPPPPPPPPPP';
$response = $client->post($url, [
    'headers' => [
        'Authorization' => '2980d79d0a69fb12a0a0ee3be31f4cb9fwhgjdhm0ertokhggoe4ydm1lrrg43f',
        'Content-Type'  => 'application/json',
        'Accept'        => 'application/json',
    ],
]);
print_r(json_decode((string) $response->getBody()));
Response samples
200404409
Content type
application/json

Copy
{
"message": "Cancelamento solicitado"
}
Webhooks
A cada atualização no status de uma venda, uma requisição POST será enviada para a URL
especificada em postback_url, contendo todas as informações atualizadas sobre a transação.
Em caso de falhas, haverá até 4 tentativas adicionais em intervalos crescentes.

Segunda tentativa: 4 minutos,
Terceira tentativa: 16 minutos,
Quarta tentativa: 64 minutos
Última tentativa: 256 minutos.
Exemplo de webhook de uma venda pix aprovada:

   {
      "payment_code": "PPPPPPPPPPPPP",
      "external_code": "AAAAAAAAAAA",
      "payment_method": "pix",
      "payment_status": "approved"
      "created_at": "1970-01-01 00:00:00",
      "updated_at" : "1970-01-01 00:00:00",
      "approved_at": "1970-01-01 00:00:00",
      "refunded_at": "1970-01-01 00:00:00",
   }