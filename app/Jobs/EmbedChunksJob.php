<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\Chunk;
use App\Models\Document;
use App\Services\AI\Contracts\EmbeddingClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Calcule les embeddings des chunks d'un document (par lots) et les écrit dans
 * la colonne pgvector `chunks.embedding`. Passe le document en `indexed`.
 *
 * Les textes sont envoyés par lot au client (le modèle bge-m3 est chargé une
 * fois par lot). En cas d'échec, le document repasse en `failed`.
 */
final class EmbedChunksJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $documentId,
        public readonly int $batchSize = 256,
    ) {
    }

    public function handle(EmbeddingClient $embedder): void
    {
        $document = Document::findOrFail($this->documentId);

        $document->chunks()
            ->orderBy('id')
            ->chunkById($this->batchSize, function (Collection $chunks) use ($embedder): void {
                $texts = $chunks->map(static fn (Chunk $chunk): string => $chunk->content)->all();
                $vectors = $embedder->embed(array_values($texts));

                foreach ($chunks->values() as $index => $chunk) {
                    /** @var Chunk $chunk */
                    DB::update(
                        'UPDATE chunks SET embedding = ?::vector WHERE id = ?',
                        [$this->toVectorLiteral($vectors[$index]), $chunk->id],
                    );
                }
            });

        $document->update(['status' => DocumentStatus::Indexed]);
    }

    public function failed(?Throwable $exception): void
    {
        Document::whereKey($this->documentId)->update(['status' => DocumentStatus::Failed]);
    }

    /** @param array<int, float> $vector */
    private function toVectorLiteral(array $vector): string
    {
        return '['.implode(',', array_map(static fn (float $v): string => (string) $v, $vector)).']';
    }
}
