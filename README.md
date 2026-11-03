# VITÓRIA INTELIGÊNCIA
## Plataforma de Inteligência Territorial, Gestão Comunitária e Comunicação Digital (Vitória/ES)

Aplicação web full-stack desenvolvida em **PHP 8.2+** (compatível com versões anteriores disponíveis no Plesk) e **MySQL 8 / MariaDB**, projetada especificamente para hospedagem tradicional **Plesk** sem necessidade de SSH, Docker, Node.js, Redis ou Composer.

---

## 1. Visão Geral da Arquitetura e Estrutura de Diretórios

```text
vitoria-inteligencia/
├── .htaccess                        # Proteção Apache/Plesk para pastas internas e cabeçalhos de segurança
├── index.php                        # Front Controller principal (Rotas, Autenticação, CSRF, CRUDs, Exportações)
├── README.md                        # Manual completo de instalação e operação no Plesk
├── MANUAL_INSTALACAO_PLESK.md       # Guia passo a passo ilustrado para administradores Plesk
├── app/
│   ├── bootstrap.php                # Autoloader PSR-4 nativo (zero Composer) e helpers globais
│   ├── Core/
│   │   ├── Auth.php                 # Login seguro (password_hash / password_verify) e RBAC
│   │   ├── Database.php             # Conexão PDO Segura (MySQL 8 / MariaDB) com Prepared Statements
│   │   ├── Logger.php               # Logs em arquivo e auditoria em banco de dados (LGPD)
│   │   └── Security.php             # Proteção CSRF, Sessões HttpOnly e Criptografia AES-256-CBC
│   └── Services/
│       ├── BackupService.php        # Gerador de dumps SQL completos em storage/backups/
│       ├── ExportService.php        # Exportador nativo de relatórios em PDF (%PDF-1.4) e Excel (.xls)
│       ├── OpenAIService.php        # Integração oficial com API OpenAI (com fontes, datas e limitações)
│       ├── RadarService.php         # Coleta ética de feeds RSS públicos e classificação territorial
│       └── SocialService.php        # Integração com APIs oficiais Meta Graph API v19.0 e X API v2
├── config/
│   └── config.example.php           # Exemplo de arquivo de configuração (gerado automaticamente pelo /install)
├── cron/                            # Scripts CLI independentes para Tarefas Agendadas (Cron Jobs) do Plesk
│   ├── CronRunner.php               # Controle de lock (flock) contra execução duplicada e log em banco
│   ├── update_news.php              # Atualiza notícias do Radar Municipal
│   ├── generate_reports.php         # Gera relatórios PDF e backup SQL diário
│   ├── update_indicators.php        # Atualiza snapshot de indicadores territoriais
│   ├── prepare_posts.php            # Verifica publicações recorrentes e notifica revisores
│   ├── publish_approved.php         # Dispara publicações aprovadas quando há token oficial válido
│   └── cleanup_temp.php             # Limpa arquivos temporários
├── database/
│   ├── schema.sql                   # Banco SQL completo (17 tabelas, 80 bairros oficiais e 75 equipamentos de Vitória/ES)
│   └── geojson/
│       ├── vitoria_bairros.geojson      # Malha poligonal dos 80 bairros oficiais (Lei Municipal nº 8.611/2013)
│       └── vitoria_equipamentos.geojson # 75 equipamentos públicos georreferenciados (29 UBS, Escolas, Parques, Praças)
├── install/
│   └── index.php                    # Instalador Visual Plesk (https://inteligencia.fernandopaiva.com.br/install)
├── public/
│   └── assets/
│       ├── css/style.css            # Identidade visual (#101B33, #2563EB, #FFFFFF, #F1F5F9, #16A34A, #F59E0B)
│       ├── vitoria_bairros.geojson
│       └── vitoria_equipamentos.geojson
├── storage/
│   ├── backups/                     # Backups SQL e relatórios gerados pelo Cron
│   ├── cache/                       # Arquivos de lock dos Cron Jobs e snapshots
│   ├── logs/                        # Logs de aplicação, erro, auditoria e cron
│   └── uploads/                     # Banco de imagens da Central de Redes Sociais
└── views/                           # Camada de visualização responsiva (Bootstrap 5 + Chart.js + Leaflet)
    ├── layouts/main.php
    ├── auth/login.php
    ├── dashboard/index.php
    ├── map/index.php
    ├── contacts/index.php
    ├── demands/index.php
    ├── radar/index.php
    ├── social/index.php
    ├── ai/index.php
    ├── agenda/index.php
    ├── reports/index.php
    └── settings/index.php
```

---

## 2. Passo a Passo de Instalação no Plesk (Sem SSH e Sem Composer)

### Passo 1: Criar o Subdomínio no Plesk
1. Acesse o painel **Plesk** da sua hospedagem.
2. Vá em **Websites & Domains (Sites e Domínios)** > **Add Subdomain (Adicionar Subdomínio)**.
3. Informe:
   - **Subdomain name:** `inteligencia`
   - **Parent domain:** `fernandopaiva.com.br`
   - **Document root:** `/inteligencia.fernandopaiva.com.br` (ou `httpdocs/inteligencia`)
