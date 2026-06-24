<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentSlide;
use App\Services\Ingestion\Data\ParsedChunk;
use App\Services\Ingestion\SemanticChunker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Découpe les slides d'un document en chunks indexables (1 tableau = 1 chunk).
 * Étape entre `parsed` et `indexed` ; les embeddings (story 1.4) prennent le relais.
 */
final class ChunkDocumentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $documentId)
    {
    }

    public function handle(SemanticChunker $chunker): void
    {
        $document = Document::with('slides')->findOrFail($this->documentId);

        DB::transaction(function () use ($document, $chunker): void {
            $document->chunks()->delete();

            foreach ($document->slides as $slide) {
                /** @var DocumentSlide $slide */
                $chunks = $chunker->chunkMarkdown($slide->raw_markdown, $slide->section);

                foreach ($chunks as $chunk) {
                    /** @var ParsedChunk $chunk */
                    $document->chunks()->create([
                        'document_slide_id' => $slide->id,
                        'section' => $chunk->section,
                        'type' => $chunk->type,
                        'content' => $chunk->content,
                        'caption' => $chunk->caption,
                        'metadata' => [
                            'slide_id' => $slide->id,
                            'slide_index' => $slide->slide_index,
                            'section' => $chunk->section,
                            'type' => $chunk->type->value,
                            'caption' => $chunk->caption,
                        ],
                    ]);
                }
            }
        });
    }
}
