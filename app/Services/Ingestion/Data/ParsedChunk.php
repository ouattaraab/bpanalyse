<?php

declare(strict_types=1);

namespace App\Services\Ingestion\Data;

use App\Enums\ChunkType;

/**
 * Un chunk prêt à indexer, issu du découpage d'une slide.
 * Pour un tableau : `caption` porte sa légende et `content` contient le tableau
 * Markdown intact (jamais scindé).
 */
final readonly class ParsedChunk
{
    public function __construct(
        public ChunkType $type,
        public string $content,
        public ?string $section = null,
        public ?string $caption = null,
    ) {}
}
