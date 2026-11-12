<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Lettura euristica dell'SDL della ricerca per dare un nome a cio' che
 * Primis restituisce solo come segnaposto:
 * - vettori segnaposto ("#sha", "#shb"...) riempiti a runtime con
 *   P.set(i, SORGENTE.get(i))  -> righe di Q13 & co.
 * - domande in loop con codice "PREFISSO"+var e etichetta letta da
 *   label = VETTORE.get(var)    -> Q12p0..p6, q19br.N
 * - variabili esportate lette come VETTORE.get(var) (con offset lineare)
 */
class PanoramicaSdlService
{
    /** @var FieldControlSreService */
    private $sre;

    public function __construct(FieldControlSreService $sre)
    {
        $this->sre = $sre;
    }

    /**
     * [codice domanda => ['context' => string|null, 'loop' => prefisso|null, 'rows' => array|null]]
     * Il codice e' quello restituito da Primis (es. "Q12p3", "q19br.4", "Q13").
     */
    public function getQuestionContext(string $prj, string $sid, array $catalogCodes): array
    {
        $parsed = $this->parse($prj, $sid);
        if ($parsed === null) {
            return [];
        }

        $result = [];
        foreach ($parsed['questions'] as $def) {
            if ($def['loop_var'] === null) {
                // Domanda statica: interessa solo se le righe sono segnaposto risolti
                if ($def['rows'] !== null && isset($catalogCodes[$def['code']])) {
                    $result[$def['code']] = ['context' => null, 'loop' => null, 'rows' => $def['rows']];
                }
                continue;
            }

            foreach ($catalogCodes as $code => $unused) {
                $code = (string) $code;
                if (strpos($code, $def['code']) !== 0) {
                    continue;
                }
                $suffix = substr($code, strlen($def['code']));
                if ($suffix === '' || !ctype_digit($suffix)) {
                    continue;
                }

                $index = (int) $suffix;
                $context = $def['labels'] !== null ? ($def['labels'][$index] ?? null) : null;
                if ($context === null || $context === '') {
                    $context = 'ciclo ' . $index;
                }

                $rows = $def['rows'];
                if ($def['rows_from_label']) {
                    $rows = [$context];
                }

                $result[$code] = ['context' => $context, 'loop' => $def['code'], 'rows' => $rows];
            }
        }

        return $result;
    }

    /**
     * [variabile => ['items' => [...], 'offset' => int]] per le variabili
     * usate come indice di un vettore di stringhe (es. listaPersonaggi.get(cella)).
     */
    public function getVariableVectors(string $prj, string $sid, array $keys): array
    {
        $parsed = $this->parse($prj, $sid);
        if ($parsed === null) {
            return [];
        }
        $source = $parsed['source'];
        $vectors = $parsed['vectors'];

        $found = [];
        foreach ($keys as $key) {
            if (preg_match('/\b(\w+)\.get\(\s*' . preg_quote($key, '/') . '\s*\)/', $source, $m) && $this->isLabelVector($vectors[$m[1]] ?? null)) {
                $found[$key] = ['items' => $vectors[$m[1]], 'offset' => 0];
            }
        }

        // var = altra +/- k, con "altra" gia' associata
        foreach ($keys as $key) {
            if (isset($found[$key])) {
                continue;
            }
            if (preg_match('/\b' . preg_quote($key, '/') . '\s*=\s*(\w+)\s*([+-])\s*(\d+)\s*;/', $source, $m) && isset($found[$m[1]])) {
                $shift = (int) $m[3] * ($m[2] === '+' ? 1 : -1);
                $found[$key] = ['items' => $found[$m[1]]['items'], 'offset' => $found[$m[1]]['offset'] + $shift];
            }
        }

        return $found;
    }

    /*
    |--------------------------------------------------------------------------
    | PARSING
    |--------------------------------------------------------------------------
    */

