<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\EmbeddingClient;
use RuntimeException;

/**
 * Embeddings multilingues bge-m3 (FR), service auto-hébergé. Dimension 1024,
 * doit correspondre à la colonne pgvector des chunks.
 * Implémentation de embed() : story 1.4.
 */
final class BgeM3EmbeddingClient implements EmbeddingClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $dimensions = 1024,
    ) {
    }

    public function embed(array $texts): array
    {
        throw new RuntimeException('BgeM3EmbeddingClient::embed à implémenter (story 1.4).');
    }

    public function dimensions(): int
    {
        return $this->dimensions;
    }

    public function provider(): string
    {
        return 'bge_m3';
    }
}
