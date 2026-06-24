<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Models\Chunk;
use App\Services\AI\Contracts\EmbeddingClient;
use Illuminate\Support\Collection;

/**
 * Recherche sémantique des chunks d'un document (cosine, pgvector).
 * La requête est embarquée par le même modèle (bge-m3) que l'indexation.
 *
 * @phpstan-type ChunkCollection Collection<int, Chunk>
 */
final class Retriever
{
    public function __construct(private readonly EmbeddingClient $embedder)
    {
    }

    /**
     * @return Collection<int, Chunk>  chunks triés par proximité (attribut `distance`)
     */
    public function retrieve(int $documentId, string $query, int $k = 6): Collection
    {
        $vector = $this->embedder->embed([$query])[0] ?? [];

        if ($vector === []) {
            return collect();
        }

        $literal = $this->toVectorLiteral($vector);

        return Chunk::query()
            ->where('document_id', $documentId)
            ->whereNotNull('embedding')
            ->select('chunks.*')
            ->selectRaw('(embedding <=> ?::vector) as distance', [$literal])
            ->orderByRaw('embedding <=> ?::vector', [$literal])
            ->limit($k)
            ->get();
    }

    /** @param array<int, float> $vector */
    private function toVectorLiteral(array $vector): string
    {
        return '['.implode(',', array_map(static fn (float $v): string => (string) $v, $vector)).']';
    }
}
