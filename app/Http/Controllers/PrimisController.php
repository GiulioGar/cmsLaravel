<?php

namespace App\Http\Controllers;

use App\Services\PrimisApiService;
use Illuminate\Http\Request;

class PrimisController extends Controller
{
    public function getInfo(PrimisApiService $primis)
    {
        try {
            $info = $primis->getApiInfo();
            return response()->json($info);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getProjects(PrimisApiService $primis)
    {
        try {
            $projects = $primis->listProjects();
            return response()->json($projects);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getSurvey(PrimisApiService $primis, $projectName, $surveyId)
{
    try {
        $survey = $primis->getSurveyDetails($projectName, $surveyId);
        return response()->json($survey);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function getQuestions(PrimisApiService $primis, $projectName, $surveyId)
{
    try {
        // Se non vuoi filtrare per type, passa null o ometti il terzo argomento
        $questions = $primis->listQuestions($projectName, $surveyId);
        return response()->json($questions);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


}
