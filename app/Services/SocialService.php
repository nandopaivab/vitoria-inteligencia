<?php
/**
 * VITÓRIA INTELIGÊNCIA — Serviço da Central de Redes Sociais (APIs Oficiais Meta Graph API e X API v2)
 *
 * Regras Obrigatórias:
 * - As publicações somente são executadas quando houver autorização (approval_status = 'Aprovado')
 *   E integração válida com token oficial configurado.
 * - Não utiliza ferramentas não oficiais para contornar limites das plataformas.
 * - Suporta recorrência Diária, Semanal e Mensal após publicação autorizada.
 */

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Security;

class SocialService
{
    /**
     * Processa a fila de publicações aprovadas cujo agendamento já venceu.
     * Só publica externamente se a conta da plataforma possuir token oficial válido;
     * caso contrário, mantém na fila e registra no log o motivo transparente.
     */
    public static function processApprovedQueue(): array
    {
        $now = date('Y-m-d H:i:s');
        $duePosts = Database::fetchAll(
            "SELECT * FROM social_posts WHERE approval_status = 'Aprovado' AND scheduled_at <= :now ORDER BY scheduled_at ASC",
            [':now' => $now]
        );

        $accounts = Database::fetchAll("SELECT * FROM social_accounts");
        $accMap   = [];
        foreach ($accounts as $acc) {
            $accMap[$acc['platform']] = $acc;
        }

        $publishedCount = 0;
        $blockedCount   = 0;
        $messages       = [];

        foreach ($duePosts as $post) {
            $platforms = array_filter(array_map('trim', explode(',', $post['platforms'])));
            $allValid  = true;
            $missingPlatforms = [];

            foreach ($platforms as $plat) {
                $acc = $accMap[$plat] ?? null;
                $token = $acc ? Security::decrypt($acc['access_token_encrypted']) : '';
                if (!$acc || $acc['status'] !== 'Conectada (API Oficial)' || empty($token)) {
                    $allValid = false;
                    $missingPlatforms[] = $plat;
                }
            }

            if (!$allValid) {
                $blockedCount++;
                $logMsg = "Publicação ID #{$post['id']} ('{$post['title']}') aprovada, mas aguardando token ativo da API oficial para: " . implode(', ', $missingPlatforms);
                Database::execute(
                    "UPDATE social_posts SET api_response_log = :log, updated_at = :dt WHERE id = :id",
                    [':log' => $logMsg, ':dt' => $now, ':id' => $post['id']]
                );
                $messages[] = $logMsg;
                continue;
            }

            // Se todas as plataformas selecionadas possuem credencial oficial válida, dispara via API Oficial
            $externalIds = [];
            $apiErrors   = [];

            foreach ($platforms as $plat) {
                $acc   = $accMap[$plat];
                $token = Security::decrypt($acc['access_token_encrypted']);
                $res   = self::dispatchToOfficialApi($plat, $acc['external_account_id'] ?? '', $token, $post);
                if ($res['ok']) {
                    $externalIds[] = $plat . ':' . $res['external_id'];
                } else {
                    $apiErrors[] = $plat . ': ' . $res['error'];
                }
            }

            if (empty($apiErrors)) {
                Database::execute(
                    "UPDATE social_posts
                     SET approval_status = 'Publicado',
                         published_at = :pub,
                         external_post_ids = :ext,
                         api_response_log = :log,
                         updated_at = :dt
                     WHERE id = :id",
                    [
                        ':pub' => $now,
                        ':ext' => implode('; ', $externalIds),
                        ':log' => "Publicado via API Oficial em {$now}: " . implode(', ', $externalIds),
                        ':dt'  => $now,
                        ':id'  => $post['id'],
                    ]
                );
                $publishedCount++;
                self::scheduleNextRecurrence($post);
                $messages[] = "Publicação #{$post['id']} enviada com sucesso via API oficial.";
            } else {
                Database::execute(
                    "UPDATE social_posts SET approval_status = 'Falha na API', api_response_log = :log, updated_at = :dt WHERE id = :id",
                    [
                        ':log' => "Erro retornado pela API oficial em {$now}: " . implode(' | ', $apiErrors),
                        ':dt'  => $now,
                        ':id'  => $post['id'],
                    ]
                );
                $messages[] = "Falha na API oficial para publicação #{$post['id']}: " . implode(' | ', $apiErrors);
            }
        }

        Logger::audit("Fila de Redes Sociais processada: {$publishedCount} publicadas, {$blockedCount} aguardando credencial API", 'Redes Sociais');
        return [
            'published' => $publishedCount,
            'blocked'   => $blockedCount,
            'messages'  => $messages,
        ];
    }

