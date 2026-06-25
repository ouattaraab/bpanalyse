<?php

declare(strict_types=1);

namespace App\Services\Ingestion\Data;

/**
 * Une page/slide parsée. Pour un PPTX, 1 slide = 1 diapositive ;
 * pour un PDF, 1 slide = 1 page. Le Markdown préserve les tableaux.
 */
final readonly class ParsedSlide
{
    public function __construct(
        public int $index,
        public string $markdown,
        public ?string $title = null,
        public ?string $section = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            index: (int) $data['index'],
            markdown: (string) ($data['markdown'] ?? ''),
            title: isset($data['title']) ? (string) $data['title'] : null,
            section: isset($data['section']) ? (string) $data['section'] : null,
        );
    }
}
