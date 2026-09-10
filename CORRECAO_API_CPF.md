# 🔧 Correção do Problema com Nome da Mãe na API de CPF

## 🔍 Problema Identificado

O nome da mãe não está aparecendo corretamente porque:
1. A estrutura de resposta da API pode variar
2. O mapeamento dos campos pode não estar capturando todos os formatos possíveis
3. Pode haver diferença entre o formato esperado e o formato retornado

## ✅ Correções Implementadas

### 1. Melhorias no Proxy PHP (`api/consultar-cpf.php`)

- ✅ Adicionado normalização de dados para diferentes estruturas de resposta
- ✅ Suporte para múltiplos formatos de campos (camelCase, snake_case, PascalCase)
- ✅ Logs detalhados para debug
- ✅ Tratamento de dados dentro de `Result` ou na raiz do JSON

### 2. Melhorias no Frontend (`validar-dados/index.html`)

- ✅ Função `getValue()` que busca dados em múltiplas estruturas possíveis
- ✅ Mapeamento robusto de campos com fallbacks
- ✅ Logs detalhados no console para debug
- ✅ Validação melhorada de campos

## 🧪 Como Diagnosticar o Problema

### Passo 1: Verificar a Estrutura Real da API

Acesse o script de debug:
```
https://seu-dominio.com/api/debug-cpf.php?cpf=11653188812
```

Este script mostrará:
- A estrutura completa da resposta da API
- Todos os campos disponíveis
- O mapeamento atual dos campos

### Passo 2: Verificar os Logs do Console

Abra o console do navegador (F12) e verifique:

1. **Log da resposta completa:**
   ```
   📊 DEBUG: Resposta completa da API: {...}
   ```

2. **Log dos dados mapeados:**
   ```
   📊 DEBUG: Dados mapeados da API:
     - Nome: ...
     - Mãe: ...
   ```

3. **Log da validação:**
   ```
   🔍 DEBUG: Nome da mãe ANTES da validação: ...
   ✅ DEBUG: Nome da mãe VÁLIDO: ...
   ```

### Passo 3: Verificar se o Nome da Mãe Está Sendo Passado

Procure no console:
```
🔍 DEBUG [createMotherNameOptions]: Nome da mãe recebido: ...
```

## 🔧 Possíveis Estruturas da API

A API pode retornar os dados em diferentes formatos:

### Formato 1: Campos na raiz
```json
{
  "nome": "JOÃO DA SILVA",
  "mae": "MARIA DA SILVA",
  "nascimento": "01/01/1990",
  "sexo": "M"
}
```

### Formato 2: Dentro de Result
```json
{
  "Result": {
    "NomePessoaFisica": "JOÃO DA SILVA",
    "NomeMae": "MARIA DA SILVA",
    "DataNascimento": "1990-01-01",
    "Sexo": "M"
  }
}
```

### Formato 3: Campos com underscore
```json
{
  "nome_completo": "JOÃO DA SILVA",
  "nome_mae": "MARIA DA SILVA",
  "data_nascimento": "01/01/1990"
}
```

## 🛠️ Correções Adicionais Necessárias

Se após as correções o problema persistir, você pode precisar:

### 1. Verificar a Documentação da API

Consulte a documentação da API `apidecpf.site` para confirmar:
- A estrutura exata da resposta
- Os nomes corretos dos campos
- Se há algum campo adicional necessário

### 2. Testar com CPF Real

Teste com um CPF real que você conheça os dados para verificar:
- Se a API está retornando os dados corretos
- Se o mapeamento está funcionando
- Se há algum problema com dados específicos

### 3. Adicionar Campos Alternativos

Se a API usar nomes de campos diferentes, adicione no código:

**No arquivo `validar-dados/index.html`, linha ~2290:**

```javascript
const mae = getValue(apiData, 
  'mae',                    // Formato atual
  'nome_mae',              // Formato alternativo 1
  'NomeMae',                // Formato alternativo 2
  'mother_name',            // Formato alternativo 3
  'mae_nome',               // Formato alternativo 4
  'NOME_MAE',               // Formato alternativo 5
  // Adicione aqui outros formatos que a API possa usar
) || "";
```

## 📋 Checklist de Verificação

- [ ] Script de debug acessível e funcionando
- [ ] Logs do console mostrando a resposta completa da API
- [ ] Nome da mãe aparecendo nos logs
- [ ] Nome da mãe sendo validado corretamente
- [ ] Nome da mãe sendo passado para `createMotherNameOptions`
- [ ] Opções de nome da mãe sendo criadas corretamente

## 🚨 Se o Problema Persistir

1. **Capture a resposta completa da API:**
   - Use o script `api/debug-cpf.php`
   - Copie a estrutura JSON completa
   - Verifique qual campo contém o nome da mãe

2. **Atualize o mapeamento:**
   - Identifique o nome exato do campo na resposta
   - Adicione esse campo na função `getValue()`
   - Teste novamente

3. **Verifique se a API está retornando dados:**
   - A API pode não ter o nome da mãe para esse CPF específico
   - Algumas APIs não retornam todos os dados
   - Verifique se o token da API está válido

## 📞 Informações para Suporte

Se precisar de ajuda adicional, forneça:

1. A resposta completa da API (do script de debug)
2. Os logs do console do navegador
3. O CPF testado (pode mascarar os últimos dígitos)
4. Screenshot da página mostrando o problema



