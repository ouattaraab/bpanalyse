<?php

declare(strict_types=1);

namespace App\Services\Presentation;

use App\Models\DocumentSlide;
use App\Services\Rag\Retriever;
use Illuminate\Support\Collection;

/**
 * Sélectionne 3 à 6 slides pertinentes pour une question (story 3.1).
 * Réutilise la recherche vectorielle sur les chunks : on remonte les chunks
 * les plus proches, on déduplique par slide, puis on ordonne par slide_index.
 */
final class SlideSelector
{
    public function __construct(private readonly Retriever $retriever) {}

    /**
     * @return Collection<int, DocumentSlide> slides ordonnées par slide_index
     */
    public function select(int $documentId, string $question, int $min = 3, int $max = 6): Collection
    {
        $chunks = $this->retriever->retrieve($documentId, $question, 24);

        $slideIds = [];
        foreach ($chunks as $chunk) {
            $slideId = $chunk->document_slide_id;
            if ($slideId !== null && ! in_array($slideId, $slideIds, true)) {
                $slideIds[] = $slideId;
            }
            if (count($slideIds) >= $max) {
                break;
            }
        }

        // Repli si trop peu de slides pertinentes : complète avec le début du document.
        if (count($slideIds) < $min) {
            $fallback = DocumentSlide::query()
                ->where('document_id', $documentId)
                ->orderBy('slide_index')
                ->pluck('id');

            foreach ($fallback as $id) {
                if (! in_array($id, $slideIds, true)) {
                    $slideIds[] = $id;
                }
                if (count($slideIds) >= $min) {
                    break;
                }
            }
        }

        return DocumentSlide::query()
            ->whereIn('id', $slideIds)
            ->orderBy('slide_index')
            ->get();
    }
}
