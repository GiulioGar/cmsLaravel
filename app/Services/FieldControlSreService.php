<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FieldControlSreService
{
    /*
    |--------------------------------------------------------------------------
    | DIRECTORY + FILES
    |--------------------------------------------------------------------------
    */

    public function resolveResultsDirectory(string $prj, string $sid): ?string
    {
        $serverDirectory = "/var/imr/fields/{$prj}/{$sid}/results";
        if (is_dir($serverDirectory)) {
            return $serverDirectory;
        }

        $localDirectory = base_path("var/imr/fields/{$prj}/{$sid}/results");
        if (is_dir($localDirectory)) {
            return $localDirectory;
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

    /*
    |--------------------------------------------------------------------------
    | PARSING
    |--------------------------------------------------------------------------
    */

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
            'iid' => $this->getSreValue($data, $columns['iid']),
            'uid' => $this->getSreValue($data, $columns['uid']),
            'start_date' => $this->getSreValue($data, $columns['start_date']),
            'end_date' => $this->getSreValue($data, $columns['end_date']),
            'duration' => (int) $this->getSreValue($data, $columns['duration'], 0),
            'status_code' => (int) $this->getSreValue($data, $columns['status'], -1),
            'last_question_code' => (int) $this->getSreValue($data, $columns['last_question_code'], 0),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CORE: BUILD DATASET (NUOVA LOGICA PANEL)
    |--------------------------------------------------------------------------
    */

    public function buildInterviewDataset(array $files, string $prj, string $sid): array
    {
        $parsedList = [];
        $uidsToCheck = [];

        /*
        |--------------------------------------------------------------------------
        | 1° LOOP → PARSING + RACCOLTA UID
        |--------------------------------------------------------------------------
        */
        foreach ($files as $file) {
            $parsed = $this->parseSreFile($file);

            if (empty($parsed)) {
                continue;
            }

            $pan = $this->extractPanFromRaw($parsed['raw']);

            if ($pan === null) {
                $uid = $this->extractUidSafe($parsed);
                if ($uid !== null) {
                    $uidsToCheck[$uid] = true;
                }
            }

            $parsed['pan'] = $pan;
            $parsedList[] = $parsed;
        }

        /*
        |--------------------------------------------------------------------------
        | CACHE UID INTERACTIVE
        |--------------------------------------------------------------------------
        */
        $cacheKey = "fieldcontrol_valid_interactive_uids_{$prj}_{$sid}";

        $validInteractiveUids = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($uidsToCheck) {

            if (empty($uidsToCheck)) {
                return [];
            }

            $valid = [];
            $uids = array_keys($uidsToCheck);

            foreach (array_chunk($uids, 1000) as $chunk) {
                $found = DB::table('t_user_info')
                    ->whereIn('user_id', $chunk)
                    ->pluck('user_id')
                    ->toArray();

                foreach ($found as $uid) {
                    $valid[(string)$uid] = true;
                }
            }

            return $valid;
        });

        /*
        |--------------------------------------------------------------------------
        | PANEL MAP (DB)
        |--------------------------------------------------------------------------
        */
        $panelNames = DB::table('t_fornitoripanel')
            ->pluck('name', 'panel_code')
            ->map(fn($v) => trim((string)$v))
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | 2° LOOP → CLASSIFICAZIONE
        |--------------------------------------------------------------------------
        */
        $interviews = [];

        foreach ($parsedList as $parsed) {

            $panel = null;

            // 1. pan presente
            if ($parsed['pan'] !== null) {
                $panel = $panelNames[$parsed['pan']] ?? 'Altro Panel';
            } else {
                // 2. fallback UID
                $uid = $this->extractUidSafe($parsed);

                if ($uid !== null && isset($validInteractiveUids[$uid])) {
                    $panel = 'Interactive';
                } else {
                    $panel = 'Da lista';
                }
            }

            $parsed['panel'] = $panel;

            $interviews[] = $parsed;
        }

        return $interviews;
    }

    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

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

            $panel = $interview['panel'];

            if (!isset($panelCounts[$panel])) {
                $panelCounts[$panel] = [
                    'complete' => 0,
                    'non_target' => 0,
                    'over_quota' => 0,
                    'sospese' => 0,
                    'bloccate' => 0,
                    'contatti' => 0,
                    'redemption' => 0,
                ];
            }

            $panelCounts[$panel]['contatti']++;

            switch ((int)$interview['status_code']) {
                case 3:
                    $counts['complete']++;
                    $panelCounts[$panel]['complete']++;
                    break;
                case 4:
                    $counts['non_target']++;
                    $panelCounts[$panel]['non_target']++;
                    break;
                case 5:
                    $counts['over_quota']++;
                    $panelCounts[$panel]['over_quota']++;
                    break;
                case 0:
                    $counts['sospese']++;
                    $panelCounts[$panel]['sospese']++;
                    break;
                case 7:
                    $counts['bloccate']++;
                    $panelCounts[$panel]['bloccate']++;
                    break;
            }
        }

        return compact('counts', 'panelCounts');
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

        $panel = $interview['panel'];

        $dayKey = $carbon->format('Y-m-d');
        $displayDate = $carbon->locale('it')->isoFormat('dddd D MMMM YYYY');

        if (!isset($dataSummaryByPanel[$panel])) {
            $dataSummaryByPanel[$panel] = [];
        }

        if (!isset($dataSummaryByPanel[$panel][$dayKey])) {
            $dataSummaryByPanel[$panel][$dayKey] = [
                'display_date' => $displayDate,
                'contatti' => 0,
                'complete' => 0,
                'non_target' => 0,
                'quotafull' => 0,
                'bloccate' => 0,
                'total_duration' => 0,
            ];
        }

        $dataSummaryByPanel[$panel][$dayKey]['contatti']++;

        switch ((int) $interview['status_code']) {
            case 3:
                $dataSummaryByPanel[$panel][$dayKey]['complete']++;
                $dataSummaryByPanel[$panel][$dayKey]['total_duration'] += (int) $interview['duration'];
                break;
            case 4:
                $dataSummaryByPanel[$panel][$dayKey]['non_target']++;
                break;
            case 5:
                $dataSummaryByPanel[$panel][$dayKey]['quotafull']++;
                break;
            case 7:
                $dataSummaryByPanel[$panel][$dayKey]['bloccate']++;
                break;
        }
    }

    foreach ($dataSummaryByPanel as &$summary) {
        krsort($summary);
    }

    return $dataSummaryByPanel;
}


    /*
    |--------------------------------------------------------------------------
    | FILTRATE (USA panel)
    |--------------------------------------------------------------------------
    */

    public function buildFiltrateCountsFromInterviews(array $interviews, array $questionMap): array
    {
        $panelFiltrateCounts = [];

        foreach ($interviews as $interview) {

            if ((int)$interview['status_code'] !== 4) {
                continue;
            }

            $panel = $interview['panel'];
            $questionId = (int)$interview['last_question_code'];

            if (!isset($panelFiltrateCounts[$panel])) {
                $panelFiltrateCounts[$panel] = [];
            }

            $question = $questionMap[$questionId] ?? [
                'code' => 'N/A',
                'text' => 'Domanda non trovata',
            ];

            $label = $question['code'] . ' - ' . $question['text'];

            $panelFiltrateCounts[$panel][$label] =
                ($panelFiltrateCounts[$panel][$label] ?? 0) + 1;
        }

        foreach ($panelFiltrateCounts as &$rows) {
            arsort($rows);
        }

        return $panelFiltrateCounts;
    }

    /*
    |--------------------------------------------------------------------------
    | UTILS
    |--------------------------------------------------------------------------
    */

    private function extractPanFromRaw(array $data): ?int
    {
        foreach ($data as $element) {
            if (strpos($element, 'pan=') !== false) {
                return (int) str_replace('pan=', '', $element);
            }
        }
        return null;
    }

    private function extractUidSafe(array $parsed): ?string
    {
        $uid = $this->extractTaggedFieldValue($parsed['raw'], 'sysUID');

        if ($uid === 'N/A') {
            $uid = $parsed['uid'] ?? null;
        }

        if ($uid === null) {
            return null;
        }

        return trim((string)$uid);
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

    /*
    |--------------------------------------------------------------------------
    | METODI ORIGINALI (NON TOCCATI)
    |--------------------------------------------------------------------------
    */

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
        return $seconds < 60 ? $seconds . ' sec.' : round($seconds / 60, 1) . ' min.';
    }

    public function parseDateToCarbon($rawDate): ?Carbon
    {
        try {
            return Carbon::createFromFormat('d/m/Y H:i:s', str_replace([' CET', ' CEST'], '', trim($rawDate)), 'Europe/Rome');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function readFirstLineFromSre(string $file): ?string
    {
        $handle = fopen($file, 'r');
        if (!$handle) return null;

        $line = fgets($handle);
        fclose($handle);

        return $line ? trim($line) : null;
    }

    private function parseSreLine(?string $line): array
    {
        return $line ? explode(';', $line) : [];
    }

    private function getSreColumnMap(array $data): array
    {
        $hasVersion = isset($data[0]) && $data[0] === '2.0';

        return [
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
        return $index !== null ? ($data[$index] ?? $default) : $default;
    }

    private function extractSreFileNumber(string $file): int
    {
        return (int) substr(pathinfo($file, PATHINFO_FILENAME), 3);
    }
}
