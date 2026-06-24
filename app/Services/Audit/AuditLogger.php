<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\ExplorerSession;
use App\Models\Interaction;

/**
 * Journalise TOUTE réponse (règle CLAUDE.md §4). Appelé par chaque mode
 * (chat, présentation, débat) après production d'une réponse.
 */
final class AuditLogger
{
    /**
     * @param  array<int, array<string, mixed>>  $sources  citations (slide/section/tableau)
     */
    public function log(
        ExplorerSession $session,
        ?Interaction $interaction,
        string $mode,
        string $question,
        array $sources,
        ?string $modelUsed,
        ?int $latencyMs,
    ): AuditLog {
        return AuditLog::create([
            'explorer_session_id' => $session->id,
            'interaction_id' => $interaction?->id,
            'document_id' => $session->document_id,
            'mode' => $mode,
            'question' => $question,
            'sources' => $sources,
            'model_used' => $modelUsed,
            'latency_ms' => $latencyMs,
        ]);
    }
}
