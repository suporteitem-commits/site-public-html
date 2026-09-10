# 📋 Instruções de Instalação - API de CPF

## 🔍 Problema Identificado

A API de CPF está retornando erro 404 porque o servidor proxy não está configurado no servidor de produção.

## ✅ Solução Implementada

Foram criados arquivos PHP que fazem o mesmo trabalho do `proxy_api.py`, mas funcionam em servidores de hospedagem compartilhada que não suportam Python.

## 📁 Arquivos Criados

1. **`api/consultar-cpf.php`** - Proxy PHP para API de CPF
2. **`api/farmacias-por-ip.php`** - Mock API para farmácias
3. **`api/ip-geo.php`** - Mock API para geolocalização
4. **`.htaccess`** - Configurações do Apache para roteamento

## 🚀 Passos para Instalação

### Opção 1: Usando PHP (Recomendado - Mais Fácil)

Esta é a solução mais simples e funciona na maioria dos servidores de hospedagem compartilhada.

#### 1. Fazer Upload dos Arquivos

Faça upload dos seguintes arquivos para o servidor:

```
public_html/
├── api/
│   ├── consultar-cpf.php
│   ├── farmacias-por-ip.php
│   └── ip-geo.php
└── .htaccess
```

#### 2. Verificar Permissões

Certifique-se de que os arquivos PHP têm permissão de leitura:

```bash
chmod 644 api/*.php
chmod 644 .htaccess
```

#### 3. Testar a API

Acesse no navegador ou via curl:

```
https://mediumspringgreen-lapwing-164376.hostingersite.com/api/consultar-cpf?cpf=11653188812
```

**Resposta esperada:** JSON com dados do CPF ou erro da API.

#### 4. Verificar Logs de Erro

Se houver problemas, verifique os logs do PHP:

- **cPanel:** Logs de Erro do PHP
- **Painel de Controle:** Seção de Logs
- **Arquivo:** `error_log` na raiz do projeto

### Opção 2: Usando Python (Avançado)

Se você tem acesso SSH ao servidor e Python instalado:

#### 1. Conectar via SSH

```bash
ssh usuario@servidor.com
cd /caminho/para/public_html
```

#### 2. Verificar Python

```bash
python3 --version
# ou
python --version
```

#### 3. Rodar o Proxy

```bash
# Rodar em foreground (para teste)
python3 proxy_api.py

# Rodar em background
nohup python3 proxy_api.py > proxy.log 2>&1 &

# Ou usar screen/tmux
screen -S proxy
python3 proxy_api.py
# Pressionar Ctrl+A depois D para desanexar
```

#### 4. Configurar como Serviço (Linux)

Criar arquivo `/etc/systemd/system/cpf-proxy.service`:

```ini
[Unit]
Description=CPF API Proxy
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/caminho/para/public_html
ExecStart=/usr/bin/python3 /caminho/para/public_html/proxy_api.py
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Ativar o serviço:

```bash
sudo systemctl enable cpf-proxy
sudo systemctl start cpf-proxy
sudo systemctl status cpf-proxy
```

### Opção 3: Proxy Reverso com Nginx

Se você usa Nginx, configure proxy reverso:

```nginx
server {
    listen 80;
    server_name mediumspringgreen-lapwing-164376.hostingersite.com;

    location /api/consultar-cpf {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

## 🔧 Configurações Adicionais

### Verificar se cURL está habilitado no PHP

Execute este código PHP para verificar:

```php
<?php
if (function_exists('curl_version')) {
    echo "✅ cURL está habilitado\n";
    $version = curl_version();
    echo "Versão: " . $version['version'] . "\n";
} else {
    echo "❌ cURL NÃO está habilitado\n";
    echo "Contate o suporte da hospedagem para habilitar\n";
}
?>
```

### Habilitar cURL (se necessário)

Se cURL não estiver habilitado, adicione no `php.ini`:

```ini
extension=curl
```

Ou peça ao suporte da hospedagem para habilitar.

## 🧪 Testes

### Teste 1: Verificar se a API está acessível

```bash
curl "https://mediumspringgreen-lapwing-164376.hostingersite.com/api/consultar-cpf?cpf=11653188812"
```

### Teste 2: Verificar resposta JSON

```bash
curl -s "https://mediumspringgreen-lapwing-164376.hostingersite.com/api/consultar-cpf?cpf=11653188812" | jq .
```

### Teste 3: Testar no navegador

Acesse:
```
https://mediumspringgreen-lapwing-164376.hostingersite.com/validar-dados/?cpf=11653188812
```

O status deve mudar de "Falha na conexão" para "Documento válido" ou mostrar os dados do CPF.

## 🐛 Troubleshooting

### Erro 404 - Arquivo não encontrado

**Causa:** Arquivo não foi enviado ou está no local errado.

**Solução:**
1. Verificar se `api/consultar-cpf.php` existe
2. Verificar permissões do arquivo
3. Verificar se `.htaccess` está funcionando

### Erro 500 - Erro interno do servidor

**Causa:** Erro no código PHP ou cURL não habilitado.

**Solução:**
1. Verificar logs de erro do PHP
2. Verificar se cURL está habilitado
3. Verificar sintaxe do PHP

### Erro de CORS

**Causa:** Headers CORS não estão sendo enviados.

**Solução:**
1. Verificar se `.htaccess` está configurado
2. Verificar headers no arquivo PHP
3. Verificar configurações do servidor

### API retorna erro

**Causa:** Token da API pode estar inválido ou API externa está fora do ar.

**Solução:**
1. Verificar token no arquivo `api/consultar-cpf.php`
2. Testar API diretamente: `https://apidecpf.site/api-v1/consultas.php?cpf=11653188812&token=SEU_TOKEN`
3. Verificar logs de erro

## 📞 Suporte

Se continuar com problemas:

1. Verifique os logs de erro do servidor
2. Teste a API diretamente no navegador
3. Verifique se o servidor suporta cURL
4. Entre em contato com o suporte da hospedagem

## ✅ Checklist de Instalação

- [ ] Arquivo `api/consultar-cpf.php` enviado
- [ ] Arquivo `api/farmacias-por-ip.php` enviado
- [ ] Arquivo `api/ip-geo.php` enviado
- [ ] Arquivo `.htaccess` enviado
- [ ] Permissões dos arquivos configuradas
- [ ] cURL habilitado no PHP
- [ ] Teste da API funcionando
- [ ] Página de validação funcionando

## 🎯 Resultado Esperado

Após a instalação, ao acessar:
```
https://mediumspringgreen-lapwing-164376.hostingersite.com/validar-dados/?cpf=11653188812
```

O sistema deve:
1. ✅ Conectar com a API de CPF
2. ✅ Retornar dados do CPF
3. ✅ Mostrar status "Documento válido"
4. ✅ Permitir prosseguir com o cadastro



