<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PrimisApiService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.primis.base_url');
        $this->token   = config('services.primis.token');
    }

    /**
     * Esempio: chiama l'endpoint /info (se non richiede token, potete omettere l'header).
     */
    public function getApiInfo()
    {
        $url = $this->baseUrl . 'info';

        // Se /info non richiede auth, potete fare una get "liscia"
        $response = Http::get($url);

        if ($response->failed()) {
            throw new \Exception('Errore di comunicazione con Primis [info]');
        }

        return $response->json();
    }

    /**
     * Chiamata a /projects (che richiede auth con il singolo token).
     */
    public function listProjects()
    {
        $url = $this->baseUrl . 'projects';

        // Impostiamo il Bearer Token con quello univoco
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept'        => 'application/json',
        ])->get($url);

        if ($response->failed()) {
            throw new \Exception('Errore di comunicazione con Primis [projects]');
        }

        return $response->json();
    }

    public function getSurveyDetails(string $projectName, string $surveyId)
{
    $url = $this->baseUrl . 'projects/' . $projectName . '/surveys/' . $surveyId;

    // Ricordati di aggiungere il Bearer token
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
        'Accept'        => 'application/json',
    ])->get($url);

    if ($response->failed()) {
        // Puoi controllare se è 404 (survey non trovata), 401 (accesso negato) ecc.
        throw new \Exception('Errore di comunicazione con Primis [getSurveyDetails]');
    }

    return $response->json();
}

/**
 * Ritorna la lista di domande di un survey in uno specifico progetto,
 * con eventuale filtro per 'type' (facoltativo).
 */
public function listQuestions(string $projectName, string $surveyId, ?string $type = null)
{
    // Costruiamo l'URL base
    $url = $this->baseUrl."projects/{$projectName}/surveys/{$surveyId}/questions";

    // Se vuoi filtrare per type (es. 'open', 'choice', etc.)
    if (!empty($type)) {
        $url .= '?type=' . $type;
    }

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
        'Accept'        => 'application/json'
    ])->get($url);

    if ($response->failed()) {
        throw new \Exception("Errore di comunicazione [listQuestions]");
    }

    return $response->json();
}


/**
 * Ritorna i dettagli di una singola domanda
 * (compresi campi come "options", se la domanda è di tipo choice/scale ecc.)
 *
 * GET /api/v1/projects/<project_name>/surveys/<survey_id>/questions/<question_id>
 */

public function getQuestion(string $projectName, string $surveyId, int $questionId)
{
    $url = $this->baseUrl."projects/{$projectName}/surveys/{$surveyId}/questions/{$questionId}";

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
        'Accept'        => 'application/json'
    ])->get($url);

    if ($response->failed()) {
        throw new \Exception("Errore di comunicazione con Primis [getQuestion]");
    }

    return $response->json();
}

/**
 * Aggiorna lo status di un survey (0=design, 1=testing, 2=collection,
 * 3=analysis, 4=closed). La risposta puo' contenere "success": false
 * con HTTP 200 (es. status non valido): l'interpretazione resta al
 * chiamante, qui si solleva eccezione solo per errori di comunicazione.
 */
public function updateSurveyStatus(string $projectName, string $surveyId, int $status): array
{
    $url = $this->baseUrl . "projects/{$projectName}/surveys/{$surveyId}/update";

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
        'Accept'        => 'application/json',
    ])->put($url, ['status' => $status]);

    if ($response->failed()) {
        throw new \Exception('Errore di comunicazione con Primis [updateSurveyStatus]');
    }

    return $response->json();
}

}
