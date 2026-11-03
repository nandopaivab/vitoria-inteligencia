# MANUAL DE INSTALAÇÃO E OPERAÇÃO NO PLESK — VITÓRIA INTELIGÊNCIA

Este manual detalha todos os procedimentos para publicar, configurar e operar o sistema **Vitória Inteligência** no subdomínio `https://inteligencia.fernandopaiva.com.br` utilizando exclusivamente a interface gráfica do **Plesk**.

---

## 1. Requisitos de Ambiente (Plesk)
- **Servidor Web:** Apache (com `mod_rewrite`) ou Nginx + Apache (padrão Plesk).
- **PHP:** 8.2+ (compatível também com PHP 8.0, 8.1 e 8.3 no seletor do Plesk).
- **Extensões PHP ativas por padrão no Plesk:** `PDO`, `pdo_mysql`, `json`, `mbstring`, `curl`, `openssl`.
- **Banco de Dados:** MySQL 8.0+ ou MariaDB 10.4+.
- **Acesso SSH / Composer / Docker:** **NÃO EXIGIDOS.**

---

## 2. Publicação dos Arquivos no Subdomínio
1. No painel Plesk, acesse **Websites & Domains** e localize o subdomínio `inteligencia.fernandopaiva.com.br`.
2. Abra o **File Manager** da pasta raiz do subdomínio.
3. Remova o arquivo `index.html` padrão gerado pelo Plesk (se existir).
4. Faça upload do pacote `vitoria-inteligencia-plesk.zip` e clique em **Extract Files**.
5. Certifique-se de que a estrutura fique diretamente na raiz do subdomínio (`index.php`, `.htaccess`, `install/`, `app/`, `config/`, `database/`, `public/`, `storage/`, `views/`, `cron/`).

---

## 3. Instalação Visual Guiada (`/install`)
1. Acesse `https://inteligencia.fernandopaiva.com.br/install`.
2. **Etapa 1 (Diagnóstico):** Confirme que todos os indicadores de versão do PHP, extensões e permissões de escrita (`config/` e `storage/`) estão marcados em verde (**Ativa / Gravável**). Clique em **Avançar para Configuração MySQL**.
3. **Etapa 2 (Banco MySQL e Administrador):**
   - Informe o Host (`localhost`), Porta (`3306`), Nome do Banco, Usuário MySQL e Senha criados no Plesk.
   - Informe seu Nome, E-mail Institucional e Senha de Administrador (mínimo 8 caracteres).
   - Clique em **Instalar Vitória Inteligência**.
4. **Etapa 3 (Conclusão e Bloqueio Automático):**
   - O instalador criará todas as 17 tabelas, importará os 80 bairros oficiais de Vitória/ES e os 75 equipamentos públicos georreferenciados, gravará `config/config.php` com criptografia `AES-256-CBC` e criará o arquivo `storage/installed.lock`.
   - Qualquer tentativa posterior de acesso a `/install` exibirá a tela de **Instalador Bloqueado por Segurança**.

---

## 4. Configuração de Chaves de API (OpenAI e Redes Sociais)
1. Faça login no painel e acesse **Segurança, APIs & LGPD** no menu lateral.
2. Insira sua chave `sk-...` da **OpenAI** e clique em **Salvar Configurações e Criptografar Chaves**. A chave será criptografada no MySQL via `AES-256-CBC` com validação HMAC.
3. Na **Central de Redes Sociais**, clique em **Configurar Token da API Oficial** em cada card (Instagram, Facebook, X) para inserir o Page ID / Account ID e o Access Token OAuth2 oficial.