    /**
     * Cria automaticamente o próximo agendamento caso a publicação possua recorrência Diária, Semanal ou Mensal.
     */
    public static function scheduleNextRecurrence(array $post): void
    {
        $rec = $post['recurrence'] ?? 'Nenhuma';
        if ($rec === 'Nenhuma') {
            return;
        }

        $baseTime = strtotime($post['scheduled_at']) ?: time();
        if ($rec === 'Diária') {
            $nextDate = date('Y-m-d H:i:s', strtotime('+1 day', $baseTime));
        } elseif ($rec === 'Semanal') {
            $nextDate = date('Y-m-d H:i:s', strtotime('+1 week', $baseTime));
        } elseif ($rec === 'Mensal') {
            $nextDate = date('Y-m-d H:i:s', strtotime('+1 month', $baseTime));
        } else {
            return;
        }

        Database::insert(
            "INSERT INTO social_posts (title, platforms, caption, hashtags, media_asset_id, media_url, scheduled_at, recurrence, approval_status, created_by_name, created_at, updated_at)
             VALUES (:title, :plat, :cap, :tags, :mid, :murl, :sched, :rec, 'Em Revisão', :creator, :dt, :dt)",
            [
                ':title'   => $post['title'] . " ({$rec})",
                ':plat'    => $post['platforms'],
                ':cap'     => $post['caption'],
                ':tags'    => $post['hashtags'],
                ':mid'     => $post['media_asset_id'],
                ':murl'    => $post['media_url'],
                ':sched'   => $nextDate,
                ':rec'     => $rec,
                ':creator' => $post['created_by_name'],
                ':dt'      => date('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Dispara requisição HTTP real para os endpoints oficiais da Meta Graph API v19.0 ou X API v2.
     */
    private static function dispatchToOfficialApi(string $platform, string $externalId, string $token, array $post): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'Extensão cURL ausente no PHP.'];
        }

        $fullText = trim($post['caption'] . "\n\n" . ($post['hashtags'] ?? ''));

        if ($platform === 'Facebook') {
            if ($externalId === '') {
                return ['ok' => false, 'error' => 'Page ID do Facebook não informado.'];
            }
            $url = "https://graph.facebook.com/v19.0/" . rawurlencode($externalId) . "/feed";
            $ch  = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_POSTFIELDS     => http_build_query([
                    'message'      => $fullText,
                    'access_token' => $token,
                ]),
            ]);
            $res  = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $json = json_decode((string)$res, true);
            if ($code >= 200 && $code < 300 && !empty($json['id'])) {
                return ['ok' => true, 'external_id' => $json['id']];
            }
            return ['ok' => false, 'error' => $json['error']['message'] ?? "HTTP {$code}"];
        }

        if ($platform === 'Instagram') {
            if ($externalId === '' || empty($post['media_url'])) {
                return ['ok' => false, 'error' => 'Instagram Graph API requer IG Business Account ID e URL pública de imagem.'];
            }
            $url = "https://graph.facebook.com/v19.0/" . rawurlencode($externalId) . "/media";
            $ch  = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_POSTFIELDS     => http_build_query([
                    'image_url'    => $post['media_url'],
                    'caption'      => $fullText,
                    'access_token' => $token,
                ]),
            ]);
            $res  = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $json = json_decode((string)$res, true);
            if ($code >= 200 && $code < 300 && !empty($json['id'])) {
                return ['ok' => true, 'external_id' => $json['id']];
            }
            return ['ok' => false, 'error' => $json['error']['message'] ?? "HTTP {$code}"];
        }

        if ($platform === 'X') {
            $url = "https://api.twitter.com/2/tweets";
            $ch  = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token,
                ],
                CURLOPT_POSTFIELDS     => json_encode(['text' => mb_substr($fullText, 0, 280)]),
            ]);
            $res  = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $json = json_decode((string)$res, true);
            if ($code >= 200 && $code < 300 && !empty($json['data']['id'])) {
                return ['ok' => true, 'external_id' => $json['data']['id']];
            }
            return ['ok' => false, 'error' => $json['detail'] ?? "HTTP {$code}"];
        }

        return ['ok' => false, 'error' => 'Plataforma desconhecida'];
    }
}
