<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class NotificationsController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function summary(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->notificationService->getTopbarSummary(),
        ]);
    }
}
