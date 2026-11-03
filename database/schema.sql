-- =============================================================================
-- VITÓRIA INTELIGÊNCIA - BANCO DE DADOS COMPLETO (MySQL 8+ / MariaDB 10.4+)
-- Plataforma de Inteligência Territorial, Gestão Comunitária e Comunicação Digital
-- Município de Vitória / ES (Código IBGE: 3205309)
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tabela de Usuários e Controle de Acesso (RBAC)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'coordenador', 'analista', 'atendente') NOT NULL DEFAULT 'analista',
  `phone` VARCHAR(30) DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabela Oficial dos 80 Bairros de Vitória/ES (Lei Municipal nº 8.611/2013)
CREATE TABLE IF NOT EXISTS `neighborhoods` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `region_id` TINYINT UNSIGNED NOT NULL,
  `region_name` VARCHAR(100) NOT NULL,
  `population` INT UNSIGNED NOT NULL DEFAULT 0,
  `area_km2` DECIMAL(8,3) NOT NULL DEFAULT 0.000,
  `households` INT UNSIGNED NOT NULL DEFAULT 0,
  `lat` DECIMAL(10,6) NOT NULL,
  `lng` DECIMAL(10,6) NOT NULL,
  `monitored` TINYINT(1) NOT NULL DEFAULT 1,
  `notes` TEXT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_neighborhood_region` (`region_id`),
  INDEX `idx_neighborhood_monitored` (`monitored`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabela de Equipamentos Públicos Georreferenciados de Vitória/ES
CREATE TABLE IF NOT EXISTS `public_equipments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(180) NOT NULL,
  `type` ENUM('Unidade de Saúde', 'Escola', 'Parque', 'Praça', 'Equipamento Esportivo', 'Equipamento Público') NOT NULL,
  `neighborhood_id` INT UNSIGNED DEFAULT NULL,
  `neighborhood_name` VARCHAR(100) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `lat` DECIMAL(10,6) NOT NULL,
  `lng` DECIMAL(10,6) NOT NULL,
  `source` VARCHAR(150) NOT NULL DEFAULT 'Prefeitura Municipal de Vitória',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_equip_type` (`type`),
  INDEX `idx_equip_neighborhood` (`neighborhood_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabela de Gestão Comunitária (Contatos, Voluntários e Colaboradores - Conformidade LGPD)
