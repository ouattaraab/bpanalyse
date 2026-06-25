<?php

declare(strict_types=1);

namespace App\Services\Ingestion\Data;

/**
 * Une mesure financière extraite : (poste, période) → valeur + unité.
 * Valeur toujours issue verbatim du tableau (jamais calculée par un LLM).
 */
final readonly class ParsedMetric
{
    public function __construct(
        public string $label,
        public float $value,
        public ?string $periodLabel = null,
        public ?int $periodYear = null,
        public ?string $unit = null,
    ) {}
}
