<?php

declare(strict_types=1);

namespace App\Services\Session;

use App\Models\ExplorerSession;

/**
 * Purge des sessions expirées (logique one-shot, story 5.4).
 * La suppression cascade sur interactions, épingles, présentations et débats
 * (contraintes FK). Les audit_logs conservent leur trace (référence dénouée).
 */
final class SessionPurger
{
    public function purgeExpired(): int
    {
        return ExplorerSession::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();
    }
}
