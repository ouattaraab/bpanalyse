<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Models\Chunk;
use Illuminate\Support\Collection;

/**
 * Met en forme les sources citées à partir des chunks retrouvés
 * (pour la réponse à l'utilisateur ET la trace d'audit).
 */
final class SourceFormatter
{
    /**
     * @param  Collection<int, Chunk>  $chunks
     * @return array<int, array<string, mixed>>
     */
    public function format(Collection $chunks): array
    {
        return $chunks->map(static fn (Chunk $chunk): array => [
            'chunk_id' => $chunk->id,
            'slide_index' => $chunk->metadata['slide_index'] ?? null,
            'section' => $chunk->section,
            'type' => $chunk->type->value,
            'caption' => $chunk->caption,
        ])->values()->all();
    }

    /**
     * Construit le contexte texte injecté au LLM, chaque extrait étant étiqueté
     * par sa source pour permettre la citation.
     *
     * @param  Collection<int, Chunk>  $chunks
     */
    public function buildContext(Collection $chunks): string
    {
        return $chunks->map(static function (Chunk $chunk): string {
            $slide = $chunk->metadata['slide_index'] ?? '?';
            $section = $chunk->section ?? 'n/a';

            return "[slide {$slide} · {$section}]\n{$chunk->content}";
        })->implode("\n\n---\n\n");
    }
}
