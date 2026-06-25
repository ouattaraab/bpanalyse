<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Services\Ingestion\Contracts\DocumentParser;
use App\Services\Ingestion\Data\ParsedSlide;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Parse un document via DocumentParser (Docling) et persiste les slides.
 * Transitions de statut : parsing → parsed (ou failed en cas d'erreur).
 *
 * Le parser est injecté (résolu par le conteneur) → mockable en test.
 */
final class ParseDocumentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $documentId) {}

    public function handle(DocumentParser $parser): void
    {
        $document = Document::findOrFail($this->documentId);

        $document->update(['status' => DocumentStatus::Parsing]);

        $absolutePath = Storage::disk('documents')->path($document->original_path);

        $parsed = $parser->parse($absolutePath);

        DB::transaction(function () use ($document, $parsed): void {
            $document->slides()->delete();

            foreach ($parsed->slides as $slide) {
                /** @var ParsedSlide $slide */
                $document->slides()->create([
                    'slide_index' => $slide->index,
                    'title' => $slide->title,
                    'section' => $slide->section,
                    'raw_markdown' => $slide->markdown,
                ]);
            }

            $document->update([
                'status' => DocumentStatus::Parsed,
                'page_count' => $parsed->pageCount,
                'title' => $document->title ?: ($parsed->title ?? $document->title),
            ]);
        });
    }

    public function failed(?Throwable $exception): void
    {
        Document::whereKey($this->documentId)->update(['status' => DocumentStatus::Failed]);
    }
}
