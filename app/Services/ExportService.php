<?php
/**
 * VITÓRIA INTELIGÊNCIA — Gerador Nativo de Relatórios em PDF (%PDF-1.4) e Excel (.xls / .csv)
 * Não exige Composer nem bibliotecas externas. Compatível com qualquer servidor Plesk PHP.
 *
 * Relatórios suportados:
 * 1. demands_by_neighborhood (Demandas por bairro)
 * 2. demands_by_category (Demandas por categoria)
 * 3. monthly_evolution (Evolução mensal)
 * 4. municipal_indicators (Indicadores municipais dos 80 bairros)
 * 5. monitored_news (Notícias monitoradas no Radar)
 * 6. community_activities (Atividades comunitárias e contatos LGPD)
 * 7. social_media (Redes sociais e métricas)
 * 8. event_attendance (Participação em eventos e agenda)
 */

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

class ExportService
{
    public static function getReportData(string $reportType): array
    {
        switch ($reportType) {
            case 'demands_by_neighborhood':
                $title   = 'Relatório de Demandas Comunitárias por Bairro — Vitória/ES';
                $headers = ['Bairro Oficial', 'Região Administrativa', 'População', 'Total Demandas', 'Abertas/Andamento', 'Concluídas'];
                $rowsRaw = Database::fetchAll(
                    "SELECT n.name, n.region_name, n.population,
                            COUNT(d.id) as total,
                            SUM(CASE WHEN d.status != 'Concluída' AND d.id IS NOT NULL THEN 1 ELSE 0 END) as abertas,
                            SUM(CASE WHEN d.status = 'Concluída' THEN 1 ELSE 0 END) as concluidas
                     FROM neighborhoods n
                     LEFT JOIN demands d ON d.neighborhood_id = n.id
                     GROUP BY n.id, n.name, n.region_name, n.population
                     HAVING COUNT(d.id) > 0
                     ORDER BY total DESC, n.name ASC"
                );
                $rows = [];
                foreach ($rowsRaw as $r) {
                    $rows[] = [
                        $r['name'],
                        $r['region_name'],
                        number_format((int)$r['population'], 0, ',', '.'),
                        (int)$r['total'],
                        (int)$r['abertas'],
                        (int)$r['concluidas'],
                    ];
                }
                return ['title' => $title, 'headers' => $headers, 'rows' => $rows];

            case 'demands_by_category':
                $title   = 'Relatório de Demandas Municipais por Categoria — Vitória/ES';
                $headers = ['Categoria', 'Total de Protocolos', 'Prioridade Alta/Urgente', 'Concluídas', 'Taxa de Resolução (%)'];
                $rowsRaw = Database::fetchAll(
                    "SELECT category,
                            COUNT(*) as total,
                            SUM(CASE WHEN priority IN ('Alta', 'Urgente') THEN 1 ELSE 0 END) as criticas,
                            SUM(CASE WHEN status = 'Concluída' THEN 1 ELSE 0 END) as concluidas
                     FROM demands
                     GROUP BY category
                     ORDER BY total DESC"
                );
                $rows = [];
                foreach ($rowsRaw as $r) {
                    $tot  = max((int)$r['total'], 1);
                    $conc = (int)$r['concluidas'];
                    $rate = round(($conc / $tot) * 100, 1) . '%';
                    $rows[] = [$r['category'], (int)$r['total'], (int)$r['criticas'], $conc, $rate];
                }
                return ['title' => $title, 'headers' => $headers, 'rows' => $rows];

            case 'monthly_evolution':
                $title   = 'Relatório de Evolução Mensal de Demandas e Atendimentos — Vitória/ES';
                $headers = ['Ano/Mês', 'Demandas Registradas', 'Demandas Concluídas', 'Novos Contatos LGPD'];
                $demRows = Database::fetchAll(
                    "SELECT SUBSTR(demand_date, 1, 7) as mes,
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'Concluída' THEN 1 ELSE 0 END) as concluidas
                     FROM demands
                     GROUP BY SUBSTR(demand_date, 1, 7)
                     ORDER BY mes ASC"
                );
                $rows = [];
                foreach ($demRows as $r) {
                    $mes = $r['mes'];
                    $cntContacts = (int)Database::fetchColumn(
                        "SELECT COUNT(*) FROM contacts WHERE SUBSTR(registration_date, 1, 7) = :m",
                        [':m' => $mes]
                    );
                    $rows[] = [$mes, (int)$r['total'], (int)$r['concluidas'], $cntContacts];
                }
                return ['title' => $title, 'headers' => $headers, 'rows' => $rows];

            case 'municipal_indicators':
                $title   = 'Indicadores Territoriais dos 80 Bairros Oficiais de Vitória/ES (Lei 8.611/2013)';
                $headers = ['ID', 'Bairro', 'Região Administrativa', 'População', 'Área (km²)', 'Domicílios', 'Equipamentos Públicos', 'Demandas'];
                $rowsRaw = Database::fetchAll(
                    "SELECT n.id, n.name, n.region_name, n.population, n.area_km2, n.households,
                            (SELECT COUNT(*) FROM public_equipments pe WHERE pe.neighborhood_id = n.id) as equips,
                            (SELECT COUNT(*) FROM demands d WHERE d.neighborhood_id = n.id) as dems
                     FROM neighborhoods n
                     ORDER BY n.region_id ASC, n.name ASC"
                );
                $rows = [];
                foreach ($rowsRaw as $r) {
                    $rows[] = [
                        $r['id'],
                        $r['name'],
                        $r['region_name'],
                        number_format((int)$r['population'], 0, ',', '.'),
                        number_format((float)$r['area_km2'], 2, ',', '.'),
                        number_format((int)$r['households'], 0, ',', '.'),
                        (int)$r['equips'],
                        (int)$r['dems'],
                    ];
                }
                return ['title' => $title, 'headers' => $headers, 'rows' => $rows];

            case 'monitored_news':
                $title   = 'Relatório do Radar de Inteligência Municipal (Notícias e Fontes Públicas)';
                $headers = ['Data Publicação', 'Categoria', 'Tipo de Fonte', 'Veículo / Órgão', 'Título da Pauta', 'Tema Recorrente'];
                $rowsRaw = Database::fetchAll(
                    "SELECT published_at, category, source_type, source_name, title, recurring_theme
                     FROM radar_items
                     ORDER BY published_at DESC"
                );
                $rows = [];
                foreach ($rowsRaw as $r) {
                    $rows[] = [
                        date('d/m/Y H:i', strtotime($r['published_at'])),
                        $r['category'],
                        $r['source_type'],
                        $r['source_name'],
                        $r['title'],
                        $r['recurring_theme'] ?? '-',
                    ];
                }
                return ['title' => $title, 'headers' => $headers, 'rows' => $rows];

            case 'community_activities':
                $title   = 'Relatório de Gestão Comunitária e Contatos com Consentimento LGPD';
                $headers = ['Nome', 'Perfil', 'Bairro', 'Data Cadastro', 'Consentimento LGPD', 'Canal Preferido', 'Responsável'];
                $rowsRaw = Database::fetchAll(
                    "SELECT c.name, c.profile_type, n.name as bairro, c.registration_date, c.consent_lgpd, c.communication_pref, c.responsible_name
                     FROM contacts c
                     LEFT JOIN neighborhoods n ON n.id = c.neighborhood_id
                     ORDER BY c.registration_date DESC"
                );
                $rows = [];
                foreach ($rowsRaw as $r) {
                    $rows[] = [
                        $r['name'],
                        $r['profile_type'],
                        $r['bairro'] ?? 'Vitória',
                        date('d/m/Y', strtotime($r['registration_date'])),
                        ((int)$r['consent_lgpd'] === 1 ? 'Ativo (LGPD)' : 'Revogado'),
                        $r['communication_pref'],
                        $r['responsible_name'],
                    ];
                }
                return ['title' => $title, 'headers' => $headers, 'rows' => $rows];

            case 'social_media':
                $title   = 'Relatório da Central de Redes Sociais e Desempenho Editorial';
                $headers = ['Título da Publicação', 'Plataformas', 'Agendamento', 'Recorrência', 'Status Aprovação', 'Alcance', 'Curtidas/Comentários'];
                $rowsRaw = Database::fetchAll(
                    "SELECT title, platforms, scheduled_at, recurrence, approval_status, reach_metric, likes_metric, comments_metric
                     FROM social_posts
                     ORDER BY scheduled_at DESC"
                );
                $rows = [];
                foreach ($rowsRaw as $r) {
                    $rows[] = [
                        $r['title'],
                        $r['platforms'],
                        date('d/m/Y H:i', strtotime($r['scheduled_at'])),
                        $r['recurrence'],
                        $r['approval_status'],
                        number_format((int)$r['reach_metric'], 0, ',', '.'),
                        ((int)$r['likes_metric'] + (int)$r['comments_metric']),
                    ];
                }
                return ['title' => $title, 'headers' => $headers, 'rows' => $rows];

            case 'event_attendance':
            default:
                $title   = 'Relatório de Agenda e Participação em Eventos Comunitários — Vitória/ES';
                $headers = ['Data/Hora', 'Tipo', 'Título do Evento / Reunião', 'Bairro', 'Responsável', 'Público Esperado', 'Público Presente', 'Status'];
                $rowsRaw = Database::fetchAll(
                    "SELECT a.start_datetime, a.event_type, a.title, n.name as bairro, a.responsible_name, a.expected_attendance, a.actual_attendance, a.status
                     FROM agenda_events a
                     LEFT JOIN neighborhoods n ON n.id = a.neighborhood_id
                     ORDER BY a.start_datetime DESC"
                );
                $rows = [];
                foreach ($rowsRaw as $r) {
                    $rows[] = [
                        date('d/m/Y H:i', strtotime($r['start_datetime'])),
                        $r['event_type'],
                        $r['title'],
                        $r['bairro'] ?? 'Vitória',
                        $r['responsible_name'],
                        (int)$r['expected_attendance'],
                        (int)$r['actual_attendance'],
                        $r['status'],
                    ];
                }
                return ['title' => $title, 'headers' => $headers, 'rows' => $rows];
        }
    }

    /**
     * Gera arquivo Excel (.xls SpreadsheetML compatível com Microsoft Excel / LibreOffice) e envia para download.
     */
    public static function downloadExcel(string $reportType): void
    {
        $data = self::getReportData($reportType);
        Logger::audit("Exportação Excel gerada: {$reportType}", 'Relatórios');

        $filename = 'vitoria_inteligencia_' . $reportType . '_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo "\xEF\xBB\xBF"; // BOM UTF-8
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta charset="UTF-8"></head><body>';
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr><th colspan="' . count($data['headers']) . '" style="background:#101B33;color:#FFFFFF;font-size:14pt;">'
            . htmlspecialchars($data['title']) . ' — Gerado em ' . date('d/m/Y H:i') . '</th></tr>';
        echo '<tr>';
        foreach ($data['headers'] as $h) {
            echo '<th style="background:#2563EB;color:#FFFFFF;font-weight:bold;">' . htmlspecialchars((string)$h) . '</th>';
        }
        echo '</tr>';
        foreach ($data['rows'] as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars((string)$cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table></body></html>';
        exit;
    }

    /**
     * Gera um arquivo binário PDF (%PDF-1.4) nativo em PHP (sem dependências externas) e envia para download ou grava em disco.
     */
    public static function generatePdfBinary(string $reportType): string
    {
        $data  = self::getReportData($reportType);
        $lines = [];
        $lines[] = "VITORIA INTELIGENCIA - PLATAFORMA DE INTELIGENCIA TERRITORIAL E GESTAO COMUNITARIA";
        $lines[] = self::toPdfIso($data['title']);
        $lines[] = "Data de Emissao: " . date('d/m/Y H:i:s') . " | Municipio: Vitoria / ES (IBGE 3205309)";
        $lines[] = str_repeat('-', 105);
        $lines[] = self::toPdfIso(implode(' | ', $data['headers']));
        $lines[] = str_repeat('-', 105);

        foreach ($data['rows'] as $row) {
            $rowStr = implode(' | ', array_map(function ($v) {
                return mb_substr((string)$v, 0, 38);
            }, $row));
            $lines[] = self::toPdfIso(mb_substr($rowStr, 0, 112));
        }

        $lines[] = str_repeat('-', 105);
        $lines[] = "Total de registros listados: " . count($data['rows']) . " | Conformidade LGPD (Lei 13.709/2018)";

        // Paginar linhas (45 linhas por página A4 paisagem/retrato)
        $chunks = array_chunk($lines, 44);
        if (empty($chunks)) {
            $chunks = [['Sem registros.']];
        }

        return self::buildRawPdf($chunks);
    }

    public static function downloadPdf(string $reportType): void
    {
        $pdfContent = self::generatePdfBinary($reportType);
        Logger::audit("Exportação PDF gerada: {$reportType}", 'Relatórios');

        $filename = 'vitoria_inteligencia_' . $reportType . '_' . date('Ymd_His') . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfContent));
        echo $pdfContent;
        exit;
    }

    private static function toPdfIso(string $str): string
    {
        if (function_exists('iconv')) {
            $conv = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str);
            if ($conv !== false) {
                return $conv;
            }
        }
        return utf8_decode($str);
    }

    private static function pdfEscape(string $s): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $s);
    }

    private static function buildRawPdf(array $pagesLines): string
    {
        $objects = [];
        // Obj 1: Catalog, Obj 2: Pages, Obj 3: Font Helvetica
        $pageObjIds    = [];
        $contentObjIds = [];
        $nextId        = 4;

        foreach ($pagesLines as $idx => $_) {
            $pageObjIds[$idx]    = $nextId++;
            $contentObjIds[$idx] = $nextId++;
        }

        $kidsStr = implode(' ', array_map(fn($id) => "{$id} 0 R", $pageObjIds));
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [ {$kidsStr} ] /Count " . count($pageObjIds) . " >>";
        $objects[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";

        foreach ($pagesLines as $idx => $pLines) {
            $pId = $pageObjIds[$idx];
            $cId = $contentObjIds[$idx];

            $streamLines = ["BT", "/F1 9 Tf", "36 800 Td", "13 TL"];
            foreach ($pLines as $lineIdx => $lText) {
                $escaped = self::pdfEscape($lText);
                if ($lineIdx === 0) {
                    $streamLines[] = "({$escaped}) Tj";
                } else {
                    $streamLines[] = "T* ({$escaped}) Tj";
                }
            }
            $streamLines[] = "ET";
            $streamData    = implode("\n", $streamLines);
            $len           = strlen($streamData);

            $objects[$pId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents {$cId} 0 R >>";
            $objects[$cId] = "<< /Length {$len} >>\nstream\n{$streamData}\nendstream";
        }

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $totalObjs  = count($objects) + 1;
        $pdf .= "xref\n0 {$totalObjs}\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size {$totalObjs} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}
