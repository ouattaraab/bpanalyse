<?php

declare(strict_types=1);

namespace App\Services\Session;

use App\Models\Document;
use App\Models\ExplorerSession;
use Illuminate\Support\Str;

/**
 * Création et résolution des sessions éphémères (logique one-shot).
 */
final class SessionService
{
    /** Durée de vie par défaut d'une session (heures). */
    private const TTL_HOURS = 6;

    public function start(Document $document): ExplorerSession
    {
        return ExplorerSession::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $document->tenant_id,
            'document_id' => $document->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addHours(self::TTL_HOURS),
        ]);
    }

    public function resolve(string $uuid): ExplorerSession
    {
        return ExplorerSession::where('uuid', $uuid)->firstOrFail();
    }
}
