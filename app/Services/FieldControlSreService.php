<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FieldControlSreService
{
    public function resolveResultsDirectory(string $prj, string $sid): ?string
    {
        $localDirectory = base_path("var/imr/fields/{$prj}/{$sid}/results");
        if (is_dir($localDirectory)) {
            return $localDirectory;
        }

        $serverDirectory = "/var/imr/fields/{$prj}/{$sid}/results";
        if (is_dir($serverDirectory)) {
            return $serverDirectory;
        }

        return null;
    }

    public function getSreFiles(?string $directory): array
    {
        if (!$directory || !is_dir($directory)) {
            return [];
        }

        $files = glob($directory . '/*.sre');

        return is_array($files) ? $files : [];
    }

    public function parseSreFile(string $file): array
    {
        $line = $this->readFirstLineFromSre($file);
        $data = $this->parseSreLine($line);

        if (empty($data)) {
            return [];
        }

        $columns = $this->getSreColumnMap($data);

        return [
            'file' => $file,
            'file_number' => $this->extractSreFileNumber($file),
            'raw' => $data,
            'columns' => $columns,
            'iid' => $this->getSreValue($data, $columns['iid']),
            'uid' => $this->getSreValue($data, $columns['uid']),
            'start_date' => $this->getSreValue($data, $columns['start_date']),
            'end_date' => $this->getSreValue($data, $columns['end_date']),
            'duration' => (int) $this->getSreValue($data, $columns['duration'], 0),
            'status_code' => (int) $this->getSreValue($data, $columns['status'], -1),
            'last_question_code' => (int) $this->getSreValue($data, $columns['last_question_code'], 0),
        ];
    }

    public function buildInterviewDataset(array $files, array $panelNames, $panelValueFromDB): array
    {
        $interviews = [];

        foreach ($files as $file) {
            $parsed = $this->parseSreFile($file);

            if (empty($parsed)) {
                continue;
            }

            $parsed['panel_used'] = $this->resolvePanelFromRawData($parsed['raw'], $panelNames, $panelValueFromDB);
            $parsed['panel_for_reports'] = $this->resolvePanelFromRawData($parsed['raw'], $panelNames, 1) ?? 'Interactive';

            $interviews[] = $parsed;
        }

        return $interviews;
    }

    public function summarizeInterviews(array $interviews, int $totalFiles): array
    {
        $counts = [
            'complete' => 0,
            'non_target' => 0,
            'over_quota' => 0,
            'sospese' => 0,
            'bloccate' => 0,
            'contatti' => $totalFiles,
        ];

        $panelCounts = [];

        foreach ($interviews as $interview) {
            $panelUsed = $interview['panel_used'];

            if (!$panelUsed) {
                continue;
            }

            if (!isset($panelCounts[$panelUsed])) {
                $panelCounts[$panelUsed] = [
                    'complete' => 0,
                    'non_target' => 0,
                    'over_quota' => 0,
                    'sospese' => 0,
                    'bloccate' => 0,
                    'contatti' => 0,
                    'redemption' => 0.0,
                ];
            }

            $panelCounts[$panelUsed]['contatti']++;

            switch ((int) $interview['status_code']) {
                case 3:
                    $counts['complete']++;
                    $panelCounts[$panelUsed]['complete']++;
                    break;
                case 4:
                    $counts['non_target']++;
                    $panelCounts[$panelUsed]['non_target']++;
                    break;
                case 5:
                    $counts['over_quota']++;
                    $panelCounts[$panelUsed]['over_quota']++;
                    break;
                case 0:
                    $counts['sospese']++;
                    $panelCounts[$panelUsed]['sospese']++;
                    break;
                case 7:
                    $counts['bloccate']++;
                    $panelCounts[$panelUsed]['bloccate']++;
                    break;
            }
        }

        return [
            'counts' => $counts,
            'panelCounts' => $panelCounts,
        ];
    }

    public function buildFiltrateCountsFromInterviews(array $interviews, array $questionMap): array
    {
        $panelFiltrateCounts = [];

        foreach ($interviews as $interview) {
            if ((int) $interview['status_code'] !== 4) {
                continue;
            }

            $panelUsed = $interview['panel_for_reports'];
            $questionId = (int) $interview['last_question_code'];

            if (!isset($panelFiltrateCounts[$panelUsed])) {
                $panelFiltrateCounts[$panelUsed] = [];
            }

            $questionDetails = $questionMap[$questionId] ?? [
                'code' => 'N/A',
                'text' => 'Domanda non trovata',
            ];

            $questionLabel = $questionDetails['code'] . ' - ' . $questionDetails['text'];

            if (!isset($panelFiltrateCounts[$panelUsed][$questionLabel])) {
                $panelFiltrateCounts[$panelUsed][$questionLabel] = 1;
            } else {
                $panelFiltrateCounts[$panelUsed][$questionLabel]++;
            }
        }

        foreach ($panelFiltrateCounts as &$filtrateCounts) {
            arsort($filtrateCounts);
        }
        unset($filtrateCounts);

        return $panelFiltrateCounts;
    }

    public function buildLogDataFromInterviews(array $interviews, array $questionMap): array
    {
        usort($interviews, function ($a, $b) {
            return $b['file_number'] <=> $a['file_number'];
        });

        $statusMap = $this->getInterviewStatusMap();
        $logData = [];

        foreach ($interviews as $interview) {
            $questionId = (int) $interview['last_question_code'];

            $questionDetails = $questionMap[$questionId] ?? [
                'code' => 'N/A',
                'text' => 'Domanda non trovata',
            ];

            $questionCode = e($questionDetails['code']);
            $questionText = e($questionDetails['text']);

            $logData[] = [
                'iid' => $interview['iid'],
                'uid' => $interview['uid'],
                'ultimo_update' => $interview['end_date'],
                'ultima_azione' => "<span data-bs-toggle='tooltip' title='{$questionText}'>{$questionCode}</span>",
                'stato' => $statusMap[$interview['status_code']] ?? 'Sconosciuto',
                'durata' => $this->formatDuration((int) $interview['duration']),
            ];
        }

        return $logData;
    }

    public function buildDataSummaryByDateFromInterviews(array $interviews): array
    {
        $dataSummaryByPanel = [];

        foreach ($interviews as $interview) {
            $interviewDate = $interview['start_date'];

            if ($interviewDate === 'N/A' || empty($interviewDate)) {
                continue;
            }

            $carbon = $this->parseDateToCarbon($interviewDate);

            if (!$carbon) {
                continue;
            }

            $panelUsed = $interview['panel_for_reports'];
            $dayKey = $carbon->format('Y-m-d');
            $displayDate = $carbon->locale('it')->isoFormat('dddd D MMMM YYYY');

            if (!isset($dataSummaryByPanel[$panelUsed])) {
                $dataSummaryByPanel[$panelUsed] = [];
            }

            if (!isset($dataSummaryByPanel[$panelUsed][$dayKey])) {
                $dataSummaryByPanel[$panelUsed][$dayKey] = [
                    'display_date' => $displayDate,
                    'contatti' => 0,
                    'complete' => 0,
                    'non_target' => 0,
                    'quotafull' => 0,
                    'bloccate' => 0,
                    'total_duration' => 0,
                ];
            }

            $dataSummaryByPanel[$panelUsed][$dayKey]['contatti']++;

            switch ((int) $interview['status_code']) {
                case 3:
                    $dataSummaryByPanel[$panelUsed][$dayKey]['complete']++;
                    $dataSummaryByPanel[$panelUsed][$dayKey]['total_duration'] += (int) $interview['duration'];
                    break;
                case 4:
                    $dataSummaryByPanel[$panelUsed][$dayKey]['non_target']++;
                    break;
                case 5:
                    $dataSummaryByPanel[$panelUsed][$dayKey]['quotafull']++;
                    break;
                case 7:
                    $dataSummaryByPanel[$panelUsed][$dayKey]['bloccate']++;
                    break;
            }
        }

        foreach ($dataSummaryByPanel as &$summary) {
            krsort($summary);
        }
        unset($summary);

        return $dataSummaryByPanel;
    }

public function resolvePanelFromRawData(array $data, array $panelNames, $panelValueFromDB = null): ?string
{
    foreach ($data as $element) {
        if (strpos($element, 'pan=') !== false) {
            $panelValue = (int) str_replace('pan=', '', $element);
            return $panelNames[$panelValue] ?? null;
        }
    }

    if ((int) $panelValueFromDB === 1) {
        return 'Interactive';
    }

    return null;
}

    public function extractTaggedFieldValue(array $data, string $fieldKey): string
    {
        for ($i = count($data) - 1; $i >= 0; $i--) {
            if (strpos($data[$i], $fieldKey . '=') !== false) {
                return str_replace($fieldKey . '=', '', $data[$i]);
            }
        }

        return 'N/A';
    }

    public function getInterviewStatusMap(): array
    {
        return [
            0 => 'In Corso',
            3 => 'Completa',
            4 => 'Non in target',
            5 => 'Quotafull',
            7 => 'Bloccata',
        ];
    }

    public function getDownloadStatusMap(): array
    {
        return [
            0 => 'suspended',
            3 => 'complete',
            4 => 'screenout',
            5 => 'quotafull',
            7 => 'badQuality',
        ];
    }

    public function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' sec.';
        }

        return round($seconds / 60, 1) . ' min.';
    }

    public function parseDateToCarbon($rawDate): ?Carbon
    {
        $cleanDate = str_replace([' CET', ' CEST'], '', trim($rawDate));

        try {
            return Carbon::createFromFormat('d/m/Y H:i:s', $cleanDate, 'Europe/Rome');
        } catch (\Exception $e) {
            Log::warning("parseDateToCarbon() - Impossibile parsare la data: [{$rawDate}], errore: {$e->getMessage()}");
            return null;
        }
    }

    private function readFirstLineFromSre(string $file): ?string
    {
        $handle = fopen($file, 'r');

        if (!$handle) {
            return null;
        }

        $line = fgets($handle);
        fclose($handle);

        return ($line !== false) ? trim($line) : null;
    }

    private function parseSreLine(?string $line): array
    {
        if ($line === null || $line === '') {
            return [];
        }

        return explode(';', $line);
    }

    private function getSreColumnMap(array $data): array
    {
        $hasVersion = isset($data[0]) && $data[0] === '2.0';

        return [
            'version' => $hasVersion ? 0 : null,
            'prj' => $hasVersion ? 1 : 0,
            'sid' => $hasVersion ? 2 : 1,
            'iid' => $hasVersion ? 3 : 2,
            'uid' => $hasVersion ? 4 : 3,
            'start_date' => $hasVersion ? 5 : 4,
            'end_date' => $hasVersion ? 6 : 5,
            'duration' => $hasVersion ? 7 : 6,
            'status' => $hasVersion ? 8 : 7,
            'last_question_code' => $hasVersion ? 9 : 8,
        ];
    }

    private function getSreValue(array $data, ?int $index, $default = 'N/A')
    {
        if ($index === null) {
            return $default;
        }

        return $data[$index] ?? $default;
    }

    private function extractSreFileNumber(string $file): int
    {
        return (int) substr(pathinfo($file, PATHINFO_FILENAME), 3);
    }
}
