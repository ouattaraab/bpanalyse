<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Ingestion\DocumentIntakeService;
use App\Services\Ingestion\IngestionPipeline;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Ingestion d'un BP depuis un fichier local : intake puis pipeline
 * (parse → chunk → embeddings → extraction financière).
 *
 * Le pipeline est chaîné sur la file : lancer `php artisan queue:work` pour le
 * dérouler. En environnement `sync`, il s'exécute immédiatement.
 */
final class IngestBpCommand extends Command
{
    protected $signature = 'bp:ingest {file : Chemin du PDF/PPTX} {--tenant= : Id ou slug du tenant} {--title= : Titre du document}';

    protected $description = 'Ingère un business plan (parse, chunk, embeddings, extraction financière).';

    public function handle(DocumentIntakeService $intake, IngestionPipeline $pipeline): int
    {
        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            $this->error("Fichier introuvable : {$file}");

            return self::FAILURE;
        }

        $tenant = $this->resolveTenant();

        $document = $intake->storeFromPath($tenant, $file, $this->option('title'));

        $this->info("Document #{$document->id} créé (tenant #{$tenant->id}, statut {$document->status->value}).");

        $pipeline->dispatch($document);

        $this->info('Pipeline d\'ingestion lancé (parse → chunk → embeddings → financials).');
        $this->line('Déroulez-le avec : php artisan queue:work');

        return self::SUCCESS;
    }

    private function resolveTenant(): Tenant
    {
        $ref = $this->option('tenant');

        if ($ref === null) {
            return Tenant::firstOrCreate(
                ['slug' => 'default'],
                ['name' => 'Default'],
            );
        }

        $tenant = is_numeric($ref)
            ? Tenant::find((int) $ref)
            : Tenant::where('slug', $ref)->first();

        if ($tenant === null) {
            $tenant = Tenant::create(['name' => (string) $ref, 'slug' => Str::slug((string) $ref)]);
        }

        return $tenant;
    }
}
