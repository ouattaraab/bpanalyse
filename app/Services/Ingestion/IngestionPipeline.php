<?php

declare(strict_types=1);

namespace App\Services\Ingestion;

use App\Jobs\ChunkDocumentJob;
use App\Jobs\EmbedChunksJob;
use App\Jobs\ExtractFinancialsJob;
use App\Jobs\ParseDocumentJob;
use App\Models\Document;
use Illuminate\Support\Facades\Bus;

/**
 * Orchestration du pipeline d'ingestion : parse → chunk → (embeddings, extraction
 * financière). Les jobs sont chaînés : chacun s'exécute après le précédent.
 *
 * Embeddings et extraction financière dépendent tous deux des chunks ; l'ordre
 * Embed puis Extract est sans incidence (indépendants entre eux).
 */
final class IngestionPipeline
{
    public function dispatch(Document $document): void
    {
        Bus::chain([
            new ParseDocumentJob($document->id),
            new ChunkDocumentJob($document->id),
            new EmbedChunksJob($document->id),
            new ExtractFinancialsJob($document->id),
        ])->dispatch();
    }
}
