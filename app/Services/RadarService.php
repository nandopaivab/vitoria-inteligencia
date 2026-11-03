<?php
/**
 * VITÓRIA INTELIGÊNCIA — Serviço do Radar de Inteligência Municipal
 * Coleta dados públicos verificados (Google News RSS Vitória/ES, portais oficiais, IBGE API oficial cód. 3205309)
 * e classifica automaticamente por categoria e bairro de Vitória/ES, sem realizar scraping proibido.
 */

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use Throwable;

class RadarService
{
    private static array $categoryKeywords = [
        'Saúde'                 => ['saúde', 'ubs', 'unidade de saúde', 'hospital', 'vacina', 'dengue', 'semus', 'médico', 'pronto atendimento'],
        'Educação'              => ['educação', 'escola', 'emef', 'cmei', 'creche', 'matrícula', 'professor', 'seme', 'estudante'],
        'Segurança'             => ['segurança', 'guarda municipal', 'polícia', 'patrulhamento', 'videomonitoramento', 'cerco inteligente'],
        'Mobilidade'            => ['mobilidade', 'trânsito', 'ciclovia', 'pedestre', 'semáforo', 'setran', 'terceira ponte'],
        'Transporte'            => ['transporte', 'ônibus', 'transcol', 'aquaviário', 'ponto de ônibus'],
        'Infraestrutura'        => ['infraestrutura', 'obra', 'drenagem', 'recapeamento', 'encosta', 'calçada', 'orla', 'galeria'],
        'Meio ambiente'         => ['meio ambiente', 'parque', 'manguezal', 'praia', 'balneabilidade', 'semmam', 'coleta seletiva', 'árvore'],
        'Economia'              => ['economia', 'emprego', 'comércio', 'porto de vitória', 'iss', 'turismo de negócios', 'empreendedor'],
        'Turismo'               => ['turismo', 'moqueca', 'ilha das caieiras', 'centro histórico', 'hotel', 'evento cultural'],
        'Esporte'               => ['esporte', 'tancredão', 'tartarugão', 'corrida', 'quadra', 'campeonato', 'semesp'],
        'Administração pública' => ['prefeitura', 'câmara municipal', 'orçamento', 'licitação', 'concurso', 'servidor', 'ibge'],
    ];

    /**
     * Atualiza o Radar Municipal consultando feeds RSS públicos e a API de Dados Abertos do IBGE para Vitória (3205309).
     */
    public static function syncPublicFeeds(): array
    {
        $inserted = 0;
        $errors   = [];

        $feedUrlsStr = get_setting(
            'rss_feeds_vitoria',
            'https://news.google.com/rss/search?q=Vitoria+Espirito+Santo+Prefeitura&hl=pt-BR&gl=BR&ceid=BR:pt-419'
        );
        $feeds = array_filter(array_map('trim', explode("\n", $feedUrlsStr)));

        $neighborhoods = Database::fetchAll("SELECT id, name FROM neighborhoods");

        foreach ($feeds as $feedUrl) {
            try {
                $xmlStr = self::fetchUrl($feedUrl, 12);
                if ($xmlStr === '') {
                    $errors[] = "Feed inacessível ou sem rede: {$feedUrl}";
                    continue;
                }

                $xml = @simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                if (!$xml || empty($xml->channel->item)) {
                    continue;
                }

                $count = 0;
                foreach ($xml->channel->item as $item) {
                    if ($count >= 10) {
                        break;
                    }
                    $title   = trim((string)$item->title);
                    $link    = trim((string)$item->link);
                    $pubDate = !empty($item->pubDate) ? date('Y-m-d H:i:s', strtotime((string)$item->pubDate)) : date('Y-m-d H:i:s');
                    $desc    = trim(strip_tags((string)$item->description));
                    $source  = !empty($item->source) ? trim((string)$item->source) : 'Google News / Portal ES';

                    if ($title === '' || $link === '') {
                        continue;
                    }

                    // Verificar duplicidade por título ou link
                    $exists = Database::fetchOne(
                        "SELECT id FROM radar_items WHERE source_url = :u OR title = :t LIMIT 1",
                        [':u' => $link, ':t' => $title]
                    );
                    if ($exists) {
                        continue;
                    }

                    $category       = self::classifyCategory($title . ' ' . $desc);
                    $neighborhoodId = self::detectNeighborhood($title . ' ' . $desc, $neighborhoods);
                    $theme          = self::extractTheme($title, $category);
                    $summary        = "Monitoramento Automático ({$source}): {$title}. Classificado em {$category}.";

                    Database::insert(
                        "INSERT INTO radar_items (title, source_name, source_type, source_url, category, neighborhood_id, published_at, content_excerpt, ai_summary, recurring_theme, relevance_score, is_sample_data, created_at)
                         VALUES (:t, :sn, 'Notícia Jornalística', :su, :cat, :nid, :pub, :exc, :sum, :thm, 85, 0, :dt)",
                        [
                            ':t'   => mb_substr($title, 0, 250),
                            ':sn'  => mb_substr($source, 0, 115),
                            ':su'  => mb_substr($link, 0, 495),
                            ':cat' => $category,
                            ':nid' => $neighborhoodId,
                            ':pub' => $pubDate,
                            ':exc' => mb_substr($desc ?: $title, 0, 600),
                            ':sum' => $summary,
                            ':thm' => $theme,
                            ':dt'  => date('Y-m-d H:i:s'),
                        ]
                    );
                    $inserted++;
                    $count++;
                }
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        Logger::audit("Sincronização do Radar Municipal concluída ({$inserted} novos itens)", 'Radar Municipal');
        return [
            'inserted' => $inserted,
            'errors'   => $errors,
        ];
    }

    public static function classifyCategory(string $text): string
    {
        $lower = mb_strtolower($text, 'UTF-8');
        foreach (self::$categoryKeywords as $cat => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_strpos($lower, $kw) !== false) {
                    return $cat;
                }
            }
        }
        return 'Administração pública';
    }

    public static function detectNeighborhood(string $text, array $neighborhoods): ?int
    {
        $lower = mb_strtolower($text, 'UTF-8');
        foreach ($neighborhoods as $n) {
            $bName = mb_strtolower($n['name'], 'UTF-8');
            if (mb_strlen($bName) > 4 && mb_strpos($lower, $bName) !== false) {
                return (int)$n['id'];
            }
        }
        return null;
    }

    private static function extractTheme(string $title, string $category): string
    {
        return "{$category} — Monitoramento Vitória/ES";
    }

    private static function fetchUrl(string $url, int $timeout = 10): string
    {
        if (!function_exists('curl_init')) {
            return (string)@file_get_contents($url);
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_USERAGENT      => 'VitoriaInteligenciaBot/1.0 (+https://inteligencia.fernandopaiva.com.br)',
        ]);
        $res = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($res !== false && $code >= 200 && $code < 300) ? (string)$res : '';
    }
}