    private function parse(string $prj, string $sid): ?array
    {
        $file = $this->sdlPath($prj, $sid);
        if ($file === null) {
            return null;
        }

        $cacheKey = "panoramica_sdl_{$prj}_{$sid}_" . @filemtime($file);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($file) {
            $source = (string) @file_get_contents($file);
            // Commenti // a fine riga: evitano falsi match (es. righe disattivate)
            $source = preg_replace('#(^|[^:"\'])//[^\n]*#', '$1', $source);

            $rawVectors = $this->parseVectors($source);
            $vectors = $this->resolvePlaceholders($source, $rawVectors);

            return [
                'source'    => $source,
                'vectors'   => $vectors,
                'questions' => $this->parseQuestions($source, $vectors, $rawVectors),
            ];
        });
    }

    private function sdlPath(string $prj, string $sid): ?string
    {
        $resultsDir = $this->sre->resolveResultsDirectory($prj, $sid);
        if ($resultsDir === null) {
            return null;
        }
        $file = dirname($resultsDir) . "/{$sid}.sdl";
        return is_readable($file) ? $file : null;
    }

    private function parseVectors(string $source): array
    {
        $vectors = [];
        if (preg_match_all('/vector\s+(\w+)\s*=\s*new\s+vector\s*\{(.*?)\}\s*;/s', $source, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                preg_match_all('/"((?:[^"\\\\]|\\\\.)*)"/', $m[2], $items);
                $vectors[$m[1]] = array_map([$this, 'cleanText'], $items[1]);
            }
        }
        return $vectors;
    }

    /**
     * Vettori segnaposto (#sh...) riempiti con P.set(i, X) dove X = SRC.get(i):
     * li sostituiamo con SRC. Se piu' segnaposto hanno gli stessi elementi
     * (pers / persName) usiamo per tutti la sorgente col testo piu' pulito.
     */
    private function resolvePlaceholders(string $source, array $raw): array
    {
        $vectors = $raw;
        $sources = [];

        if (preg_match_all('/(\w+)\s*=\s*(\w+)\.get\(\s*(\w+)\s*\)\s*;\s*(\w+)\.set\(\s*\3\s*,\s*\1\s*\)/', $source, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $placeholder = $m[4];
                $src = $m[2];
                if (isset($raw[$placeholder], $raw[$src]) && $this->isPlaceholderVector($raw[$placeholder])) {
                    $sources[$placeholder][] = $src;
                }
            }
        }

        // Sorgente migliore per ogni gruppo di segnaposto con gli stessi elementi
        $bestByShape = [];
        foreach ($sources as $placeholder => $srcList) {
            $shape = implode('|', $raw[$placeholder]);
            foreach ($srcList as $src) {
                $score = $this->averageLength($raw[$src]);
                if ($score > 0 && (!isset($bestByShape[$shape]) || $score < $bestByShape[$shape]['score'])) {
                    $bestByShape[$shape] = ['src' => $src, 'score' => $score];
                }
            }
        }

        foreach ($raw as $name => $items) {
            if (!$this->isPlaceholderVector($items)) {
                continue;
            }
            $shape = implode('|', $items);
            if (isset($bestByShape[$shape])) {
                $vectors[$name] = $raw[$bestByShape[$shape]['src']];
            }
        }

        return $vectors;
    }

    private function parseQuestions(string $source, array $vectors, array $rawVectors): array
    {
        $questions = [];
        if (!preg_match_all('/new\s+question\s*\(\s*"(\w+)"/', $source, $starts, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $count = count($starts[0]);
        for ($i = 0; $i < $count; $i++) {
            $begin = $starts[0][$i][1];
            $end = $i + 1 < $count ? $starts[0][$i + 1][1] : strlen($source);
            $block = substr($source, $begin, $end - $begin);

            if (!preg_match('/setProperty\(\s*"code"\s*,\s*"([^"]*)"\s*(?:\+\s*(\w+))?\s*\)/', $block, $codeMatch)) {
                continue;
            }
            $prefix = $codeMatch[1];
            $loopVar = $codeMatch[2] ?? null;
            if ($loopVar === '') {
                $loopVar = null;
            }

            // Righe: vettore segnaposto risolto (solo se davvero sostituito)
            $rows = null;
            if (preg_match('/setProperty\(\s*"rows"\s*,\s*(\w+)\s*\)/', $block, $rowsMatch)) {
                $rowsVector = $rowsMatch[1];
                if (isset($rawVectors[$rowsVector]) && $this->isPlaceholderVector($rawVectors[$rowsVector])
                    && ($vectors[$rowsVector] ?? null) !== $rawVectors[$rowsVector]) {
                    $rows = $vectors[$rowsVector];
                }
            }

            $labels = null;
            $rowsFromLabel = false;
            if ($loopVar !== null) {
                $before = substr($source, max(0, $begin - 6000), 6000);
                if (preg_match_all('/(\w+)\s*=\s*(?:\(\w+\)\s*)?(\w+)\.get\(\s*' . preg_quote($loopVar, '/') . '\s*\)\s*;/', $before, $labelMatches, PREG_SET_ORDER)) {
                    // Il piu' vicino alla domanda che punti a un vettore di testi
                    for ($k = count($labelMatches) - 1; $k >= 0; $k--) {
                        $vectorName = $labelMatches[$k][2];
                        if ($this->isLabelVector($vectors[$vectorName] ?? null)) {
                            $labels = $vectors[$vectorName];
                            $labelVar = $labelMatches[$k][1];
                            // Riga singola riempita con l'etichetta: rows.set(0, label)
                            if (isset($rowsMatch[1]) && preg_match('/\b' . preg_quote($rowsMatch[1], '/') . '\.set\(\s*0\s*,\s*' . preg_quote($labelVar, '/') . '\s*\)/', $block)) {
                                $rowsFromLabel = true;
                            }
                            break;
                        }
                    }
                }
            }

            $questions[] = [
                'code'            => $prefix,
                'loop_var'        => $loopVar,
                'labels'          => $labels,
                'rows'            => $rows,
                'rows_from_label' => $rowsFromLabel,
            ];
        }

        return $questions;
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    private function isPlaceholderVector(array $items): bool
    {
        if (empty($items)) {
            return false;
        }
        foreach ($items as $item) {
            if (strpos($item, '#sh') !== 0) {
                return false;
            }
        }
        return true;
    }

    /** Vettore utilizzabile come etichette: testi, non solo numeri o segnaposto */
    private function isLabelVector(?array $items): bool
    {
        if (empty($items) || $this->isPlaceholderVector($items)) {
            return false;
        }
        foreach ($items as $item) {
            if ($item !== '' && !is_numeric($item)) {
                return true;
            }
        }
        return false;
    }

    private function averageLength(array $items): float
    {
        $items = array_filter($items, function ($item) {
            return $item !== '';
        });
        if (empty($items)) {
            return 0;
        }
        return array_sum(array_map('mb_strlen', $items)) / count($items);
    }

    private function cleanText(string $text): string
    {
        $text = stripcslashes($text);
        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $text);
        $text = html_entity_decode(strip_tags(str_replace('<', ' <', $text)), ENT_QUOTES, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}
