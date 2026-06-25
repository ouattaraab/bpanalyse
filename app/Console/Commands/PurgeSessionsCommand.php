<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Session\SessionPurger;
use Illuminate\Console\Command;

/**
 * Purge programmée des sessions expirées (planifiée dans routes/console.php).
 */
final class PurgeSessionsCommand extends Command
{
    protected $signature = 'sessions:purge';

    protected $description = 'Supprime les sessions expirées et leurs données associées (one-shot).';

    public function handle(SessionPurger $purger): int
    {
        $count = $purger->purgeExpired();
        $this->info("Sessions expirées purgées : {$count}");

        return self::SUCCESS;
    }
}
