<?php

declare(strict_types=1);

namespace App\Services\Ingestion\Data;

/**
 * Résultat structuré du parsing d'un document : Markdown global + slides/pages,
 * tableaux préservés. Produit par DocumentParser, consommé par ParseDocumentJob
 * puis le chunking (story 1.3).
 */
final readonly class ParsedDocument
{
    /** @param array<int, ParsedSlide> $slides */
    public function __construct(
        public string $markdown,
        public array $slides,
        public int $pageCount,
        public ?string $title = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $slides = array_map(
            static fn (array $slide): ParsedSlide => ParsedSlide::fromArray($slide),
            $data['slides'] ?? [],
        );

        return new self(
            markdown: (string) ($data['markdown'] ?? ''),
            slides: $slides,
            pageCount: (int) ($data['page_count'] ?? count($slides)),
            title: isset($data['title']) ? (string) $data['title'] : null,
        );
    }
}