4. Em **PHP Settings**, selecione **PHP 8.2** (ou superior disponível no Plesk) e certifique-se de que o certificado SSL/TLS Let's Encrypt está ativo.

### Passo 2: Criar o Banco de Dados MySQL no Plesk
1. No painel Plesk, clique em **Databases (Bancos de Dados)** > **Add Database (Adicionar Banco de Dados)**.
2. Defina:
   - **Database name:** `vitoria_inteligencia`
   - **Related site:** `inteligencia.fernandopaiva.com.br`
   - **Database user name:** crie o usuário e anote a senha gerada.

### Passo 3: Enviar o Arquivo ZIP pelo Gerenciador de Arquivos do Plesk
1. No Plesk, abra o **File Manager (Gerenciador de Arquivos)** da pasta raiz do subdomínio `inteligencia.fernandopaiva.com.br`.
2. Clique em **Upload** e envie o arquivo `vitoria-inteligencia-plesk.zip`.
3. Clique sobre o arquivo enviado e selecione **Extract Files (Extrair Arquivos)** diretamente na raiz do subdomínio.
4. Verifique se as pastas `config/` e `storage/` possuem permissão de escrita pelo usuário do PHP (padrão no Plesk FastCGI/FPM).

### Passo 4: Executar o Instalador Visual (`/install`)
1. Abra o navegador e acesse:
   `https://inteligencia.fernandopaiva.com.br/install`
2. O instalador executará automaticamente as **10 etapas obrigatórias**:
   1. Verificação da versão do PHP.
   2. Verificação das extensões (`pdo`, `pdo_mysql`, `json`, `mbstring`, `curl`, `openssl`).
   3. Verificação das permissões das pastas `config/` e `storage/`.
   4. Solicitação das credenciais do banco MySQL criado no Plesk.
   5. Teste de conexão PDO ao MySQL.
   6. Criação automática das **17 tabelas** e carga dos **80 bairros oficiais de Vitória/ES** (Lei Municipal nº 8.611/2013) + **75 equipamentos públicos verificados**.
   7. Criação da conta de Administrador Geral com `password_hash()`.
   8. Geração automática do arquivo `config/config.php` com chave criptográfica exclusiva AES-256.
   9. Finalização da instalação.
   10. Bloqueio automático do instalador (`storage/installed.lock`).

---

## 3. Configuração das Automações no Plesk (Scheduled Tasks / Cron Jobs)

No painel do Plesk, acesse **Tools & Settings** (ou na aba lateral do domínio) > **Scheduled Tasks (Tarefas Agendadas)** > **Add Task**.
Escolha o tipo **Run a PHP script (Executar um script PHP)** com PHP 8.2+ e cadastre os 6 jobs independentes:

| Tarefa | Caminho do Script | Periodicidade Recomendada | Função |
| :--- | :--- | :--- | :--- |
| **1. Atualizar Radar** | `cron/update_news.php` | A cada 2 horas (`0 */2 * * *`) | Sincroniza feeds RSS públicos de Vitória/ES e classifica por categoria e bairro |
| **2. Executar Fila Social** | `cron/publish_approved.php` | A cada 15 min (`*/15 * * * *`) | Publica itens aprovados que possuam token oficial válido (Meta/X) |
| **3. Atualizar Indicadores** | `cron/update_indicators.php` | A cada 6 horas (`0 */6 * * *`) | Consolida snapshot de indicadores territoriais dos 80 bairros |
| **4. Preparar Publicações** | `cron/prepare_posts.php` | Diariamente às 07:00 (`0 7 * * *`) | Verifica publicações recorrentes e gera alertas de revisão |
| **5. Relatórios e Backup** | `cron/generate_reports.php` | Diariamente às 02:00 (`0 2 * * *`) | Gera relatório PDF diário e backup SQL completo em `storage/backups/` |
| **6. Limpeza Temporária** | `cron/cleanup_temp.php` | Domingos às 03:30 (`30 3 * * 0`) | Remove arquivos temporários expirados em `storage/cache/` |

Todos os scripts utilizam `CronRunner.php` com bloqueio de arquivo (`flock`) para impedir execuções duplicadas simultâneas e registram logs em `storage/logs/cron.log` e na tabela `cron_runs`.

---

## 4. Conformidade Legal (LGPD) e Ética de Dados

- **Proteção de Dados Pessoais (Lei nº 13.709/2018):** O módulo de Gestão Comunitária registra o status de consentimento (`consent_lgpd`), finalidade institucional e canal preferido de comunicação, permitindo edição, consulta de histórico e eliminação definitiva a pedido do titular.
- **Vedação de Perfilamento Político-Eleitoral:** O banco de dados e os formulários **não coletam** intenção de voto, preferência partidária, ideologia ou perfil político individual, nem utilizam endereço residencial para classificação ou predição de comportamento eleitoral.
- **Integrações Oficiais e Transparentes:** A Central de Redes Sociais e o módulo de Inteligência Artificial (OpenAI) operam exclusivamente via APIs oficiais com credenciais criptografadas (`AES-256-CBC`) e não simulam disparos externos quando as chaves não estão configuradas.
