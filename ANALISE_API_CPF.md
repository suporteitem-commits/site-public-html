# 📊 Análise Completa - Integração da API CPF

## ✅ Status Atual da Integração

### 1. **Arquivo: `validar-dados/index.html`** ✅ CORRETO
- **Linha 2291**: Usando `/api/consultar-cpf?cpf=${cpf}` corretamente
- **Status**: ✅ Já integrado e funcionando
- **Implementação**: 
  - Faz fetch para a API através do proxy PHP
  - Tem função `getValue()` robusta para mapear diferentes estruturas
  - Logs de debug implementados
  - Tratamento de erros adequado

```2291:2291:validar-dados/index.html
      const apiUrl = `/api/consultar-cpf?cpf=${cpf}`;
```

### 2. **Arquivo: `validar-dados/Validação de Dados - ANVISA.html`** ❌ PRECISA CORREÇÃO
- **Linha 2285**: Usando `/consulta-propria-cpf` que **NÃO EXISTE**
- **Status**: ❌ Endpoint incorreto - precisa ser corrigido
- **Problema**: Este arquivo está tentando usar um endpoint que não existe no projeto
- **Solução**: Alterar para usar `/api/consultar-cpf` como no arquivo principal

**Código atual (INCORRETO):**
```2285:2289:validar-dados/Validação de Dados - ANVISA.html
      const response = await fetch("/consulta-propria-cpf", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ cpf: cpf }),
      });
```

**Deve ser alterado para:**
```javascript
      const apiUrl = `/api/consultar-cpf?cpf=${cpf}`;
      const response = await fetch(apiUrl);
```

### 3. **Arquivo: `cadastro/index.html`** ⚠️ NÃO USA API CPF
- **Status**: ⚠️ Apenas validação de formato, não consulta API
- **Observação**: Este arquivo apenas valida o formato do CPF e armazena no sessionStorage
- **Recomendação**: Se precisar validar o CPF antes de prosseguir, deve integrar a API aqui também

### 4. **Arquivos que CONSUMEM dados do CPF (localStorage)** ✅ NÃO PRECISAM DE CORREÇÃO
- **Arquivos**: 
  - `endereco/index.html` - Lê `cpfData` do localStorage (linhas 206, 589, 1640)
  - `questionario-saude/index.html` - Lê `cpfData` do localStorage (linha 2695)
  - `selecao/index.html` - Lê `cpfValidado` do localStorage (linha 2800)
- **Status**: ✅ Funcionando corretamente
- **Observação**: Esses arquivos não fazem chamadas à API CPF diretamente. Eles consomem os dados que foram salvos no localStorage pela página `validar-dados/index.html` após a consulta à API.

### 5. **Arquivo: `validar-dados/index.html` - Função comentada** ⚠️
- **Linha 2239**: Há uma referência a `/api/consulta-detalhada-cpf` que está comentada
- **Status**: ⚠️ Função desabilitada (linha 2284)
- **Observação**: Parece ser uma API secundária que não está disponível

## 🔧 Correções Necessárias

### Prioridade ALTA 🔴

#### 1. Corrigir `validar-dados/Validação de Dados - ANVISA.html`
- **Arquivo**: `validar-dados/Validação de Dados - ANVISA.html`
- **Linha**: ~2285
- **Ação**: Substituir endpoint `/consulta-propria-cpf` por `/api/consultar-cpf?cpf=${cpf}`
- **Motivo**: O endpoint atual não existe e causará erro 404

**Código a ser substituído:**
```javascript
// ❌ REMOVER ESTE CÓDIGO:
const response = await fetch("/consulta-propria-cpf", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ cpf: cpf }),
});

// ✅ SUBSTITUIR POR:
const apiUrl = `/api/consultar-cpf?cpf=${cpf}`;
const response = await fetch(apiUrl);

if (!response.ok) {
  throw new Error(`Erro na consulta: ${response.status}`);
}

const apiData = await response.json();
```

**Também precisa adicionar a função `getValue()` e o mapeamento de dados** (similar ao que existe em `validar-dados/index.html`)

### Prioridade MÉDIA 🟡