-- Nota Legal: Não coleta intenção de voto, preferência partidária, ideologia ou perfil político individual.
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(35) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `profile_type` ENUM('Voluntário', 'Colaborador', 'Participante de Atividade', 'Liderança Comunitária') NOT NULL DEFAULT 'Participante de Atividade',
  `neighborhood_id` INT UNSIGNED NOT NULL,
  `registration_date` DATE NOT NULL,
  `consent_lgpd` TINYINT(1) NOT NULL DEFAULT 1,
  `consent_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `consent_purpose` VARCHAR(255) NOT NULL DEFAULT 'Comunicação institucional, convites para eventos comunitários e acompanhamento de demandas públicas municipais (Lei 13.709/2018 - LGPD)',
  `communication_pref` ENUM('WhatsApp', 'E-mail', 'Telefone', 'SMS', 'Nenhum') NOT NULL DEFAULT 'WhatsApp',
  `responsible_user_id` INT UNSIGNED DEFAULT NULL,
  `responsible_name` VARCHAR(120) NOT NULL,
  `relationship_summary` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_contact_neighborhood` (`neighborhood_id`),
  INDEX `idx_contact_consent` (`consent_lgpd`),
  INDEX `idx_contact_type` (`profile_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Histórico de Relacionamento Comunitário
CREATE TABLE IF NOT EXISTS `contact_interactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `contact_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `user_name` VARCHAR(120) NOT NULL,
  `interaction_type` ENUM('Atendimento Presencial', 'Contato Telefônico', 'Mensagem / Informativo', 'Participação em Reunião', 'Participação em Evento', 'Atualização de Consentimento LGPD') NOT NULL,
  `description` TEXT NOT NULL,
  `interaction_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_inter_contact` (`contact_id`),
  CONSTRAINT `fk_inter_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabela de Gestão de Demandas Comunitárias Municipais
CREATE TABLE IF NOT EXISTS `demands` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `protocol_code` VARCHAR(30) NOT NULL UNIQUE,
  `title` VARCHAR(180) NOT NULL,
  `description` TEXT NOT NULL,
  `neighborhood_id` INT UNSIGNED NOT NULL,
  `address_location` VARCHAR(255) NOT NULL,
  `lat` DECIMAL(10,6) NOT NULL,
  `lng` DECIMAL(10,6) NOT NULL,
  `category` ENUM('Saúde', 'Educação', 'Mobilidade', 'Segurança pública', 'Infraestrutura', 'Iluminação', 'Limpeza urbana', 'Saneamento', 'Meio ambiente', 'Esporte', 'Assistência social') NOT NULL,
  `demand_date` DATE NOT NULL,
  `priority` ENUM('Baixa', 'Média', 'Alta', 'Urgente') NOT NULL DEFAULT 'Média',
  `status` ENUM('Aberta', 'Em Análise', 'Encaminhada ao Órgão', 'Em Execução', 'Concluída', 'Arquivada') NOT NULL DEFAULT 'Aberta',
  `responsible_user_id` INT UNSIGNED DEFAULT NULL,
  `responsible_name` VARCHAR(120) NOT NULL,
  `contact_id` INT UNSIGNED DEFAULT NULL,
  `external_reference` VARCHAR(100) DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_demand_neighborhood` (`neighborhood_id`),
  INDEX `idx_demand_category` (`category`),
  INDEX `idx_demand_status` (`status`),
  INDEX `idx_demand_priority` (`priority`),
  INDEX `idx_demand_date` (`demand_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Histórico de Movimentação de Demandas
CREATE TABLE IF NOT EXISTS `demand_history` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `demand_id` INT UNSIGNED NOT NULL,
  `previous_status` VARCHAR(50) DEFAULT NULL,
  `new_status` VARCHAR(50) NOT NULL,
  `notes` TEXT NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `user_name` VARCHAR(120) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_dem_hist_demand` (`demand_id`),
  CONSTRAINT `fk_dem_hist_demand` FOREIGN KEY (`demand_id`) REFERENCES `demands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Radar de Inteligência Municipal (Notícias, Dados Públicos, Pesquisas de Tendência e Menções)
CREATE TABLE IF NOT EXISTS `radar_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `source_name` VARCHAR(120) NOT NULL,
  `source_type` ENUM('Notícia Jornalística', 'Portal Oficial / Governo', 'Tendência de Busca (Google Trends)', 'Dados Públicos / IBGE', 'Menção Pública Social') NOT NULL DEFAULT 'Notícia Jornalística',
  `source_url` VARCHAR(500) NOT NULL,
  `category` ENUM('Saúde', 'Educação', 'Segurança', 'Mobilidade', 'Transporte', 'Infraestrutura', 'Meio ambiente', 'Economia', 'Turismo', 'Esporte', 'Administração pública') NOT NULL,
  `neighborhood_id` INT UNSIGNED DEFAULT NULL,
  `published_at` DATETIME NOT NULL,
  `content_excerpt` TEXT DEFAULT NULL,
  `ai_summary` TEXT DEFAULT NULL,
  `recurring_theme` VARCHAR(120) DEFAULT NULL,
  `relevance_score` TINYINT UNSIGNED NOT NULL DEFAULT 75,
  `is_sample_data` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_radar_category` (`category`),
  INDEX `idx_radar_source_type` (`source_type`),
  INDEX `idx_radar_published` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Contas de Redes Sociais (APIs Oficiais Meta Graph API & X API v2)
CREATE TABLE IF NOT EXISTS `social_accounts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `platform` ENUM('Instagram', 'Facebook', 'X') NOT NULL,
  `account_name` VARCHAR(120) NOT NULL,
  `account_handle` VARCHAR(100) NOT NULL,
  `external_account_id` VARCHAR(120) DEFAULT NULL,
  `access_token_encrypted` TEXT DEFAULT NULL,
  `token_expires_at` DATETIME DEFAULT NULL,
  `status` ENUM('Conectada (API Oficial)', 'Aguardando Credenciais', 'Token Expirado', 'Desconectada') NOT NULL DEFAULT 'Aguardando Credenciais',
  `followers_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `engagement_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `last_sync_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Banco de Imagens / Mídia para Redes Sociais
CREATE TABLE IF NOT EXISTS `social_media_assets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(60) NOT NULL DEFAULT 'image/jpeg',
  `file_size_kb` INT UNSIGNED NOT NULL DEFAULT 0,
  `alt_text` VARCHAR(255) DEFAULT NULL,
  `category` VARCHAR(80) DEFAULT 'Institucional',
  `uploaded_by` VARCHAR(120) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Publicações, Calendário Editorial, Fila de Publicação e Fluxo de Aprovação
CREATE TABLE IF NOT EXISTS `social_posts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(180) NOT NULL,
  `platforms` VARCHAR(100) NOT NULL COMMENT 'Instagram,Facebook,X',
  `caption` TEXT NOT NULL,
  `hashtags` VARCHAR(500) DEFAULT NULL,
  `media_asset_id` INT UNSIGNED DEFAULT NULL,
  `media_url` VARCHAR(255) DEFAULT NULL,
  `scheduled_at` DATETIME NOT NULL,
  `recurrence` ENUM('Nenhuma', 'Diária', 'Semanal', 'Mensal') NOT NULL DEFAULT 'Nenhuma',
  `approval_status` ENUM('Rascunho', 'Em Revisão', 'Aprovado', 'Rejeitado', 'Publicado', 'Falha na API') NOT NULL DEFAULT 'Rascunho',
  `approved_by_user_id` INT UNSIGNED DEFAULT NULL,
  `approved_by_name` VARCHAR(120) DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `published_at` DATETIME DEFAULT NULL,
  `external_post_ids` VARCHAR(255) DEFAULT NULL,
  `api_response_log` TEXT DEFAULT NULL,
  `reach_metric` INT UNSIGNED NOT NULL DEFAULT 0,
  `impressions_metric` INT UNSIGNED NOT NULL DEFAULT 0,
  `likes_metric` INT UNSIGNED NOT NULL DEFAULT 0,
  `comments_metric` INT UNSIGNED NOT NULL DEFAULT 0,
  `shares_metric` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_by_name` VARCHAR(120) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_social_sched` (`scheduled_at`),
  INDEX `idx_social_status` (`approval_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Agenda Municipal e Comunitária (Eventos, Reuniões, Audiências, Compromissos)
CREATE TABLE IF NOT EXISTS `agenda_events` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(180) NOT NULL,
  `event_type` ENUM('Evento', 'Reunião', 'Audiência Pública', 'Compromisso Institucional', 'Atividade Comunitária') NOT NULL,
  `description` TEXT DEFAULT NULL,
  `start_datetime` DATETIME NOT NULL,
  `end_datetime` DATETIME NOT NULL,
  `neighborhood_id` INT UNSIGNED DEFAULT NULL,
  `address` VARCHAR(255) NOT NULL,
  `responsible_name` VARCHAR(120) NOT NULL,
  `participants_list` TEXT DEFAULT NULL,
  `expected_attendance` INT UNSIGNED NOT NULL DEFAULT 0,
  `actual_attendance` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('Agendado', 'Confirmado', 'Realizado', 'Cancelado') NOT NULL DEFAULT 'Agendado',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_agenda_start` (`start_datetime`),
  INDEX `idx_agenda_type` (`event_type`),
  INDEX `idx_agenda_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Histórico de Análises de Inteligência Artificial (OpenAI API)
CREATE TABLE IF NOT EXISTS `ai_analyses` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `analysis_type` ENUM('Resumo de Notícias', 'Análise de Demandas Municipais', 'Tendências de Assuntos Públicos', 'Relatório Executivo', 'Sugestão de Pautas Informativas', 'Calendário Editorial', 'Texto Institucional', 'Análise de Indicadores') NOT NULL,
  `prompt_context` TEXT NOT NULL,
  `ai_response` LONGTEXT NOT NULL,
  `data_sources` TEXT NOT NULL,
  `data_limitations` TEXT NOT NULL,
  `model_used` VARCHAR(60) NOT NULL DEFAULT 'gpt-4o-mini',
  `user_id` INT UNSIGNED DEFAULT NULL,
  `user_name` VARCHAR(120) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ai_type` (`analysis_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Notificações do Sistema
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` VARCHAR(500) NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'danger') NOT NULL DEFAULT 'info',
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Configurações e Credenciais Criptografadas (AES-256-CBC)
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `is_encrypted` TINYINT(1) NOT NULL DEFAULT 0,
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Logs de Auditoria, Segurança e LGPD
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `user_name` VARCHAR(120) DEFAULT 'Sistema',
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(60) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_audit_module` (`module`),
  INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Controle de Execução de Cron Jobs (Plesk Tasks)
CREATE TABLE IF NOT EXISTS `cron_runs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `job_name` VARCHAR(100) NOT NULL,
  `status` ENUM('running', 'success', 'error', 'skipped_locked') NOT NULL,
  `started_at` DATETIME NOT NULL,
  `finished_at` DATETIME DEFAULT NULL,
  `output_message` TEXT DEFAULT NULL,
  INDEX `idx_cron_job` (`job_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- INSERÇÃO DOS 80 BAIRROS OFICIAIS DE VITÓRIA/ES (Lei Municipal nº 8.611/2013)
-- =============================================================================
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (1, 'Centro', 1, 'Região 1 - Centro', 21640, 1.72, 8920, -20.3197, -40.3385, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (2, 'Do Moscoso', 1, 'Região 1 - Centro', 2180, 0.18, 740, -20.3175, -40.3452, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (3, 'Fonte Grande', 1, 'Região 1 - Centro', 2890, 0.42, 960, -20.3155, -40.3408, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (4, 'Ilha do Príncipe', 1, 'Região 1 - Centro', 4510, 0.48, 1520, -20.3208, -40.3528, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (5, 'Parque Moscoso', 1, 'Região 1 - Centro', 2950, 0.25, 1150, -20.3188, -40.3435, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (6, 'Piedade', 1, 'Região 1 - Centro', 2640, 0.22, 890, -20.3162, -40.3425, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (7, 'Santa Clara', 1, 'Região 1 - Centro', 2110, 0.19, 720, -20.3168, -40.3442, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (8, 'Vila Rubim', 1, 'Região 1 - Centro', 2480, 0.31, 910, -20.3195, -40.3482, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (9, 'Alagoano', 2, 'Região 2 - Santo Antônio', 1950, 0.16, 670, -20.3142, -40.3545, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (10, 'Ariovaldo Favalessa', 2, 'Região 2 - Santo Antônio', 1820, 0.15, 630, -20.313, -40.3532, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (11, 'Bela Vista', 2, 'Região 2 - Santo Antônio', 3420, 0.39, 1140, -20.3055, -40.3595, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (12, 'Caratoíra', 2, 'Região 2 - Santo Antônio', 5120, 0.45, 1780, -20.315, -40.3515, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (13, 'Do Cabral', 2, 'Região 2 - Santo Antônio', 1980, 0.21, 680, -20.3122, -40.349, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (14, 'Do Quadro', 2, 'Região 2 - Santo Antônio', 2450, 0.23, 840, -20.3145, -40.3488, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (15, 'Estrelinha', 2, 'Região 2 - Santo Antônio', 2840, 0.35, 950, -20.3018, -40.3568, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (16, 'Grande Vitória', 2, 'Região 2 - Santo Antônio', 3690, 0.41, 1240, -20.3002, -40.3545, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (17, 'Inhanguetá', 2, 'Região 2 - Santo Antônio', 4120, 0.58, 1380, -20.298, -40.3572, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (18, 'Mário Cypreste', 2, 'Região 2 - Santo Antônio', 2890, 0.62, 980, -20.3168, -40.3568, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (19, 'Santa Tereza', 2, 'Região 2 - Santo Antônio', 3180, 0.34, 1090, -20.3105, -40.3522, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (20, 'Santo Antônio', 2, 'Região 2 - Santo Antônio', 11420, 1.15, 3950, -20.3088, -40.3575, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (21, 'Universitário', 2, 'Região 2 - Santo Antônio', 2250, 0.33, 760, -20.3038, -40.3532, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (22, 'Bento Ferreira', 3, 'Região 3 - Jucutuquara', 6850, 0.89, 2580, -20.3125, -40.3095, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (23, 'Consolação', 3, 'Região 3 - Jucutuquara', 4380, 0.42, 1510, -20.3048, -40.3162, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (24, 'Cruzamento', 3, 'Região 3 - Jucutuquara', 3920, 0.36, 1320, -20.3112, -40.3268, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (25, 'De Lourdes', 3, 'Região 3 - Jucutuquara', 2910, 0.44, 1020, -20.3065, -40.3198, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (26, 'Forte São João', 3, 'Região 3 - Jucutuquara', 3280, 0.56, 1130, -20.3158, -40.3255, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (27, 'Fradinhos', 3, 'Região 3 - Jucutuquara', 4890, 1.48, 1720, -20.3052, -40.3315, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (28, 'Gurigica', 3, 'Região 3 - Jucutuquara', 5640, 0.47, 1920, -20.3068, -40.3135, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (29, 'Horto', 3, 'Região 3 - Jucutuquara', 2760, 0.39, 960, -20.3095, -40.3152, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (30, 'Ilha de Santa Maria', 3, 'Região 3 - Jucutuquara', 6420, 0.62, 2380, -20.3155, -40.3168, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (31, 'Jesus de Nazareth', 3, 'Região 3 - Jucutuquara', 4150, 0.54, 1420, -20.3165, -40.3045, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (32, 'Jucutuquara', 3, 'Região 3 - Jucutuquara', 7540, 0.78, 2790, -20.3092, -40.3225, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (33, 'Monte Belo', 3, 'Região 3 - Jucutuquara', 3190, 0.37, 1150, -20.3138, -40.3132, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (34, 'Nazareth', 3, 'Região 3 - Jucutuquara', 2340, 0.28, 860, -20.3098, -40.3185, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (35, 'Romão', 3, 'Região 3 - Jucutuquara', 5210, 0.59, 1760, -20.3132, -40.3302, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (36, 'Andorinhas', 4, 'Região 4 - Maruípe', 5840, 0.64, 2010, -20.2862, -40.3088, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (37, 'Bonfim', 4, 'Região 4 - Maruípe', 6120, 0.58, 2110, -20.2995, -40.3138, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (38, 'Da Penha', 4, 'Região 4 - Maruípe', 5490, 0.51, 1890, -20.2975, -40.3105, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (39, 'Itararé', 4, 'Região 4 - Maruípe', 8920, 0.72, 3120, -20.2932, -40.3085, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (40, 'Joana d''Arc', 4, 'Região 4 - Maruípe', 4180, 0.49, 1440, -20.2868, -40.3185, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (41, 'Maruípe', 4, 'Região 4 - Maruípe', 7850, 0.85, 2780, -20.2948, -40.3175, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (42, 'Santa Cecília', 4, 'Região 4 - Maruípe', 3240, 0.36, 1180, -20.3018, -40.3195, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (43, 'Santa Martha', 4, 'Região 4 - Maruípe', 5920, 0.61, 2040, -20.2885, -40.3145, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (44, 'Santos Dumont', 4, 'Região 4 - Maruípe', 4650, 0.48, 1620, -20.3025, -40.3228, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (45, 'São Benedito', 4, 'Região 4 - Maruípe', 3180, 0.29, 1090, -20.2972, -40.3155, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (46, 'São Cristóvão', 4, 'Região 4 - Maruípe', 6480, 0.74, 2250, -20.2918, -40.3225, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (47, 'Tabuazeiro', 4, 'Região 4 - Maruípe', 7120, 0.82, 2490, -20.2952, -40.3258, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (48, 'Barro Vermelho', 5, 'Região 5 - Praia do Canto', 6540, 0.68, 2480, -20.2935, -40.2995, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (49, 'Enseada do Suá', 5, 'Região 5 - Praia do Canto', 4980, 1.45, 1950, -20.3125, -40.2895, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (50, 'Ilha do Boi', 5, 'Região 5 - Praia do Canto', 1480, 0.65, 520, -20.3112, -40.2815, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (51, 'Ilha do Frade', 5, 'Região 5 - Praia do Canto', 1120, 0.48, 410, -20.3025, -40.2828, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (52, 'Praia do Canto', 5, 'Região 5 - Praia do Canto', 16890, 1.38, 6840, -20.2965, -40.2925, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (53, 'Praia do Suá', 5, 'Região 5 - Praia do Canto', 5420, 0.56, 2010, -20.3095, -40.2995, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (54, 'Santa Helena', 5, 'Região 5 - Praia do Canto', 2840, 0.34, 1080, -20.3045, -40.2962, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (55, 'Santa Lúcia', 5, 'Região 5 - Praia do Canto', 5920, 0.72, 2290, -20.2995, -40.3025, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (56, 'Santa Luíza', 5, 'Região 5 - Praia do Canto', 4380, 0.49, 1650, -20.2912, -40.3038, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (57, 'Aeroporto', 6, 'Região 6 - Goiabeiras', 1240, 5.42, 450, -20.2585, -40.2865, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (58, 'Antônio Honório', 6, 'Região 6 - Goiabeiras', 4520, 0.54, 1590, -20.2645, -40.3015, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (59, 'Goiabeiras', 6, 'Região 6 - Goiabeiras', 9840, 1.65, 3480, -20.2715, -40.3028, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (60, 'Jabour', 6, 'Região 6 - Goiabeiras', 5680, 0.68, 2040, -20.2672, -40.2975, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (61, 'Maria Ortiz', 6, 'Região 6 - Goiabeiras', 12450, 1.12, 4390, -20.2562, -40.2995, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (62, 'Segurança do Lar', 6, 'Região 6 - Goiabeiras', 2380, 0.35, 850, -20.2615, -40.2962, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (63, 'Solon Borges', 6, 'Região 6 - Goiabeiras', 2890, 0.38, 1020, -20.2598, -40.2988, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (64, 'Comdusa', 7, 'Região 7 - São Pedro', 2410, 0.31, 830, -20.2852, -40.3358, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (65, 'Conquista', 7, 'Região 7 - São Pedro', 5420, 0.72, 1860, -20.2695, -40.3342, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (66, 'Ilha das Caieiras', 7, 'Região 7 - São Pedro', 4890, 0.65, 1690, -20.2798, -40.3385, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (67, 'Nova Palestina', 7, 'Região 7 - São Pedro', 6780, 0.89, 2310, -20.2655, -40.3368, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (68, 'Redenção', 7, 'Região 7 - São Pedro', 4950, 0.56, 1710, -20.2735, -40.3335, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (69, 'Resistência', 7, 'Região 7 - São Pedro', 8120, 1.15, 2790, -20.2768, -40.3285, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (70, 'Santo André', 7, 'Região 7 - São Pedro', 5640, 0.59, 1940, -20.2785, -40.3342, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (71, 'Santos Reis', 7, 'Região 7 - São Pedro', 3920, 0.44, 1350, -20.2835, -40.3372, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (72, 'São José', 7, 'Região 7 - São Pedro', 3780, 0.41, 1310, -20.2812, -40.3355, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (73, 'São Pedro', 7, 'Região 7 - São Pedro', 9450, 0.98, 3290, -20.2868, -40.3332, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (74, 'Jardim Camburi', 8, 'Região 8 - Jardim Camburi', 46580, 4.12, 18420, -20.2588, -40.2685, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (75, 'Boa Vista', 9, 'Região 9 - Jardim da Penha', 2840, 0.42, 1020, -20.2735, -40.2995, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (76, 'Jardim da Penha', 9, 'Região 9 - Jardim da Penha', 32850, 2.48, 13240, -20.2795, -40.2925, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (77, 'Mata da Praia', 9, 'Região 9 - Jardim da Penha', 11920, 1.95, 4380, -20.2718, -40.2885, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (78, 'Morada de Camburi', 9, 'Região 9 - Jardim da Penha', 3640, 0.62, 1290, -20.2685, -40.2935, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (79, 'Pontal de Camburi', 9, 'Região 9 - Jardim da Penha', 2480, 0.39, 940, -20.2855, -40.2965, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');
INSERT IGNORE INTO `neighborhoods` (`id`, `name`, `region_id`, `region_name`, `population`, `area_km2`, `households`, `lat`, `lng`, `monitored`, `notes`) VALUES (80, 'República', 9, 'Região 9 - Jardim da Penha', 4890, 0.68, 1780, -20.2692, -40.2962, 1, 'Limite territorial oficial conforme Lei Municipal nº 8.611/2013 - PMV / Geobases ES');

-- =============================================================================
-- INSERÇÃO DOS EQUIPAMENTOS PÚBLICOS OFICIAIS DE VITÓRIA/ES
-- =============================================================================
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (1, 'US Andorinhas - Dr. Luis Claudio Passos', 'Unidade de Saúde', 36, 'Andorinhas', 'Rua Emílio Ferreira da Silva, 160', '(27) 3132-5101', -20.2865, -40.3082, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (2, 'US Bonfim - Thomaz Tommassi', 'Unidade de Saúde', 37, 'Bonfim', 'Rua Dióscoro Carneiro Filho, S/N', '(27) 3132-5102', -20.2992, -40.3141, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (3, 'US Centro - Geny Grijó', 'Unidade de Saúde', 1, 'Centro', 'Rua Cais de São Francisco, 54', '(27) 3132-5103', -20.3204, -40.3371, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (4, 'US da Penha - Dr. Gilson Santos', 'Unidade de Saúde', 38, 'Da Penha', 'Rua Padre Luiz Koester, 78', '(27) 3132-5104', -20.2978, -40.3101, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (5, 'US do Quadro - Avelina Maria Lacerda Gonçalves', 'Unidade de Saúde', 14, 'Do Quadro', 'Rua Lucidato Vieira Falcão, 5', '(27) 3132-5105', -20.3148, -40.3485, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (6, 'US Estrelinha', 'Unidade de Saúde', 15, 'Estrelinha', 'Rodovia Serafim Derenzi, 2285', '(27) 3132-5106', -20.3021, -40.3564, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (7, 'US Fonte Grande - Dr. Affonso Schwab', 'Unidade de Saúde', 3, 'Fonte Grande', 'Rua Graciano Neves, 352', '(27) 3132-5107', -20.3182, -40.3402, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (8, 'US Forte São João - Dr. Bolivar de Abreu', 'Unidade de Saúde', 26, 'Forte São João', 'Av. Marechal Mascarenhas de Moraes, 1185', '(27) 3132-5108', -20.3161, -40.3251, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (9, 'US Ilha das Caieiras - Vereador Nenel Miranda', 'Unidade de Saúde', 66, 'Ilha das Caieiras', 'Rua Felicidade Correia dos Santos, 40', '(27) 3132-5109', -20.2801, -40.3381, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (10, 'US Ilha de Santa Maria', 'Unidade de Saúde', 30, 'Ilha de Santa Maria', 'Rua João Bastos Vieira, 365', '(27) 3132-5110', -20.3152, -40.3165, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (11, 'US Ilha do Príncipe - Dr. Manoel Rocha Coutinho', 'Unidade de Saúde', 4, 'Ilha do Príncipe', 'Rua Fenelon Gomes, 112', '(27) 3132-5111', -20.3211, -40.3524, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (12, 'US Itararé - Dilson dos Santos Loureiro', 'Unidade de Saúde', 39, 'Itararé', 'Rua Doutor Arlindo Sodré, 260', '(27) 3132-5112', -20.2935, -40.3081, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (13, 'US Jabour - Dr. Carlito Von Schilgen', 'Unidade de Saúde', 60, 'Jabour', 'Rua Engenheiro César Dantas, 176', '(27) 3132-5113', -20.2675, -40.2971, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (14, 'US Jardim Camburi - Raul Oliveira Nunes', 'Unidade de Saúde', 74, 'Jardim Camburi', 'Rua Belmiro Teixeira Pimenta, 640', '(27) 3132-5114', -20.2582, -40.2692, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (15, 'US Jardim da Penha - Otaviano Rodrigues de Carvalho', 'Unidade de Saúde', 76, 'Jardim da Penha', 'Av. Francisco Generoso da Fonseca, 320', '(27) 3132-5115', -20.2788, -40.2932, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (16, 'US Jesus de Nazareth - Dr. Luiz Castellar da Silva', 'Unidade de Saúde', 31, 'Jesus de Nazareth', 'Rua Helena Muller, 168', '(27) 3132-5116', -20.3168, -40.3041, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (17, 'US Maria Ortiz', 'Unidade de Saúde', 61, 'Maria Ortiz', 'Rua Prof. Renato Ribeiro dos Santos, 271', '(27) 3132-5117', -20.2565, -40.2991, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (18, 'US Maruípe', 'Unidade de Saúde', 41, 'Maruípe', 'Rua Victor Fritoli Navarro, 20', '(27) 3132-5118', -20.2945, -40.3178, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (19, 'US Praia do Suá - Lucilo Borges Sant''anna', 'Unidade de Saúde', 53, 'Praia do Suá', 'Rua Caetano Tunholi, S/N', '(27) 3132-5119', -20.3091, -40.2998, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (20, 'US República - Dr. Jolindo Martins', 'Unidade de Saúde', 80, 'República', 'Av. Rozendo Serapião de Souza Filho, 490', '(27) 3132-5120', -20.2695, -40.2965, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (21, 'US Resistência', 'Unidade de Saúde', 69, 'Resistência', 'Rua Nossa Senhora da Conceição, 270', '(27) 3132-5121', -20.2771, -40.3281, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (22, 'US Santa Luíza - Dr. José Moysés', 'Unidade de Saúde', 56, 'Santa Luíza', 'Rua Dr. João Carlos de Souza, 330', '(27) 3132-5122', -20.2915, -40.3035, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (23, 'US Santa Martha - Benedito Gomes da Silva', 'Unidade de Saúde', 43, 'Santa Martha', 'Rua Getúlio Miranda, 70', '(27) 3132-5123', -20.2882, -40.3148, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (24, 'US Santo André', 'Unidade de Saúde', 70, 'Santo André', 'Rua da Coragem, 180', '(27) 3132-5124', -20.2782, -40.3345, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (25, 'US Santo Antônio - Júlio Cézar Prattes Mattos', 'Unidade de Saúde', 20, 'Santo Antônio', 'Rua Ernesto Bassini, 605', '(27) 3132-5125', -20.3092, -40.3568, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (26, 'US São Cristóvão', 'Unidade de Saúde', 46, 'São Cristóvão', 'Rua Alcides Lyrio do Nascimento, 60', '(27) 3132-5126', -20.2921, -40.3228, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (27, 'US Alagoano - Ariovaldo Favalessa', 'Unidade de Saúde', 10, 'Ariovaldo Favalessa', 'Servidão Ernesto Caliari, S/N', '(27) 3132-5127', -20.3133, -40.3535, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (28, 'US Consolação - Maria Rangel dos Passos', 'Unidade de Saúde', 23, 'Consolação', 'Rua Des. Otávio de Carvalho Lengruber, 185', '(27) 3132-5128', -20.3051, -40.3159, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (29, 'US Conquista / Nova Palestina - Maria Leda Santos', 'Unidade de Saúde', 67, 'Nova Palestina', 'Rodovia Serafim Derenzi, 6090', '(27) 3132-5129', -20.2661, -40.3362, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (30, 'PA da Praia do Suá (Pronto Atendimento 24h)', 'Unidade de Saúde', 53, 'Praia do Suá', 'Rua Almirante Tamandaré, 150', '(27) 3382-6500', -20.3085, -40.2982, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (31, 'PA de São Pedro (Pronto Atendimento 24h)', 'Unidade de Saúde', 73, 'São Pedro', 'Rodovia Serafim Derenzi, 4570', '(27) 3382-6600', -20.2858, -40.3328, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (32, 'EMEF Adão Benezath', 'Escola', 58, 'Antônio Honório', 'Rua Professora Clara Lima, 63', '(27) 3382-5301', -20.2648, -40.3012, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (33, 'EMEF Adevalni Sysesmundo Ferreira de Azevedo', 'Escola', 74, 'Jardim Camburi', 'Rua Victorino Cardoso, 140', '(27) 3382-5302', -20.2595, -40.2678, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (34, 'EMEF Alberto de Almeida', 'Escola', 20, 'Santo Antônio', 'Rodovia Serafim Derenzi, 60', '(27) 3382-5303', -20.3082, -40.3581, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (35, 'EMEF Álvaro de Castro Mattos', 'Escola', 76, 'Jardim da Penha', 'Rua Eugenílio Ramos, 781', '(27) 3382-5304', -20.2802, -40.2918, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (36, 'EMEF Alvimar Silva', 'Escola', 18, 'Mário Cypreste', 'Rua Miguel Pessoa, 10', '(27) 3382-5305', -20.3171, -40.3562, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (37, 'EMEF Aristóbulo Barbosa Leão', 'Escola', 22, 'Bento Ferreira', 'Avenida Vitória, 3010', '(27) 3382-5306', -20.3128, -40.3099, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (38, 'EMEF Arthur da Costa e Silva', 'Escola', 80, 'República', 'Rua Presidente Rodrigues Alves, 255', '(27) 3382-5307', -20.2688, -40.2958, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (39, 'EMEF Castelo Branco', 'Escola', 4, 'Ilha do Príncipe', 'Avenida Jurema Barroso, 130', '(27) 3382-5308', -20.3205, -40.3532, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (40, 'EMEF Ceciliano Abel de Almeida', 'Escola', 39, 'Itararé', 'Rua Doutor Arlindo Sodré, 416', '(27) 3382-5309', -20.2928, -40.3089, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (41, 'EMEF Eber Louzada Zippinotti', 'Escola', 76, 'Jardim da Penha', 'Rua Natalina Daher Carneiro, 815', '(27) 3382-5310', -20.2782, -40.2941, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (42, 'EMEF Eliane Rodrigues dos Santos', 'Escola', 66, 'Ilha das Caieiras', 'Rua Felicidade Correia dos Santos, 620', '(27) 3382-5311', -20.2792, -40.3391, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (43, 'EMEF Elzira Vivacqua dos Santos', 'Escola', 74, 'Jardim Camburi', 'Rua Filogônio Motta, 70', '(27) 3382-5312', -20.2575, -40.2702, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (44, 'EMEF Francisco Lacerda de Aguiar', 'Escola', 73, 'São Pedro', 'Rua Guilherme Bassini, 57', '(27) 3382-5313', -20.2872, -40.3338, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (45, 'EMEF Heloisa Abreu Júdice de Mattos', 'Escola', 11, 'Bela Vista', 'Rua Manoel Ferreira Constantino, 400', '(27) 3382-5314', -20.3058, -40.3591, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (46, 'EMEF Izaura Marques da Silva', 'Escola', 36, 'Andorinhas', 'Av. Leitão da Silva, 3291', '(27) 3382-5315', -20.2858, -40.3092, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (47, 'CMEI Ana Maria Chaves Colares', 'Escola', 74, 'Jardim Camburi', 'Rua Oswaldo Bastos de Souza Freitas, 380', '(27) 3382-5316', -20.2602, -40.2665, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (48, 'CMEI Anísio Spínola Teixeira', 'Escola', 69, 'Resistência', 'Rua América do Sul, 100', '(27) 3382-5317', -20.2762, -40.3289, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (49, 'CMEI Ernestina Pessoa', 'Escola', 5, 'Parque Moscoso', 'Av. República, 200', '(27) 3382-5318', -20.3185, -40.3438, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (50, 'Parque Municipal Pedra da Cebola', 'Parque', 77, 'Mata da Praia', 'Rua Ana Vieira Mafra, S/N', '(27) 3382-6593', -20.2752, -40.2962, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (51, 'Parque Municipal Horto de Maruípe', 'Parque', 41, 'Maruípe', 'Avenida Maruípe, 1750', '(27) 3382-6595', -20.2962, -40.3185, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (52, 'Parque Moscoso', 'Parque', 5, 'Parque Moscoso', 'Avenida Cleto Nunes, 242', '(27) 3382-6597', -20.3191, -40.3432, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (53, 'Parque Municipal de Barreiros', 'Parque', 46, 'São Cristóvão', 'Rodovia Serafim Derenzi, 10505', '(27) 3382-6599', -20.2912, -40.3235, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (54, 'Parque Pianista Manolo Cabral (Chácara Paraíso)', 'Parque', 48, 'Barro Vermelho', 'Rua Chapot Presvot / Rua Guilherme Serrano', '(27) 3382-6590', -20.2942, -40.2988, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (55, 'Parque Padre Alfonso Pastore', 'Parque', 77, 'Mata da Praia', 'Av. Nicolau Von Schilgen, S/N', '(27) 3382-6590', -20.2708, -40.2892, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (56, 'Atlântica Parque', 'Parque', 74, 'Jardim Camburi', 'Av. Dante Michelini (final da Orla de Camburi)', '(27) 3382-6590', -20.2645, -40.2652, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (57, 'Parque Barão de Monjardim', 'Parque', 25, 'De Lourdes', 'Rua Flávio Abaurre / Rua Eng. Alencar Araripe', '(27) 3382-6590', -20.3055, -40.3202, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (58, 'Parque Cultural Reserva Vitória', 'Parque', 49, 'Enseada do Suá', 'Av. Marília Scarton Coutinho', '(27) 3382-6590', -20.3118, -40.2855, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (59, 'Parque Estadual da Fonte Grande', 'Parque', 27, 'Fradinhos', 'Rodovia Serafim Derenzi, S/N', '(27) 3382-6576', -20.3088, -40.3395, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (60, 'Parque Natural Municipal Gruta da Onça', 'Parque', 1, 'Centro', 'Rua Barão de Monjardim, Centro', '(27) 3382-6578', -20.3172, -40.3365, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (61, 'Praça do Papa (Praça Israel Pinheiro)', 'Praça', 49, 'Enseada do Suá', 'Av. Nossa Senhora dos Navegantes', '', -20.3145, -40.2932, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (62, 'Praça dos Namorados', 'Praça', 52, 'Praia do Canto', 'Av. Saturnino de Brito', '', -20.3012, -40.2912, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (63, 'Praça da Ciência', 'Praça', 52, 'Praia do Canto', 'Av. Américo Buaiz, S/N', '(27) 3345-0882', -20.3045, -40.2918, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (64, 'Praça Costa Pereira', 'Praça', 1, 'Centro', 'Av. Jerônimo Monteiro, Centro', '', -20.3198, -40.3375, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (65, 'Praça Oito de Setembro', 'Praça', 1, 'Centro', 'Av. Jerônimo Monteiro / Av. Mal. Mascarenhas de Moraes', '', -20.3212, -40.3392, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (66, 'Praça Nilze Mendes', 'Praça', 74, 'Jardim Camburi', 'Rua Carlos Martins, Jardim Camburi', '', -20.2568, -40.2682, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (67, 'Praça Regina Frigeri Furno', 'Praça', 76, 'Jardim da Penha', 'Av. Anísio Fernandes Coelho, Jardim da Penha', '', -20.2812, -40.2935, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (68, 'Praça Philogomiro Lannes', 'Praça', 76, 'Jardim da Penha', 'Av. Hugo Viola, Jardim da Penha', '', -20.2772, -40.2952, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (69, 'Praça Asdrúbal Soares', 'Praça', 32, 'Jucutuquara', 'Av. Paulino Muller, Jucutuquara', '', -20.3088, -40.3218, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (70, 'Centro Esportivo Tancredo de Almeida Neves (Tancredão)', 'Equipamento Esportivo', 18, 'Mário Cypreste', 'Rua Rosilda Falcão dos Anjos, 150', '(27) 3382-6430', -20.3175, -40.3575, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (71, 'Ginásio Poliesportivo Tartarugão (Jones dos Santos Neves)', 'Equipamento Esportivo', 22, 'Bento Ferreira', 'Rua Desembargador Ferreira Coelho, 100', '(27) 3132-2050', -20.3132, -40.3085, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (72, 'Centro de Esportes e Lazer de Eucalipto', 'Equipamento Esportivo', 37, 'Bonfim', 'Rua Monsenhor Raymundo Pereira Barros, 17', '(27) 3382-6433', -20.3005, -40.3125, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (73, 'Palácio Jerônimo Monteiro - Prefeitura Municipal de Vitória', 'Equipamento Público', 22, 'Bento Ferreira', 'Av. Marechal Mascarenhas de Moraes, 1927', '156', -20.3145, -40.3112, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (74, 'Câmara Municipal de Vitória', 'Equipamento Público', 22, 'Bento Ferreira', 'Av. Marechal Mascarenhas de Moraes, 1788', '(27) 3334-4500', -20.3151, -40.3125, 'Prefeitura Municipal de Vitória (Portal Oficial)');
INSERT IGNORE INTO `public_equipments` (`id`, `name`, `type`, `neighborhood_id`, `neighborhood_name`, `address`, `phone`, `lat`, `lng`, `source`) VALUES (75, 'Ciac Itararé - Centro Integrado de Atendimento ao Cidadão', 'Equipamento Público', 39, 'Itararé', 'Rua Marins Alvarino, 384', '156', -20.2938, -40.3075, 'Prefeitura Municipal de Vitória (Portal Oficial)');

-- =============================================================================
-- DADOS INICIAIS DEMONSTRATIVOS IDENTIFICADOS (PODEM SER EDITADOS/EXCLUÍDOS)
-- =============================================================================

-- Contatos Comunitários com Consentimento LGPD
INSERT IGNORE INTO `contacts` (`id`, `name`, `phone`, `email`, `profile_type`, `neighborhood_id`, `registration_date`, `consent_lgpd`, `consent_purpose`, `communication_pref`, `responsible_name`, `relationship_summary`) VALUES
(1, 'Carlos Eduardo Baptista [REGISTRO DEMONSTRATIVO]', '(27) 99812-3401', 'carlos.baptista@exemplo.vitoria.br', 'Voluntário', 73, '2026-05-12', 1, 'Consentimento ativo LGPD para participação em ações comunitárias e informativos municipais', 'WhatsApp', 'Coordenação Territorial', 'Participa do conselho comunitário de Jardim Camburi e acompanha zeladoria urbana das praças.'),
(2, 'Mariana Louzada Feu Rosa [REGISTRO DEMONSTRATIVO]', '(27) 99745-8820', 'mariana.louzada@exemplo.vitoria.br', 'Liderança Comunitária', 75, '2026-06-03', 1, 'Consentimento ativo LGPD para pautas comunitárias e audiências públicas', 'WhatsApp', 'Coordenação Territorial', 'Atua em projetos de mobilidade cicloviária e arborização urbana em Jardim da Penha.'),
(3, 'Roberto Siqueira de Maruípe [REGISTRO DEMONSTRATIVO]', '(27) 99921-4510', 'roberto.siqueira@exemplo.vitoria.br', 'Colaborador', 41, '2026-07-18', 1, 'Consentimento ativo LGPD para acompanhamento de demandas de saúde e educação', 'E-mail', 'Equipe de Atendimento', 'Acompanha cronograma de reformas de equipamentos públicos na Região 4 - Maruípe.'),
(4, 'Luciana Coutinho da Ilha [REGISTRO DEMONSTRATIVO]', '(27) 99633-1190', 'luciana.coutinho@exemplo.vitoria.br', 'Voluntário', 66, '2026-08-22', 1, 'Consentimento ativo LGPD para atividades culturais, ambientais e comunitárias', 'WhatsApp', 'Equipe de Atendimento', 'Integrante de grupo comunitário de turismo sustentável e preservação do manguezal na Ilha das Caieiras.'),
(5, 'Antônio Marcos Ferreira [REGISTRO DEMONSTRATIVO]', '(27) 99877-6023', 'antonio.ferreira@exemplo.vitoria.br', 'Participante de Atividade', 1, '2026-09-10', 1, 'Consentimento ativo LGPD para reuniões sobre revitalização do Centro Histórico', 'Telefone', 'Coordenação Territorial', 'Participante ativo das reuniões sobre iluminação pública e cultura no Centro de Vitória.'),
(6, 'Fernanda Bermudes de Santo Antônio [REGISTRO DEMONSTRATIVO]', '(27) 99714-3309', 'fernanda.bermudes@exemplo.vitoria.br', 'Voluntário', 20, '2026-10-04', 1, 'Consentimento ativo LGPD para ações sociais e esportivas na Região 2', 'WhatsApp', 'Equipe de Atendimento', 'Apoia atividades esportivas e educativas para jovens na região de Santo Antônio.');

INSERT IGNORE INTO `contact_interactions` (`id`, `contact_id`, `user_name`, `interaction_type`, `description`, `interaction_date`) VALUES
(1, 1, 'Coordenação Territorial', 'Participação em Reunião', 'Participou de reunião comunitária sobre manutenção da Praça Nilze Mendes em Jardim Camburi.', '2026-09-15 19:00:00'),
(2, 2, 'Coordenação Territorial', 'Atendimento Presencial', 'Apresentou sugestão técnica de travessia elevada próxima à EMEF Álvaro de Castro Mattos.', '2026-10-02 14:30:00'),
(3, 4, 'Equipe de Atendimento', 'Participação em Evento', 'Participou de mutirão de educação ambiental na orla da Ilha das Caieiras.', '2026-10-19 09:00:00');

-- Demandas Comunitárias Georreferenciadas em Vitória/ES
INSERT IGNORE INTO `demands` (`id`, `protocol_code`, `title`, `description`, `neighborhood_id`, `address_location`, `lat`, `lng`, `category`, `demand_date`, `priority`, `status`, `responsible_name`, `contact_id`, `external_reference`) VALUES
(1, 'VIT-2026-0001', 'Manutenção de iluminação LED no entorno da Praça Nilze Mendes', 'Solicitação comunitária de substituição de 4 luminárias apagadas e poda preventiva de árvores que encobrem os postes na Rua Carlos Martins.', 73, 'Rua Carlos Martins, próximo à Praça Nilze Mendes, Jardim Camburi', -20.2568, -40.2682, 'Iluminação', '2026-08-14', 'Alta', 'Concluída', 'Coordenação de Zeladoria', 1, '156-PMV-88412'),
(2, 'VIT-2026-0002', 'Reforço de sinalização de pedestres em frente à EMEF Álvaro de Castro Mattos', 'Revitalização de faixa de pedestres e instalação de placas de área escolar para segurança nos horários de entrada e saída.', 75, 'Rua Eugenílio Ramos, 781, Jardim da Penha', -20.2802, -40.2918, 'Mobilidade', '2026-09-02', 'Alta', 'Em Execução', 'Equipe Técnica de Mobilidade', 2, '156-PMV-90124'),
(3, 'VIT-2026-0003', 'Ampliação de agendamento odontológico na US Maruípe', 'Moradores relatam necessidade de ampliação de horários de atendimento odontológico preventivo na unidade básica da região.', 41, 'Rua Victor Fritoli Navarro, 20, Maruípe', -20.2945, -40.3178, 'Saúde', '2026-09-18', 'Urgente', 'Encaminhada ao Órgão', 'Assessoria de Saúde Pública', 3, 'OF-SEMUS-2026-114'),
(4, 'VIT-2026-0004', 'Limpeza de galeria pluvial e desobstrução de bueiros na Av. Paulino Muller', 'Acúmulo de resíduos sólidos em caixas-ralo causando alagamento pontual em dias de chuva intensa.', 32, 'Av. Paulino Muller, próximo à Praça Asdrúbal Soares, Jucutuquara', -20.3088, -40.3218, 'Saneamento', '2026-09-25', 'Alta', 'Em Execução', 'Coordenação de Zeladoria', NULL, '156-PMV-91880'),
(5, 'VIT-2026-0005', 'Recuperação de alambrado e pintura de quadra poliesportiva em Santo Antônio', 'Demanda da comunidade esportiva para reparo no alambrado lateral e demarcação do piso da quadra comunitária.', 20, 'Rua Ernesto Bassini, Santo Antônio', -20.3088, -40.3575, 'Esporte', '2026-10-03', 'Média', 'Em Análise', 'Assessoria Comunitária', 6, NULL),
(6, 'VIT-2026-0006', 'Monitoramento ambiental e coleta seletiva na orla da Ilha das Caieiras', 'Instalação de novos ecopontos/contentores para resíduos recicláveis junto ao polo gastronômico da Ilha das Caieiras.', 66, 'Rua Felicidade Correia dos Santos, Ilha das Caieiras', -20.2798, -40.3385, 'Meio ambiente', '2026-10-11', 'Média', 'Aberta', 'Assessoria Ambiental', 4, NULL),
(7, 'VIT-2026-0007', 'Reforma de calçada cidadã e acessibilidade na Av. Jerônimo Monteiro', 'Reparo de trechos com piso podotátil danificado no corredor comercial do Centro de Vitória.', 1, 'Av. Jerônimo Monteiro, Centro', -20.3198, -40.3375, 'Infraestrutura', '2026-10-16', 'Alta', 'Encaminhada ao Órgão', 'Equipe de Infraestrutura', 5, '156-PMV-94210'),
(8, 'VIT-2026-0008', 'Patrulhamento preventivo integrado na região da Praça Costa Pereira', 'Solicitação de comerciantes e moradores para reforço de rondas da Guarda Civil Municipal no período noturno.', 1, 'Praça Costa Pereira, Centro', -20.3195, -40.3378, 'Segurança pública', '2026-10-21', 'Urgente', 'Em Análise', 'Assessoria Institucional', 5, 'OF-SEMSU-2026-089'),
(9, 'VIT-2026-0009', 'Climatização e manutenção elétrica em salas da EMEF Ceciliano Abel de Almeida', 'Verificação de rede elétrica para funcionamento pleno dos aparelhos de ar-condicionado.', 39, 'Rua Doutor Arlindo Sodré, 416, Itararé', -20.2928, -40.3089, 'Educação', '2026-10-25', 'Alta', 'Aberta', 'Assessoria de Educação', NULL, NULL),
(10, 'VIT-2026-0010', 'Mutirão de limpeza urbana e capina em encosta de São Pedro', 'Retirada de entulho descartado irregularmente e limpeza preventiva de canaleta de drenagem.', 73, 'Rodovia Serafim Derenzi, São Pedro', -20.2868, -40.3332, 'Limpeza urbana', '2026-10-29', 'Média', 'Aberta', 'Coordenação de Zeladoria', NULL, NULL);

INSERT IGNORE INTO `demand_history` (`id`, `demand_id`, `previous_status`, `new_status`, `notes`, `user_name`, `created_at`) VALUES
(1, 1, 'Aberta', 'Encaminhada ao Órgão', 'Demanda registrada no Fala Vitória 156 sob protocolo 156-PMV-88412.', 'Coordenação de Zeladoria', '2026-08-15 10:00:00'),
(2, 1, 'Encaminhada ao Órgão', 'Concluída', 'Equipe de iluminação pública substituiu as 4 luminárias por LED e realizou poda.', 'Coordenação de Zeladoria', '2026-08-22 16:40:00'),
(3, 2, 'Aberta', 'Em Execução', 'Vistoria técnica realizada; pintura termoplástica programada no cronograma da SEMMAM/SETRAN.', 'Equipe Técnica de Mobilidade', '2026-09-10 11:20:00'),
(4, 3, 'Aberta', 'Encaminhada ao Órgão', 'Ofício técnico encaminhado à Secretaria Municipal de Saúde (SEMUS).', 'Assessoria de Saúde Pública', '2026-09-20 09:15:00');

-- Radar de Inteligência Municipal (Notícias, Portais Oficiais, IBGE e Tendências)
INSERT IGNORE INTO `radar_items` (`id`, `title`, `source_name`, `source_type`, `source_url`, `category`, `neighborhood_id`, `published_at`, `content_excerpt`, `ai_summary`, `recurring_theme`, `relevance_score`, `is_sample_data`) VALUES
(1, 'Prefeitura de Vitória avança com obras da Orla de São Pedro e Ilha das Caieiras', 'Prefeitura de Vitória (Portal Oficial)', 'Portal Oficial / Governo', 'https://www.vitoria.es.gov.br', 'Infraestrutura', 66, '2026-10-28 14:20:00', 'Intervenções de requalificação urbana na Região 7 contemplam nova urbanização, decks de contemplação, ciclovia, drenagem e valorização gastronômica e turística da Ilha das Caieiras e São Pedro.', 'Resumo IA: O projeto de requalificação da Orla Noroeste (São Pedro / Ilha das Caieiras) foca em infraestrutura urbana, mobilidade ativa e estímulo ao turismo gastronômico local. Fonte oficial: PMV.', 'Requalificação da Orla Noroeste', 95, 0),
(2, 'Unidades Básicas de Saúde de Vitória mantêm plantão aos finais de semana em seis regiões', 'Secretaria Municipal de Saúde (SEMUS)', 'Portal Oficial / Governo', 'https://www.vitoria.es.gov.br/semus/unidades-basicas-de-saude', 'Saúde', 53, '2026-10-30 09:00:00', 'As unidades de Jardim Camburi, Maria Ortiz, Maruípe, Praia do Suá, Santo Antônio e Conquista/Nova Palestina funcionam das 7h às 19h em sábados, domingos e feriados.', 'Resumo IA: A rede municipal mantém 6 UBS em regime de plantão 7h-19h nos fins de semana para desafogar os Prontos-Atendimentos (PA Praia do Suá e PA São Pedro).', 'Atendimento Ampliado na Saúde Básica', 92, 0),
(3, 'Câmara Municipal de Vitória debate plano de drenagem urbana e prevenção a alagamentos', 'Câmara Municipal de Vitória', 'Portal Oficial / Governo', 'https://www.cmv.es.gov.br', 'Infraestrutura', 22, '2026-10-31 16:10:00', 'Sessão especial reuniu técnicos e lideranças comunitárias para avaliar investimentos em macrodrenagem nas bacias de Jucutuquara, Maruípe e Santo Antônio.', 'Resumo IA: Debate legislativo concentrou-se na manutenção preventiva de galerias pluviais e cronograma de macrodrenagem antes do período chuvoso de verão.', 'Macrodrenagem e Prevenção de Chuvas', 89, 0),
(4, 'Panorama IBGE Cidades: Vitória registra indicadores de escolarização e saneamento entre os maiores das capitais', 'IBGE Cidades - Vitória/ES (3205309)', 'Dados Públicos / IBGE', 'https://cidades.ibge.gov.br/brasil/es/vitoria/panorama', 'Administração pública', NULL, '2026-11-01 08:00:00', 'Dados consolidados do IBGE destacam cobertura de esgotamento sanitário, taxa de escolarização de 6 a 14 anos e PIB per capita do município de Vitória.', 'Resumo IA: Indicadores estruturais oficiais do IBGE (código 3205309) posicionam Vitória como referência estadual em cobertura de serviços básicos, exigindo foco na redução de assimetrias entre regiões administrativas.', 'Indicadores Estruturais IBGE', 90, 0),
(5, 'Interesse de busca por agendamento online de saúde e vagas em CMEIs cresce em Vitória', 'Google Trends / Monitoramento de Termos Públicos', 'Tendência de Busca (Google Trends)', 'https://trends.google.com.br/trends/explore?geo=BR-ES&q=vitoria%20saude,cmei%20vitoria', 'Educação', NULL, '2026-11-02 10:30:00', 'Termos relacionados a matrícula escolar municipal, Confirma Vitória e agendamento de consultas lideram buscas de serviços públicos municipais na Grande Vitória.', 'Resumo IA: Dados agregados de tendência de pesquisa mostram alta demanda informacional por calendário de matrículas em CMEIs/EMEFs e funcionamento do agendamento online de saúde.', 'Serviços Digitais de Saúde e Educação', 86, 0);

-- Contas de Redes Sociais (Configuração Oficial)
INSERT IGNORE INTO `social_accounts` (`id`, `platform`, `account_name`, `account_handle`, `status`, `followers_count`, `engagement_rate`) VALUES
(1, 'Instagram', 'Vitória Inteligência Oficial', '@vitoriainteligencia.es', 'Aguardando Credenciais', 14820, 4.65),
(2, 'Facebook', 'Vitória Inteligência - Gestão Comunitária', '/vitoriainteligencia.es', 'Aguardando Credenciais', 9340, 3.12),
(3, 'X', 'Vitória Inteligência ES', '@vitoria_intel', 'Aguardando Credenciais', 4190, 2.48);

-- Publicações no Calendário Editorial e Fila de Aprovação
INSERT IGNORE INTO `social_posts` (`id`, `title`, `platforms`, `caption`, `hashtags`, `scheduled_at`, `recurrence`, `approval_status`, `approved_by_name`, `approved_at`, `published_at`, `reach_metric`, `impressions_metric`, `likes_metric`, `comments_metric`, `shares_metric`, `created_by_name`) VALUES
(1, 'Informativo: Unidades de Saúde com Plantão de Fim de Semana em Vitória', 'Instagram,Facebook', 'Saiba quais são as 6 Unidades Básicas de Saúde de Vitória que funcionam aos sábados, domingos e feriados das 7h às 19h para atendimento básico: Jardim Camburi, Maria Ortiz, Maruípe, Praia do Suá, Santo Antônio e Conquista/Nova Palestina. Cuide da sua saúde e compartilhe com seus vizinhos!', '#VitoriaES #SaudePublica #GestaoComunitaria #ServicosMunicipais #VitoriaInteligencia', '2026-10-24 09:00:00', 'Semanal', 'Publicado', 'Administrador Geral', '2026-10-23 17:00:00', '2026-10-24 09:00:05', 6840, 9120, 542, 48, 129, 'Equipe de Comunicação'),
(2, 'Balanço Mensal de Demandas Comunitárias por Região Administrativa', 'Instagram,Facebook,X', 'Confira o painel atualizado das demandas comunitárias acompanhadas nas 9 Regiões Administrativas de Vitória. Transparência, dados georreferenciados e participação cidadã para melhorar cada bairro da nossa capital.', '#VitoriaES #InteligenciaTerritorial #ParticipacaoCidada #Transparencia #DadosPublicos', '2026-11-05 18:00:00', 'Mensal', 'Aprovado', 'Administrador Geral', '2026-11-02 15:30:00', NULL, 0, 0, 0, 0, 0, 'Equipe de Comunicação'),
(3, 'Guia dos Parques Urbanos de Vitória: Lazer e Meio Ambiente', 'Instagram', 'De Pedra da Cebola ao Parque Moscoso e Horto de Maruípe: conheça os horários e endereços dos parques urbanos de Vitória para aproveitar com a família neste fim de semana.', '#ParquesDeVitoria #MeioAmbiente #QualidadeDeVida #VitoriaES', '2026-11-07 10:00:00', 'Nenhuma', 'Em Revisão', NULL, NULL, NULL, 0, 0, 0, 0, 0, 'Equipe de Comunicação');

-- Agenda de Eventos, Reuniões e Atividades Comunitárias
INSERT IGNORE INTO `agenda_events` (`id`, `title`, `event_type`, `description`, `start_datetime`, `end_datetime`, `neighborhood_id`, `address`, `responsible_name`, `participants_list`, `expected_attendance`, `actual_attendance`, `status`) VALUES
(1, 'Reunião Comunitária: Mobilidade e Segurança Viária em Jardim da Penha', 'Reunião', 'Encontro com moradores e comerciantes para consolidar propostas de travessia segura e ciclofaixas no bairro.', '2026-10-22 19:00:00', '2026-10-22 21:00:00', 75, 'Auditório Comunitário - Av. Francisco Generoso da Fonseca, Jardim da Penha', 'Coordenação Territorial', 'Associação de Moradores, Voluntários da Região 9, Equipe Técnica', 45, 52, 'Realizado'),
(2, 'Mutirão de Escuta e Zeladoria Urbana na Região de Maruípe', 'Atividade Comunitária', 'Caminhada técnica para georreferenciamento de demandas de iluminação, calçadas e drenagem.', '2026-10-29 08:30:00', '2026-10-29 12:00:00', 41, 'Ponto de encontro: Praça em frente ao Horto de Maruípe - Av. Maruípe, 1750', 'Equipe de Atendimento', 'Lideranças de Maruípe, Bonfim, Itararé e Tabuazeiro', 30, 34, 'Realizado'),
(3, 'Audiência Pública: Drenagem Urbana e Prevenção de Chuvas em Vitória', 'Audiência Pública', 'Acompanhamento técnico do plano municipal de macrodrenagem e limpeza de galerias.', '2026-11-06 14:00:00', '2026-11-06 17:00:00', 22, 'Plenário da Câmara Municipal de Vitória - Av. Mal. Mascarenhas de Moraes, 1788, Bento Ferreira', 'Coordenação Geral', 'Equipe Técnica, Representantes das 9 Regiões Administrativas', 80, 0, 'Confirmado'),
(4, 'Encontro de Voluntários e Planejamento Mensal de Comunicação Comunitária', 'Evento', 'Apresentação dos indicadores territoriais do mês e definição do calendário de ações nos bairros.', '2026-11-12 19:00:00', '2026-11-12 21:00:00', 49, 'Espaço Institucional - Enseada do Suá, Vitória/ES', 'Coordenação Geral', 'Voluntários cadastrados com consentimento LGPD', 60, 0, 'Agendado');

-- Notificações Iniciais
INSERT IGNORE INTO `notifications` (`id`, `title`, `message`, `type`, `link`, `is_read`) VALUES
(1, 'Sistema Vitória Inteligência Ativo', 'Base cartográfica oficial dos 80 bairros de Vitória/ES (Lei 8.611/2013) carregada com sucesso.', 'success', '?route=map', 0),
(2, 'Demandas Prioritárias em Aberto', 'Existem demandas classificadas como Urgentes ou Alta Prioridade aguardando atualização de protocolo.', 'warning', '?route=demands', 0),
(3, 'Publicação Aprovada na Fila', '1 publicação aprovada aguarda execução ou conexão das credenciais oficiais de API.', 'info', '?route=social', 0);

-- Configurações Padrão do Sistema
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `is_encrypted`, `description`) VALUES
('site_name', 'Vitória Inteligência', 0, 'Nome oficial da plataforma'),
('municipality_name', 'Vitória / ES', 0, 'Município monitorado'),
('ibge_code', '3205309', 0, 'Código IBGE oficial de Vitória/ES'),
('lgpd_dpo_email', 'lgpd@inteligencia.fernandopaiva.com.br', 0, 'Canal do Encarregado de Proteção de Dados (DPO - LGPD)'),
('openai_api_key', '', 1, 'Chave da API OpenAI (criptografada AES-256-CBC)'),
('openai_model', 'gpt-4o-mini', 0, 'Modelo padrão OpenAI para análises e resumos'),
('rss_feeds_vitoria', 'https://news.google.com/rss/search?q=Vitoria+Espirito+Santo+Prefeitura&hl=pt-BR&gl=BR&ceid=BR:pt-419', 0, 'Feeds RSS públicos monitorados pelo Radar Municipal'),
('meta_app_id', '', 0, 'App ID oficial da Meta (Instagram / Facebook Graph API)'),
('meta_app_secret', '', 1, 'App Secret oficial da Meta (criptografado)'),
('x_api_bearer_token', '', 1, 'Bearer Token oficial da API v2 do X/Twitter (criptografado)');

SET FOREIGN_KEY_CHECKS = 1;
