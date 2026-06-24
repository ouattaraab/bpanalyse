<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

/**
 * Génération d'embeddings multilingues (français).
 * Provider : bge-m3 / multilingual-e5-large (auto-hébergé recommandé).
 */
interface EmbeddingClient
{
    /**
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>  un vecteur par texte
     */
    public function embed(array $texts): array;

    /** Dimension des vecteurs (doit correspondre à la colonne pgvector). */
    public function dimensions(): int;

    public function provider(): string;
}
