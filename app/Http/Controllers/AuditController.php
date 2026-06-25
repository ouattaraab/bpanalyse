<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ExplorerSession;
use Illuminate\Http\JsonResponse;

/**
 * Consultation de l'audit d'une session (story 5.3) : trace de TOUTE réponse
 * (chat, présentation, débat) — question, sources, modèle, horodatage.
 */
class AuditController extends Controller
{
    public function index(ExplorerSession $session): JsonResponse
    {
        $logs = AuditLog::query()
            ->where('explorer_session_id', $session->id)
            ->latest()
            ->get()
            ->map(static fn (AuditLog $log): array => [
                'mode' => $log->mode,
                'question' => $log->question,
                'sources' => $log->sources ?? [],
                'model_used' => $log->model_used,
                'latency_ms' => $log->latency_ms,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $logs]);
    }
}
