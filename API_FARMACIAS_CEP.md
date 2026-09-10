# 🏥 API de Busca de Farmácias por CEP

## ✅ Implementação Concluída

Foi implementada uma solução completa para buscar farmácias credenciadas próximas ao CEP do usuário quando ele seleciona "Retirada na Rede Credenciada".

## 📁 Arquivos Criados

1. **`api/procurar-farmacias.php`** - API principal para buscar farmácias por CEP/endereço
2. **`api/pharmacy-api-key.php`** - Endpoint para retornar chave da API (opcional)
3. **`.htaccess`** - Atualizado com rotas para as novas APIs

## 🔧 Como Funciona

### 1. Fluxo do Usuário

1. Usuário preenche o endereço (incluindo CEP) na página de cadastro
2. Usuário chega na página de seleção (`/selecao`)
3. Usuário seleciona "Retirada na Rede Credenciada"
4. Sistema busca automaticamente farmácias próximas ao CEP
5. Lista de farmácias é exibida para seleção

### 2. Processo de Busca

```
CEP do Usuário
    ↓
ViaCEP API (obter coordenadas)
    ↓
Buscar Farmácias
    ↓
[Opção 1] Google Places API (se configurada)
    ↓
[Opção 2] Lista Mockada (fallback)
    ↓
Retornar Lista de Farmácias
```

## 🚀 Funcionalidades

### ✅ Busca por CEP
- Aceita CEP com ou sem formatação (12345-678 ou 12345678)
- Busca coordenadas via ViaCEP
- Filtra farmácias por região

### ✅ Busca por Endereço Completo
- Aceita endereço completo como fallback
- Extrai cidade e estado para filtrar

### ✅ Integração com Google Places (Opcional)
- Se você tiver uma chave do Google Places API, configure via variável de ambiente
- A API usará o Google Places para buscar farmácias reais
- Sem chave, usa lista mockada de farmácias credenciadas

### ✅ Lista Mockada (Fallback)
- Farmácias credenciadas por principais cidades brasileiras
- São Paulo, Rio de Janeiro, Belo Horizonte, Curitiba, etc.
- Filtra automaticamente por cidade/estado do CEP

## 📋 Endpoints da API

### GET `/api/procurar-farmacias`

**Parâmetros:**
- `address` (obrigatório): CEP ou endereço completo
- `radius` (opcional): Raio de busca em metros (padrão: 15000 = 15km)

**Exemplo:**
```
GET /api/procurar-farmacias?address=01310-100&radius=15000
```

**Resposta de Sucesso:**
```json
{
  "success": true,
  "data": {
    "pharmacies": [
      {
        "place_id": "mock_123456",
        "name": "Farmácia Popular Central",
        "vicinity": "Rua Augusta, 123",
        "formatted_address": "Rua Augusta, 123, São Paulo - SP",
        "rating": 4.5,
        "geometry": {
          "location": {
            "lat": -23.5505,
            "lng": -46.6333
          }
        },
        "photo_urls": []
      }
    ],
    "count": 1,
    "address": "01310-100"
  }
}
```

**Resposta de Erro:**
```json
{
  "success": false,
  "error": "Endereço ou CEP não fornecido",
  "message": "Por favor, informe o endereço ou CEP na requisição"
}
```

### GET `/api/pharmacy-api-key`

Retorna a chave da API do Google Places (se configurada) ou string vazia.

**Resposta:**
```json
{
  "success": true,
  "api_key": "",
  "message": "Usando farmácias mockadas (sem Google Places API)"
}
```

## ⚙️ Configuração

### Opção 1: Usar Lista Mockada (Padrão)

Não é necessária nenhuma configuração. A API já funciona com a lista mockada de farmácias.

### Opção 2: Usar Google Places API

1. Obtenha uma chave da Google Places API em: https://console.cloud.google.com/
2. Configure a variável de ambiente no servidor:
   ```bash
   export GOOGLE_PLACES_API_KEY="sua-chave-aqui"
   ```
3. Ou configure no `.htaccess` ou `php.ini` (dependendo do servidor)

**Nota:** O Google Places API tem custos. Verifique os preços antes de usar.

## 🧪 Testes

### Teste 1: Buscar Farmácias por CEP

```bash
curl "https://seu-dominio.com/api/procurar-farmacias?address=01310-100&radius=15000"
```

### Teste 2: Buscar Farmácias por Endereço

```bash
curl "https://seu-dominio.com/api/procurar-farmacias?address=São%20Paulo,%20SP&radius=15000"
```

### Teste 3: Verificar Chave da API

```bash
curl "https://seu-dominio.com/api/pharmacy-api-key"
```

## 📝 Adicionar Mais Farmácias Mockadas

Para adicionar mais farmácias à lista mockada, edite o arquivo `api/procurar-farmacias.php`:

```php
function obterFarmaciasCredenciadas() {
    return [
        // Adicione novas farmácias aqui
        [
            'nome' => 'Nome da Farmácia',
            'endereco' => 'Rua, Número',
            'cidade' => 'Nome da Cidade',
            'estado' => 'UF',
            'lat' => -23.5505,  // Latitude
            'lng' => -46.6333   // Longitude
        ],
        // ...
    ];
}
```

## 🔍 Debug

### Verificar Logs do Servidor

Os logs incluem informações sobre:
- CEPs consultados
- Farmácias encontradas
- Erros (se houver)

### Console do Navegador

Abra o console (F12) e verifique:
```
[PHARMACY] Buscando farmácias para: 01310-100
[PHARMACY] Farmácias encontradas: 3
```

## ✅ Checklist de Instalação

- [x] Arquivo `api/procurar-farmacias.php` criado
- [x] Arquivo `api/pharmacy-api-key.php` criado
- [x] `.htaccess` atualizado com novas rotas
- [x] Código JavaScript atualizado para usar a nova API
- [x] Lista mockada de farmácias configurada
- [ ] (Opcional) Google Places API Key configurada

## 🎯 Resultado Esperado

Quando o usuário:
1. ✅ Seleciona "Retirada na Rede Credenciada"
2. ✅ A seção de farmácias aparece automaticamente
3. ✅ Sistema busca farmácias próximas ao CEP
4. ✅ Lista de farmácias é exibida para seleção
5. ✅ Usuário pode selecionar uma farmácia
6. ✅ Farmácia selecionada é salva no localStorage

## 🐛 Troubleshooting

### Erro: "Nenhuma farmácia encontrada"

**Causa:** CEP não está na lista mockada ou região não tem farmácias.

**Solução:**
1. Adicione farmácias para a região no arquivo PHP
2. Ou configure Google Places API para busca real

### Erro: "Erro ao localizar unidades"

**Causa:** Erro na API ou CEP inválido.

**Solução:**
1. Verifique se o CEP está correto
2. Verifique logs do servidor
3. Teste a API diretamente via curl

### Farmácias não aparecem ao clicar

**Causa:** JavaScript não está chamando a função corretamente.

**Solução:**
1. Verifique console do navegador (F12)
2. Verifique se `endereco_form_data` está no localStorage
3. Verifique se o CEP está preenchido

## 📞 Suporte

Se precisar de ajuda:
1. Verifique os logs do servidor
2. Teste a API diretamente via curl
3. Verifique o console do navegador
4. Confirme que os arquivos foram enviados corretamente



