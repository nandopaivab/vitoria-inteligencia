<?php
/**
 * VITÓRIA INTELIGÊNCIA — Exemplo de Arquivo de Configuração
 * Este arquivo é gerado automaticamente pelo instalador visual (/install).
 * Caso deseje configurar manualmente no Plesk, copie este arquivo para config/config.php
 */

return [
    'app_name'    => 'Vitória Inteligência',
    'app_url'     => 'https://inteligencia.fernandopaiva.com.br',
    'timezone'    => 'America/Sao_Paulo',
    'environment' => 'production',
    'debug'       => false,

    // Banco de Dados MySQL 8 / MariaDB (Plesk)
    'db' => [
        'driver'   => 'mysql', // 'mysql' para Plesk (suporta 'sqlite' apenas para testes automatizados locais)
        'host'     => 'localhost',
        'port'     => '3306',
        'database' => 'vitoria_inteligencia',
        'username' => 'vitoria_user',
        'password' => 'SUA_SENHA_FORTE_AQUI',
        'charset'  => 'utf8mb4',
    ],

    // Chave Criptográfica AES-256-CBC para proteção de tokens de API e dados sensíveis (32 bytes hex)
    'app_key' => 'e8f4b2a91c3d7e6054a18b9c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e',

    // Configurações de Sessão e Segurança
    'session_name'     => 'VITORIA_INTEL_SESS',
    'session_lifetime' => 7200, // 2 horas
    'installed_at'     => '2026-11-03 06:00:00',
];