#### 2. Verificar se `cadastro/index.html` precisa da API
- **Arquivo**: `cadastro/index.html`
- **Linha**: ~972 (função `verifyCPF`)
- **Ação**: Avaliar se é necessário consultar a API CPF antes de prosseguir
- **Observação**: Atualmente apenas valida formato e armazena no sessionStorage

#### 3. Padronizar tratamento de resposta da API
- **Ação**: Garantir que todos os arquivos que usam a API tenham:
  - Função `getValue()` para mapear diferentes estruturas
  - Logs de debug adequados
  - Tratamento de erros consistente

## 📁 Estrutura da API CPF

### Endpoint Principal
- **URL**: `/api/consultar-cpf?cpf={cpf}`
- **Método**: GET
- **Arquivo**: `api/consultar-cpf.php`
- **Token**: `e3bd2312d93dca38d2003095196a09c2`
- **API Externa**: `https://apidecpf.site/api-v1/consultas.php`

### Endpoint de Debug
- **URL**: `/api/debug-cpf.php?cpf={cpf}`
- **Arquivo**: `api/debug-cpf.php`
- **Uso**: Para diagnosticar problemas com a estrutura da resposta

## 🔍 Mapeamento de Campos

A API pode retornar dados em diferentes formatos. O proxy PHP (`api/consultar-cpf.php`) já normaliza os dados, mas o frontend também deve ter fallbacks:

### Campos Normalizados:
- **Nome**: `nome`, `Nome`, `NomePessoaFisica`, `nome_completo`
- **Mãe**: `mae`, `NomeMae`, `nome_mae`, `mother_name`
- **Nascimento**: `nascimento`, `DataNascimento`, `data_nascimento`
- **Pai**: `pai`, `NomePai`, `nome_pai`
- **Sexo**: `sexo`, `Sexo`, `gender`
- **Localização**: `localizacao`, `MunicipioNascimento`, `municipio`

## ✅ Checklist de Verificação

### Arquivos que DEVEM usar a API CPF:
- [x] `validar-dados/index.html` - ✅ Já integrado corretamente
- [ ] `validar-dados/Validação de Dados - ANVISA.html` - ❌ Precisa correção
- [ ] `cadastro/index.html` - ⚠️ Avaliar necessidade

### Arquivos da API:
- [x] `api/consultar-cpf.php` - ✅ Existe e está funcional
- [x] `api/debug-cpf.php` - ✅ Existe para debug
- [x] `api/test.php` - ✅ Existe para testes

### Funcionalidades:
- [x] Proxy PHP implementado
- [x] Normalização de dados no proxy
- [x] Tratamento de CORS
- [x] Logs de debug
- [x] Tratamento de erros
- [ ] Padronização em todos os arquivos frontend

## 🔄 Fluxo de Dados do CPF

```
1. cadastro/index.html
   └─> Valida formato do CPF
   └─> Salva no sessionStorage
   └─> Redireciona para validar-dados

2. validar-dados/index.html ✅
   └─> Consulta API: /api/consultar-cpf?cpf={cpf}
   └─> Processa resposta com getValue()
   └─> Salva no localStorage como "cpfData"
   └─> Redireciona para próxima etapa

3. Outras páginas (endereco, questionario-saude, selecao)
   └─> Lê dados do localStorage
   └─> Usa dados para pré-preencher formulários
   └─> NÃO fazem chamadas à API
```

## 🚀 Próximos Passos

1. **URGENTE**: Corrigir `validar-dados/Validação de Dados - ANVISA.html`
   - Substituir endpoint `/consulta-propria-cpf` por `/api/consultar-cpf`
   - Adicionar função `getValue()` e mapeamento de dados
   - Adicionar tratamento de erros e logs de debug

2. **OPCIONAL**: Avaliar se `cadastro/index.html` precisa validar CPF via API
   - Atualmente apenas valida formato
   - Pode ser útil validar antes de prosseguir

3. Testar a integração em todos os pontos após correções
4. Garantir que todos os arquivos usam o mesmo padrão de tratamento de resposta

## 📝 Notas Importantes

- O arquivo `validar-dados/index.html` serve como **referência** de implementação correta
- A função `getValue()` é essencial para lidar com diferentes estruturas de resposta
- Sempre usar o endpoint `/api/consultar-cpf` (não chamar a API externa diretamente)
- O proxy PHP resolve problemas de CORS e normaliza os dados

