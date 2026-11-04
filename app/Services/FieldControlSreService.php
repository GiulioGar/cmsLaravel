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

    /**
     * Versione leggera di buildInterviewDataset()+summarizeInterviews(), senza
     * Primis (question map) ne' build di log/quote/filtrate: serve solo per
     * popolare le modali Download/Reset Bloccate su pagine diverse da
     * fieldControl (lista panel + conteggio bloccate).
     */
    public function getLightPanelSummary(string $prj, string $sid): array
    {
        $directory = $this->resolveResultsDirectory($prj, $sid);
        $files = $this->getSreFiles($directory);
        $interviews = $this->buildInterviewDataset($files, $prj, $sid);

        return $this->summarizeInterviews($interviews, count($files));
    }

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

    public function resolveResourcesDirectory(string $prj, string $sid): ?string
    {
        $serverDirectory = "/var/imr/fields/{$prj}/{$sid}/resources";
        if (is_dir($serverDirectory)) {
            return $serverDirectory;
        }

        $localDirectory = base_path("var/imr/fields/{$prj}/{$sid}/resources");
        if (is_dir($localDirectory)) {
            return $localDirectory;
        }

        return null;
    }

    public function getConfigRedirectVariables(string $prj, string $sid, $panelCode): array
    {
        if ($panelCode === null || $panelCode === '') {
            return [];
        }

        $resourcesDirectory = $this->resolveResourcesDirectory($prj, $sid);
        if (!$resourcesDirectory) {
            return [];
        }

        $configPath = $resourcesDirectory . DIRECTORY_SEPARATOR . 'config.json';
        if (!is_file($configPath) || !is_readable($configPath)) {
            return [];
        }

        $content = @file_get_contents($configPath);
        if ($content === false || trim($content) === '') {
            return [];
        }

        $config = json_decode($content, true);
        if (!is_array($config)) {
            return [];
        }

        $redirections = $config['interview']['production']['redirections'] ?? [];
        if (!is_array($redirections)) {
            return [];
        }

        $variables = [];

        foreach ($redirections as $redirection) {
            if (!is_array($redirection)) {
                continue;
            }

            if (($redirection['parameter_name'] ?? null) !== 'pan') {
                continue;
            }

            if ((string) ($redirection['parameter_value'] ?? '') !== (string) $panelCode) {
                continue;
            }

            $redirectTo = (string) ($redirection['redirect_to'] ?? '');
            if ($redirectTo === '') {
                continue;
            }

            preg_match_all('/\$\(([^)]+)\)/', $redirectTo, $matches);

            foreach ($matches[1] as $variableName) {
                $variableName = trim((string) $variableName);

                if ($variableName === '') {
                    continue;
                }

                $variables[$variableName] = $variableName;
            }
        }

        return array_values($variables);
    }

    public function getRedirectionsList(string $prj, string $sid): array
    {
        $resourcesDirectory = $this->resolveResourcesDirectory($prj, $sid);
        if (!$resourcesDirectory) {
            return [];
        }

        $configPath = $resourcesDirectory . DIRECTORY_SEPARATOR . 'config.json';
        if (!is_file($configPath) || !is_readable($configPath)) {
            return [];
        }

        $content = @file_get_contents($configPath);
        if ($content === false || trim($content) === '') {
            return [];
        }

        $config = json_decode($content, true);
        if (!is_array($config)) {
            return [];
        }

        $redirections = $config['interview']['production']['redirections'] ?? [];
        if (!is_array($redirections)) {
            return [];
        }

        $statusMap = $this->getInterviewStatusMap();
        $list = [];

        foreach ($redirections as $rawIndex => $redirection) {
            if (!is_array($redirection)) {
                continue;
            }

            $parameterName = (string) ($redirection['parameter_name'] ?? '');
            if ($parameterName === 'test') {
                continue;
            }

            $redirectTo = (string) ($redirection['redirect_to'] ?? '');
            if ($redirectTo === '') {
                continue;
            }

            $exitCode = (int) ($redirection['exit_code'] ?? -1);

            $list[] = [
                'index' => (int) $rawIndex,
                'parameter_name' => $parameterName,
                'parameter_value' => (string) ($redirection['parameter_value'] ?? ''),
                'exit_code' => $exitCode,
                'exit_label' => $statusMap[$exitCode] ?? 'Sconosciuto',
                'redirect_to' => $redirectTo,
            ];
        }

        return $list;
    }

    /**
     * Elimina TUTTE le redirection di un panel (stesso parameter_name +
     * parameter_value), cosi' come vengono create in blocco: non ha senso
     * eliminare solo l'esito "Completa" di un panel e lasciare orfani gli
     * altri due (Non in target/Quotafull).
     */
    public function deleteRedirectionsGroup(string $prj, string $sid, string $parameterName, string $parameterValue): array
    {
        $resourcesDirectory = $this->resolveResourcesDirectory($prj, $sid);
        if (!$resourcesDirectory) {
            return ['success' => false, 'message' => 'Cartella resources non trovata per questa ricerca.'];
        }

        $configPath = $resourcesDirectory . DIRECTORY_SEPARATOR . 'config.json';
        if (!is_file($configPath) || !is_readable($configPath)) {
            return ['success' => false, 'message' => 'File config.json non trovato o non leggibile.'];
        }

        $content = @file_get_contents($configPath);
        if ($content === false || trim($content) === '') {
            return ['success' => false, 'message' => 'File config.json vuoto o illeggibile.'];
        }

        $config = json_decode($content, true);
        if (!is_array($config) || !isset($config['interview']['production']['redirections']) || !is_array($config['interview']['production']['redirections'])) {
            return ['success' => false, 'message' => 'Struttura redirections non trovata nel config.json.'];
        }

        $original = $config['interview']['production']['redirections'];

        $filtered = array_values(array_filter($original, function ($entry) use ($parameterName, $parameterValue) {
            if (!is_array($entry)) {
                return true;
            }

            $matches = (string) ($entry['parameter_name'] ?? '') === $parameterName
                && (string) ($entry['parameter_value'] ?? '') === $parameterValue;

            return !$matches;
        }));

        $removedCount = count($original) - count($filtered);

        if ($removedCount === 0) {
            return ['success' => false, 'message' => 'Nessuna redirection trovata per questo panel.'];
        }

        if (!is_writable($configPath)) {
            return ['success' => false, 'message' => 'File config.json non scrivibile.'];
        }

        $config['interview']['production']['redirections'] = $filtered;

        $encoded = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return ['success' => false, 'message' => 'Errore durante la codifica del config.json.'];
        }

        if (@file_put_contents($configPath, $encoded) === false) {
            return ['success' => false, 'message' => 'Errore durante la scrittura del config.json.'];
        }

        return ['success' => true, 'removed' => $removedCount];
    }

    /**
     * Aggiorna in blocco il campo redirect_to di una o più redirection esistenti
     * nel config.json, individuate tramite l'indice originale nell'array
     * interview.production.redirections (quello restituito da getRedirectionsList()).
     * Legge e riscrive il file una sola volta per tutte le modifiche passate.
     *
     * @param array $updates lista di ['index' => int, 'redirect_to' => string]
     */
    public function updateRedirections(string $prj, string $sid, array $updates): array
    {
        if (empty($updates)) {
            return ['success' => false, 'message' => 'Nessuna modifica da salvare.'];
        }

        $resourcesDirectory = $this->resolveResourcesDirectory($prj, $sid);
        if (!$resourcesDirectory) {
            return ['success' => false, 'message' => 'Cartella resources non trovata per questa ricerca.'];
        }

        $configPath = $resourcesDirectory . DIRECTORY_SEPARATOR . 'config.json';
        if (!is_file($configPath) || !is_readable($configPath)) {
            return ['success' => false, 'message' => 'File config.json non trovato o non leggibile.'];
        }

        $content = @file_get_contents($configPath);
        if ($content === false || trim($content) === '') {
            return ['success' => false, 'message' => 'File config.json vuoto o illeggibile.'];
        }

        $config = json_decode($content, true);
        if (!is_array($config) || !isset($config['interview']['production']['redirections']) || !is_array($config['interview']['production']['redirections'])) {
            return ['success' => false, 'message' => 'Struttura redirections non trovata nel config.json.'];
        }

        $updatedCount = 0;

        foreach ($updates as $update) {
            if (!is_array($update)) {
                continue;
            }

            $index = (int) ($update['index'] ?? -1);
            $redirectTo = trim((string) ($update['redirect_to'] ?? ''));

            if ($redirectTo === '') {
                continue;
            }

            if (!isset($config['interview']['production']['redirections'][$index]) || !is_array($config['interview']['production']['redirections'][$index])) {
                continue;
            }

            $config['interview']['production']['redirections'][$index]['redirect_to'] = $redirectTo;
            $updatedCount++;
        }

        if ($updatedCount === 0) {
            return ['success' => false, 'message' => 'Nessuna redirection valida da aggiornare.'];
        }

        if (!is_writable($configPath)) {
            return ['success' => false, 'message' => 'File config.json non scrivibile.'];
        }

        $encoded = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return ['success' => false, 'message' => 'Errore durante la codifica del config.json.'];
        }

        if (@file_put_contents($configPath, $encoded) === false) {
            return ['success' => false, 'message' => 'Errore durante la scrittura del config.json.'];
        }

        return ['success' => true, 'updated' => $updatedCount];
    }

    /**
     * Aggiunge nuove redirection in coda all'array interview.production.redirections
     * del config.json. Ogni elemento di $newEntries deve avere almeno
     * parameter_value, exit_code, redirect_to; parameter_name di default è "pan".
     */
    public function addRedirections(string $prj, string $sid, array $newEntries): array
    {
        if (empty($newEntries)) {
            return ['success' => false, 'message' => 'Nessuna redirection da aggiungere.'];
        }

        $resourcesDirectory = $this->resolveResourcesDirectory($prj, $sid);
        if (!$resourcesDirectory) {
            return ['success' => false, 'message' => 'Cartella resources non trovata per questa ricerca.'];
        }

        $configPath = $resourcesDirectory . DIRECTORY_SEPARATOR . 'config.json';
        if (!is_file($configPath) || !is_readable($configPath)) {
            return ['success' => false, 'message' => 'File config.json non trovato o non leggibile.'];
        }

        $content = @file_get_contents($configPath);
        if ($content === false || trim($content) === '') {
            return ['success' => false, 'message' => 'File config.json vuoto o illeggibile.'];
        }

        $config = json_decode($content, true);
        if (!is_array($config)) {
            return ['success' => false, 'message' => 'File config.json non valido.'];
        }

        if (!isset($config['interview']['production']['redirections']) || !is_array($config['interview']['production']['redirections'])) {
            $config['interview']['production']['redirections'] = [];
        }

        $addedCount = 0;

        foreach ($newEntries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $parameterValue = trim((string) ($entry['parameter_value'] ?? ''));
            $exitCode = isset($entry['exit_code']) && $entry['exit_code'] !== '' ? (int) $entry['exit_code'] : null;
            $redirectTo = trim((string) ($entry['redirect_to'] ?? ''));
            $parameterName = trim((string) ($entry['parameter_name'] ?? 'pan'));

            if ($parameterValue === '' || $exitCode === null || $redirectTo === '') {
                continue;
            }

            $config['interview']['production']['redirections'][] = [
                'parameter_name' => $parameterName !== '' ? $parameterName : 'pan',
                'parameter_value' => $parameterValue,
                'exit_code' => $exitCode,
                'redirect_to' => $redirectTo,
            ];
            $addedCount++;
        }

        if ($addedCount === 0) {
            return ['success' => false, 'message' => 'Nessuna redirection valida da aggiungere (controlla panel, esito e link).'];
        }

        if (!is_writable($configPath)) {
            return ['success' => false, 'message' => 'File config.json non scrivibile.'];
        }

        $encoded = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return ['success' => false, 'message' => 'Errore durante la codifica del config.json.'];
        }

        if (@file_put_contents($configPath, $encoded) === false) {
            return ['success' => false, 'message' => 'Errore durante la scrittura del config.json.'];
        }

        return ['success' => true, 'added' => $addedCount];
    }

    /**
     * Ritorna il contenuto grezzo (stringa) del config.json di una ricerca,
     * o null se la cartella/il file non esistono o non sono leggibili.
     * Usato per la pubblicazione su Primis (upload del file cosi' com'e'),
     * dove serve il contenuto raw e non l'array decodificato.
     */
    public function readConfigJsonRaw(string $prj, string $sid): ?string
    {
        $resourcesDirectory = $this->resolveResourcesDirectory($prj, $sid);
        if (!$resourcesDirectory) {
            return null;
        }

        $configPath = $resourcesDirectory . DIRECTORY_SEPARATOR . 'config.json';
        if (!is_file($configPath) || !is_readable($configPath)) {
            return null;
        }

        $content = @file_get_contents($configPath);

        return ($content !== false && trim($content) !== '') ? $content : null;
    }

    /**
     * Lista grezza delle quote (quota.targets) cosi' come definite nel
     * config.json, con l'indice originale nell'array (necessario per
     * modificarle/eliminarle in modo puntuale con updateQuotaTarget() e
     * deleteQuotaTarget()). total_interviews e' gestito a parte perche'
     * non e' un elemento dell'array targets ma un valore singolo.
     */
    public function getQuotaTargetsList(string $prj, string $sid): array
    {
        $empty = ['total_interviews' => null, 'targets' => []];

        $resourcesDirectory = $this->resolveResourcesDirectory($prj, $sid);
        if (!$resourcesDirectory) {
            return $empty;
        }

        $configPath = $resourcesDirectory . DIRECTORY_SEPARATOR . 'config.json';
        if (!is_file($configPath) || !is_readable($configPath)) {
            return $empty;
        }

        $content = @file_get_contents($configPath);
        if ($content === false || trim($content) === '') {
            return $empty;
        }

        $config = json_decode($content, true);
        if (!is_array($config)) {
            return $empty;
        }

        $totalInterviews = $config['quota']['total_interviews'] ?? null;
        $targets = $config['quota']['targets'] ?? [];

        if (!is_array($targets)) {
            $targets = [];
        }

        $list = [];

        foreach ($targets as $rawIndex => $target) {
            if (!is_array($target)) {
                continue;
            }

            $list[] = [
                'index' => (int) $rawIndex,
                'name' => (string) ($target['name'] ?? ''),
                'description' => (string) ($target['description'] ?? ''),
                'question_id' => isset($target['question_id']) ? (int) $target['question_id'] : null,
                'option_id' => is_array($target['option_id'] ?? null) ? array_values($target['option_id']) : [],
                'parameter_name' => isset($target['parameter_name']) ? (string) $target['parameter_name'] : null,
                'parameter_value' => isset($target['parameter_value']) ? (string) $target['parameter_value'] : null,
                'value' => isset($target['value']) ? (int) $target['value'] : 0,
            ];
        }

        return [
            'total_interviews' => $totalInterviews !== null ? (int) $totalInterviews : null,
            'targets' => $list,
        ];
    }

    /**
     * Aggiunge una nuova quota in coda a quota.targets nel config.json.
     * Supporta le due forme viste nei config.json reali: quota legata a
     * domanda+opzioni (question_id + option_id[]) oppure quota legata a
     * un parametro (parameter_name + parameter_value, es. source_panel).
     */
    public function addQuotaTarget(string $prj, string $sid, array $entry): array
    {
        $resourcesDirectory = $this->resolveResourcesDirectory($prj, $sid);
        if (!$resourcesDirectory) {
            return ['success' => false, 'message' => 'Cartella resources non trovata per questa ricerca.'];
        }

        $configPath = $resourcesDirectory . DIRECTORY_SEPARATOR . 'config.json';
        if (!is_file($configPath) || !is_readable($configPath)) {
            return ['success' => false, 'message' => 'File config.json non trovato o non leggibile.'];
        }

        $content = @file_get_contents($configPath);
        if ($content === false || trim($content) === '') {
            return ['success' => false, 'message' => 'File config.json vuoto o illeggibile.'];
        }

        $config = json_decode($content, true);
        if (!is_array($config)) {
            return ['success' => false, 'message' => 'File config.json non valido.'];
        }

        if (!isset($config['quota']) || !is_array($config['quota'])) {
            $config['quota'] = [];
        }
        if (!isset($config['quota']['targets']) || !is_array($config['quota']['targets'])) {
            $config['quota']['targets'] = [];
        }

        $name = trim((string) ($entry['name'] ?? ''));
        $description = trim((string) ($entry['description'] ?? ''));
        $value = (isset($entry['value']) && $entry['value'] !== '' && is_numeric($entry['value'])) ? (int) $entry['value'] : null;
        $questionId = (isset($entry['question_id']) && $entry['question_id'] !== '' && is_numeric($entry['question_id'])) ? (int) $entry['question_id'] : null;
        $optionIds = $entry['option_id'] ?? [];
        $parameterName = trim((string) ($entry['parameter_name'] ?? ''));
        $parameterValue = trim((string) ($entry['parameter_value'] ?? ''));

        if ($name === '' || $value === null) {
            return ['success' => false, 'message' => 'Nome e valore della quota sono obbligatori.'];
        }

        foreach ($config['quota']['targets'] as $existing) {
            if (is_array($existing) && (string) ($existing['name'] ?? '') === $name) {
                return ['success' => false, 'message' => 'Esiste gia\' una quota con questo nome.'];
            }
        }

        $newTarget = [
            'name' => $name,
            'description' => $description !== '' ? $description : $name,
            'value' => $value,
        ];

        if ($questionId !== null && is_array($optionIds) && !empty($optionIds)) {
            $newTarget['question_id'] = $questionId;
            $newTarget['option_id'] = array_values(array_map('intval', $optionIds));
        } elseif ($parameterName !== '' && $parameterValue !== '') {
            $newTarget['parameter_name'] = $parameterName;
            $newTarget['parameter_value'] = $parameterValue;
        } else {
            return ['success' => false, 'message' => 'Specifica domanda + opzioni oppure parametro + valore per questa quota.'];
        }

        $newIndex = count($config['quota']['targets']);
        $config['quota']['targets'][] = $newTarget;

        $result = $this->writeConfigJson($configPath, $config);

        if ($result['success']) {
            // Stessa forma di getQuotaTargetsList(), cosi' il frontend puo'
            // aggiungere la riga in tabella senza ricaricare la pagina.
            $result['target'] = [
                'index' => $newIndex,
                'name' => $newTarget['name'],
                'description' => $newTarget['description'],
                'question_id' => $newTarget['question_id'] ?? null,
                'option_id' => $newTarget['option_id'] ?? [],
                'parameter_name' => $newTarget['parameter_name'] ?? null,
                'parameter_value' => $newTarget['parameter_value'] ?? null,
                'value' => $newTarget['value'],
            ];
        }

        return $result;
    }

    /**
     * Aggiorna una quota esistente in quota.targets, individuata tramite
     * l'indice originale nell'array (quello restituito da
     * getQuotaTargetsList()). Il nome non e' modificabile: e' la chiave
     * che lega la quota a t_quota_status.target_name, rinominarla
     * creerebbe una quota orfana lato Primis.
     */
    public function updateQuotaTarget(string $prj, string $sid, int $index, array $entry): array
    {
        $resourcesDirectory = $this->resolveResourcesDirectory($prj, $sid);
        if (!$resourcesDirectory) {
            return ['success' => false, 'message' => 'Cartella resources non trovata per questa ricerca.'];
        }

        $configPath = $resourcesDirectory . DIRECTORY_SEPARATOR . 'config.json';
        if (!is_file($configPath) || !is_readable($configPath)) {
            return ['success' => false, 'message' => 'File config.json non trovato o non leggibile.'];
        }

        $content = @file_get_contents($configPath);
        if ($content === false || trim($content) === '') {
            return ['success' => false, 'message' => 'File config.json vuoto o illeggibile.'];
        }

        $config = json_decode($content, true);
        if (!is_array($config) || !isset($config['quota']['targets'][$index]) || !is_array($config['quota']['targets'][$index])) {
            return ['success' => false, 'message' => 'Quota non trovata nel config.json.'];
        }

        $target = $config['quota']['targets'][$index];

        if (array_key_exists('description', $entry)) {
            $description = trim((string) $entry['description']);
            if ($description !== '') {
                $target['description'] = $description;
            }
        }

        if (array_key_exists('value', $entry) && $entry['value'] !== '' && is_numeric($entry['value'])) {
            $target['value'] = (int) $entry['value'];
        }

        if (array_key_exists('option_id', $entry) && is_array($entry['option_id']) && !empty($entry['option_id'])) {
            $target['option_id'] = array_values(array_map('intval', $entry['option_id']));
        }

        if (array_key_exists('parameter_value', $entry) && trim((string) $entry['parameter_value']) !== '') {
            $target['parameter_value'] = trim((string) $entry['parameter_value']);
        }

        $config['quota']['targets'][$index] = $target;

        return $this->writeConfigJson($configPath, $config);
    }

    /**
     * Elimina una quota da quota.targets tramite il suo indice originale
     * nell'array (quello restituito da getQuotaTargetsList()).
     */
    public function deleteQuotaTarget(string $prj, string $sid, int $index): array
    {
        $resourcesDirectory = $this->resolveResourcesDirectory($prj, $sid);
        if (!$resourcesDirectory) {
            return ['success' => false, 'message' => 'Cartella resources non trovata per questa ricerca.'];
        }

        $configPath = $resourcesDirectory . DIRECTORY_SEPARATOR . 'config.json';
        if (!is_file($configPath) || !is_readable($configPath)) {
            return ['success' => false, 'message' => 'File config.json non trovato o non leggibile.'];
        }

        $content = @file_get_contents($configPath);
        if ($content === false || trim($content) === '') {
            return ['success' => false, 'message' => 'File config.json vuoto o illeggibile.'];
        }

        $config = json_decode($content, true);
        if (!is_array($config) || !isset($config['quota']['targets'][$index]) || !is_array($config['quota']['targets'][$index])) {
            return ['success' => false, 'message' => 'Quota non trovata nel config.json.'];
        }

        $removedName = $config['quota']['targets'][$index]['name'] ?? null;

        unset($config['quota']['targets'][$index]);
        $config['quota']['targets'] = array_values($config['quota']['targets']);

        $result = $this->writeConfigJson($configPath, $config);

        if ($result['success']) {
            $result['removed_name'] = $removedName;
        }

        return $result;
    }

    /**
     * Aggiorna quota.total_interviews (il totale complessivo, non legato a
     * una singola domanda/opzione/panel).
     */
    public function updateQuotaTotalInterviews(string $prj, string $sid, int $value): array
    {
        $resourcesDirectory = $this->resolveResourcesDirectory($prj, $sid);
        if (!$resourcesDirectory) {
            return ['success' => false, 'message' => 'Cartella resources non trovata per questa ricerca.'];
        }

        $configPath = $resourcesDirectory . DIRECTORY_SEPARATOR . 'config.json';
        if (!is_file($configPath) || !is_readable($configPath)) {
            return ['success' => false, 'message' => 'File config.json non trovato o non leggibile.'];
        }

        $content = @file_get_contents($configPath);
        if ($content === false || trim($content) === '') {
            return ['success' => false, 'message' => 'File config.json vuoto o illeggibile.'];
        }

        $config = json_decode($content, true);
        if (!is_array($config)) {
            return ['success' => false, 'message' => 'File config.json non valido.'];
        }

        if (!isset($config['quota']) || !is_array($config['quota'])) {
            $config['quota'] = [];
        }

        $config['quota']['total_interviews'] = $value;

        return $this->writeConfigJson($configPath, $config);
    }

    /**
     * Helper comune di scrittura per le funzioni quota.*: codifica e
     * salva l'array di config aggiornato, con gli stessi controlli e lo
     * stesso formato di output (success/message) usati per le redirection.
     */
    private function writeConfigJson(string $configPath, array $config): array
    {
        if (!is_writable($configPath)) {
            return ['success' => false, 'message' => 'File config.json non scrivibile.'];
        }

        $encoded = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return ['success' => false, 'message' => 'Errore durante la codifica del config.json.'];
        }

        if (@file_put_contents($configPath, $encoded) === false) {
            return ['success' => false, 'message' => 'Errore durante la scrittura del config.json.'];
        }

        return ['success' => true];
    }

    /**
     * Analizza un testo incollato (tipicamente il testo di un'email di un
     * fornitore panel) e prova a riconoscere righe "Esito: URL" o "Esito\tURL",
     * convertendo eventuali placeholder (es. [transaction_id], [insert_value],
     * XXXX) nella sintassi $(nome_variabile) già usata nei redirect_to esistenti.
     * Il risultato è sempre da rivedere/confermare manualmente prima di scrivere
     * nel config.json: qui non scriviamo nulla, solo analisi.
     */
    public function parseRedirectionText(string $text): array
    {
        $statusMap = $this->getInterviewStatusMap();

        // Label -> exit_code per i casi non ambigui (il formato lo descrive
        // esplicitamente l'utente: 3=completa, 4=screenout, 5=quotafull).
        $certainLabels = [
            3 => ['complete', 'completes', 'completed', 'qualified'],
            4 => ['screenout', 'screen out', 'screened', 'termination', 'qualification termination', 'terminate', 'non in target'],
            5 => ['quotafull', 'quota full', 'overquota', 'over quota'],
        ];

        // Label che somigliano a un esito ma non sono riconducibili con certezza
        // a 3/4/5: le segnaliamo "da rivedere" senza indovinare l'exit_code.
        $ambiguousLabels = [
            'quality', 'quality check', 'quality checks',
            'dedupe', 'duplication', 'duplication/security termination', 'security termination',
        ];

        $reverse = [];
        foreach ($certainLabels as $code => $labels) {
            foreach ($labels as $label) {
                $reverse[$label] = $code;
            }
        }

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $text);
        $lineCount = count($lines);

        $results = [];

        for ($i = 0; $i < $lineCount; $i++) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }

            // Caso 1: label e URL sulla stessa riga (tabelle incollate da email,
            // separate da tab o da uno o più ":" seguiti da spazi).
            if (preg_match('/^(.*?)[:\t]+\s*(https?:\/\/\S+)\s*$/i', $line, $m)) {
                $rawLabel = trim($m[1]);
                $url = trim($m[2]);

                if ($rawLabel !== '' && !preg_match('/^https?:\/\//i', $rawLabel)) {
                    $results[] = $this->buildRedirectionCandidate($rawLabel, $url, $reverse, $ambiguousLabels, $statusMap);
                    continue;
                }
            }

            // Caso 2: riga di sola label (senza URL), con l'URL sulla riga
            // successiva non vuota.
            if (!preg_match('/https?:\/\//i', $line)) {
                $normalized = $this->normalizeRedirectionLabel($line);

                if (isset($reverse[$normalized]) || in_array($normalized, $ambiguousLabels, true)) {
                    $j = $i + 1;
                    while ($j < $lineCount && trim($lines[$j]) === '') {
                        $j++;
                    }

                    if ($j < $lineCount && preg_match('/(https?:\/\/\S+)/i', $lines[$j], $um)) {
                        $results[] = $this->buildRedirectionCandidate($line, trim($um[1]), $reverse, $ambiguousLabels, $statusMap);
                        $i = $j;
                    }
                }
            }
        }

        return $results;
    }

    private function normalizeRedirectionLabel(string $label): string
    {
        $label = strtolower(trim($label));
        $label = preg_replace('/\(.*?\)/', '', $label); // rimuove "(Qualified)", "(Terminate)" ecc.
        $label = rtrim($label, ": \t");
        $label = preg_replace('/\s+/', ' ', $label);

        return trim($label);
    }

    private function buildRedirectionCandidate(string $rawLabel, string $url, array $reverse, array $ambiguousLabels, array $statusMap): array
    {
        $normalized = $this->normalizeRedirectionLabel($rawLabel);
        $exitCode = $reverse[$normalized] ?? null;
        $isKnownAmbiguous = in_array($normalized, $ambiguousLabels, true);

        [$cleanUrl, $variables] = $this->extractRedirectionVariables($url);

        return [
            'raw_label' => $rawLabel,
            'exit_code' => $exitCode,
            'exit_label' => $exitCode !== null ? ($statusMap[$exitCode] ?? null) : null,
            'redirect_to' => $cleanUrl,
            'variables' => $variables,
            'needs_review' => $exitCode === null || $isKnownAmbiguous,
        ];
    }

    /**
     * Sostituisce i placeholder noti in un link di redirect con la sintassi
     * $(nome_variabile): [nome_esplicito] viene usato cosi' com'e', mentre
     * placeholder generici ([insert_value], XXXX, XXXXXX) vengono rinominati
     * in base al parametro della query string che li precede (es. RID=XXXX
     * diventa RID=$(rid)). Se non si riesce a risalire a un nome, il
     * placeholder viene lasciato invariato (andra' corretto a mano in fase di
     * revisione).
     */
    private function extractRedirectionVariables(string $url): array
    {
        $variables = [];
        $genericNames = ['insert_value', 'insertvalue', 'value', 'placeholder', 'insert_here', 'your_value', 'xxx'];

        // [nome] preceduto da "chiave=" direttamente attaccato
        $url = preg_replace_callback('/([a-zA-Z0-9_\.]+)=\[([a-zA-Z0-9_]+)\]/', function ($m) use (&$variables, $genericNames) {
            $key = $m[1];
            $bracketName = strtolower($m[2]);
            $varName = in_array($bracketName, $genericNames, true) ? strtolower($key) : $bracketName;
            $variables[$varName] = $varName;

            return $key . '=$(' . $varName . ')';
        }, $url);

        // [nome] isolato, non preceduto da "chiave="
        $url = preg_replace_callback('/\[([a-zA-Z0-9_]+)\]/', function ($m) use (&$variables) {
            $varName = strtolower($m[1]);
            $variables[$varName] = $varName;

            return '$(' . $varName . ')';
        }, $url);

        // XXXX / XXXXXX preceduto da "chiave=" (placeholder generico senza nome esplicito)
        $url = preg_replace_callback('/([a-zA-Z0-9_\.]+)=([Xx]{3,})(?=[&#\s]|$)/', function ($m) use (&$variables) {
            $varName = strtolower($m[1]);
            $variables[$varName] = $varName;

            return $m[1] . '=$(' . $varName . ')';
        }, $url);

        return [$url, array_values($variables)];
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
            'last_file_question_code' => $this->extractLastQuestionCodeFromFile($file),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CORE: BUILD DATASET (NUOVA LOGICA PANEL)
    |--------------------------------------------------------------------------
    */

    public function buildInterviewDataset(array $files, string $prj, string $sid, bool $keepRaw = false): array
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

            $uidInteractive = null;
            if ($pan === null) {
                $uidInteractive = $this->extractUidSafe($parsed);
                if ($uidInteractive !== null) {
                    $uidsToCheck[$uidInteractive] = true;
                }
            }

            if (!$keepRaw) {
                unset($parsed['raw']);
            }

            $parsed['pan'] = $pan;
            $parsed['_uid_interactive'] = $uidInteractive;
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
                // 2. fallback UID (già estratto nel 1° loop)
                $uid = $parsed['_uid_interactive'];

                if ($uid !== null && isset($validInteractiveUids[$uid])) {
                    $panel = 'Interactive';
                } else {
                    $panel = 'Da lista';
                }
            }

            unset($parsed['_uid_interactive']);
            $parsed['panel'] = $panel;

            $interviews[] = $parsed;
        }

        unset($parsedList, $validInteractiveUids);

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
            'bad_quality' => 0,
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
                    'bad_quality' => 0,
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
                case 10:
                    $counts['bad_quality']++;
                    $panelCounts[$panel]['bad_quality']++;
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

    $interviews = array_slice($interviews, 0, 1000);

    $statusMap = $this->getInterviewStatusMap();
    $logData = [];

    foreach ($interviews as $interview) {
        $systemQuestionHtml = $this->buildQuestionTooltipHtml(
            (int) $interview['last_question_code'],
            $questionMap,
            'Ultima domanda da sistema'
        );

        $lastFileQuestionCode = (int) ($interview['last_file_question_code'] ?? 0);
        $fileQuestionHtml = $lastFileQuestionCode > 0
            ? $this->buildQuestionTooltipHtml(
                $lastFileQuestionCode,
                $questionMap,
                'Ultima domanda nel File Dati'
            )
            : 'empty';

        $panelLabel = (string) ($interview['panel'] ?? 'Sconosciuto');
        $statoLabel = $statusMap[$interview['status_code']] ?? 'Sconosciuto';

        $logData[] = [
            'iid' => $interview['iid'],
            'uid' => $interview['uid'],
            'ultimo_update' => $this->formatLogUpdateHtml($interview['end_date']),
            'ultima_azione_sistema' => $systemQuestionHtml,
            'ultima_azione_file' => $fileQuestionHtml,
            'stato' => $statoLabel,
            'durata' => $this->formatDuration((int) $interview['duration']),
            'panel' => $panelLabel,
            'iid_tooltip' => $this->buildIidTooltipHtml($interview, $questionMap, $panelLabel, $statoLabel),
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
                'bad_quality' => 0,
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
            case 10:
                $dataSummaryByPanel[$panel][$dayKey]['bad_quality']++;
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

    public function resolveDownloadFieldValue(array $interview, string $fieldKey, string $prj, string $sid): string
    {
        $value = $this->extractTaggedFieldValue($interview['raw'], $fieldKey);

        if ($value !== 'N/A') {
            return $value;
        }

        switch ($fieldKey) {
            case 'uid':
            case 'sysUID':
                return isset($interview['uid']) ? (string) $interview['uid'] : 'N/A';
            case 'iid':
                return isset($interview['iid']) ? (string) $interview['iid'] : 'N/A';
            case 'prj':
            case 'mioprj':
                return $prj;
            case 'sid':
            case 'miosid':
                return $sid;
            default:
                return 'N/A';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | METODI ORIGINALI (NON TOCCATI)
    |--------------------------------------------------------------------------
    */

    public function getInterviewStatusMap(): array
    {
        return [
            0  => 'In Corso',
            3  => 'Completa',
            4  => 'Non in target',
            5  => 'Quotafull',
            7  => 'Stop Int',
            10 => 'Bad Quality',
        ];
    }

    public function getDownloadStatusMap(): array
    {
        return [
            0  => 'suspended',
            3  => 'complete',
            4  => 'screenout',
            5  => 'quotafull',
            7  => 'stopInt',
            10 => 'badQuality',
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

    private function extractLastQuestionCodeFromFile(string $file): int
    {
        if (!is_readable($file)) {
            return 0;
        }

        $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (!is_array($lines) || count($lines) < 2) {
            return 0;
        }

        for ($i = count($lines) - 1; $i >= 1; $i--) {
            $line = trim((string) $lines[$i]);

            if ($line === '') {
                continue;
            }

            $parts = explode(';', $line);
            $questionCode = isset($parts[1]) ? trim((string) $parts[1]) : '';

            if ($questionCode !== '' && is_numeric($questionCode)) {
                return (int) $questionCode;
            }
        }

        return 0;
    }

    private function buildQuestionTooltipHtml(int $questionId, array $questionMap, string $tooltipPrefix): string
    {
        $questionDetails = $questionMap[$questionId] ?? [
            'code' => 'N/A',
            'text' => 'Domanda non trovata',
        ];

        $questionCode = e($questionDetails['code']);
        $questionText = e($questionDetails['text']);
        $tooltipTitle = '<div class="fc-question-tooltip-content">'
            . '<div class="fc-question-tooltip-label">' . e($tooltipPrefix) . '</div>'
            . '<div class="fc-question-tooltip-divider"></div>'
            . '<div class="fc-question-tooltip-text">' . $questionText . '</div>'
            . '</div>';

        return "<span data-bs-toggle='tooltip' data-bs-html='true' data-bs-custom-class='fc-question-tooltip' title='{$tooltipTitle}'>{$questionCode}</span>";
    }

    private function buildIidTooltipHtml(array $interview, array $questionMap, string $panelLabel, string $statoLabel): string
    {
        $sysCode = (int) ($interview['last_question_code'] ?? 0);
        $sysLabel = $questionMap[$sysCode]['code'] ?? 'N/A';

        $fileCode = (int) ($interview['last_file_question_code'] ?? 0);
        $fileLabel = $fileCode > 0 ? ($questionMap[$fileCode]['code'] ?? 'N/A') : '—';

        $rows = [
            ['Panel', $panelLabel],
            ['UID', (string) ($interview['uid'] ?? 'N/A')],
            ['Stato', $statoLabel],
            ['Inizio', (string) ($interview['start_date'] ?? 'N/A')],
            ['Fine', (string) ($interview['end_date'] ?? 'N/A')],
            ['Durata', $this->formatDuration((int) ($interview['duration'] ?? 0))],
            ['Ultima domanda (sistema)', (string) $sysLabel],
            ['Ultima domanda (file)', (string) $fileLabel],
        ];

        $html = '<div class="fc-iid-tooltip">';
        foreach ($rows as [$label, $value]) {
            $html .= '<div class="fc-iid-tooltip-row"><span class="fc-iid-tooltip-label">' . e($label) . '</span><span class="fc-iid-tooltip-value">' . e($value) . '</span></div>';
        }
        $html .= '</div>';

        return $html;
    }

    private function formatLogUpdateHtml($rawDate): string
    {
        $date = trim((string) $rawDate);

        if ($date === '' || strtoupper($date) === 'N/A') {
            return 'N/A';
        }

        $parsedDate = $this->parseDateToCarbon($date);

        if ($parsedDate instanceof Carbon) {
            return e($parsedDate->format('d/m/Y')) . '<br><small class="text-muted">' . e($parsedDate->format('H:i:s')) . '</small>';
        }

        $cleanDate = trim(str_replace(array(' CET', ' CEST'), '', $date));
        $parts = preg_split('/\s+/', $cleanDate);

        if (is_array($parts) && count($parts) >= 2) {
            $time = array_pop($parts);
            $onlyDate = implode(' ', $parts);

            return e($onlyDate) . '<br><small class="text-muted">' . e($time) . '</small>';
        }

        return e($cleanDate);
    }

    private function extractSreFileNumber(string $file): int
    {
        return (int) substr(pathinfo($file, PATHINFO_FILENAME), 3);
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE — Bad Quality flag (scrittura sicura sul .sre)
    |--------------------------------------------------------------------------
    */

    /**
     * Trova il file .sre corrispondente a un IID in una directory, leggendo
     * solo la prima riga di ciascun file (stesso pattern di parseSreFile()).
     */
    public function locateSreFileByIid(string $directory, string $iid): ?string
    {
        foreach ($this->getSreFiles($directory) as $file) {
            $parsed = $this->parseSreFile($file);
            if (!empty($parsed) && (string) $parsed['iid'] === $iid) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Aggiorna atomicamente lo status (campo 8/9 in prima riga, offset-aware) di un
     * file .sre, SOLO se lo stato corrente è $fromStatus. Preserva byte-per-byte tutto
     * il contenuto successivo alla prima riga (incluso il terminatore di riga originale),
     * fa backup non distruttivo in resBackup/ e scrive via file temporaneo + rename
     * (mai un file a metà scritto).
     *
     * @return array{success: bool, error: ?string, current_status: ?int, uid: ?string, iid: ?string}
     */
    public function writeSreStatus(string $file, int $fromStatus, int $toStatus): array
    {
        $raw = @file_get_contents($file);
        if ($raw === false) {
            return ['success' => false, 'error' => 'read_failed', 'current_status' => null, 'uid' => null, 'iid' => null];
        }

        $pos = strpos($raw, "\n");
        if ($pos === false) {
            $firstLineRaw = $raw;
            $eol  = '';
            $rest = '';
        } elseif ($pos > 0 && $raw[$pos - 1] === "\r") {
            $firstLineRaw = substr($raw, 0, $pos - 1);
            $eol  = "\r\n";
            $rest = substr($raw, $pos + 1);
        } else {
            $firstLineRaw = substr($raw, 0, $pos);
            $eol  = "\n";
            $rest = substr($raw, $pos + 1);
        }

        $columns = explode(';', trim($firstLineRaw));
        $colMap  = $this->getSreColumnMap($columns);
        $idx     = $colMap['status'];

        if (!isset($columns[$idx])) {
            return ['success' => false, 'error' => 'status_index_missing', 'current_status' => null, 'uid' => null, 'iid' => null];
        }

        $current = (int) trim($columns[$idx]);
        $uid = (string) $this->getSreValue($columns, $colMap['uid']);
        $iid = (string) $this->getSreValue($columns, $colMap['iid']);

        if ($current !== $fromStatus) {
            return ['success' => false, 'error' => 'unexpected_status', 'current_status' => $current, 'uid' => $uid, 'iid' => $iid];
        }

        $columns[$idx] = (string) $toStatus;
        $newContent = implode(';', $columns) . $eol . $rest;

        $backupDir = dirname($file) . '/../resBackup';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }
        $backupFile = $backupDir . '/' . basename($file);
        if (!file_exists($backupFile)) {
            @copy($file, $backupFile);
        }

        $tmpFile = $file . '.tmp' . uniqid('', true);
        if (@file_put_contents($tmpFile, $newContent, LOCK_EX) === false) {
            @unlink($tmpFile);
            return ['success' => false, 'error' => 'write_failed', 'current_status' => $current, 'uid' => $uid, 'iid' => $iid];
        }
        if (!@rename($tmpFile, $file)) {
            @unlink($tmpFile);
            return ['success' => false, 'error' => 'rename_failed', 'current_status' => $current, 'uid' => $uid, 'iid' => $iid];
        }

        return ['success' => true, 'error' => null, 'current_status' => $toStatus, 'uid' => $uid, 'iid' => $iid];
    }
}
