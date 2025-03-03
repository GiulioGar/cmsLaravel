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



}
