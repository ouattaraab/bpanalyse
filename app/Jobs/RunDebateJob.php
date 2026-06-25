<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Debate;
use App\Services\Debate\DebateOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Exécute le débat (orchestrateur tour-par-tour) de façon asynchrone.
 * Les répliques sont persistées au fil de l'eau ; statut → completed/failed.
 */
final class RunDebateJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $debateId) {}

    public function handle(DebateOrchestrator $orchestrator): void
    {
        $orchestrator->run(Debate::findOrFail($this->debateId));
    }

    public function failed(?Throwable $exception): void
    {
        Debate::whereKey($this->debateId)->update(['status' => 'failed']);
    }
}
