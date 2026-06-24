<?php

declare(strict_types=1);

use App\Enums\ChunkType;
use App\Models\Chunk;
use App\Models\Document;
use App\Services\AI\Contracts\EmbeddingClient;
use App\Services\Presentation\SlideSelector;
use App\Services\Rag\Retriever;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/** Crée une slide avec un chunk indexé (embedding = vecteur unitaire e_position). */
function slideWithIndexedChunk(Document $document, int $index, int $hot): void
{
    $slide = $document->slides()->create([
        'slide_index' => $index,
        'raw_markdown' => "Contenu de la slide {$index}",
    ]);

    $chunk = Chunk::factory()->for($document)->create([
        'document_slide_id' => $slide->id,
        'type' => ChunkType::Text,
        'metadata' => ['slide_index' => $index],
    ]);

    $vector = array_fill(0, 1024, 0.0);
    $vector[$hot] = 1.0;
    DB::update('UPDATE chunks SET embedding = ?::vector WHERE id = ?', ['['.implode(',', $vector).']', $chunk->id]);
}

it('sélectionne entre 3 et 6 slides distinctes, ordonnées par index', function () {
    $document = Document::factory()->create();
    for ($i = 1; $i <= 8; $i++) {
        slideWithIndexedChunk($document, $i, $i); // chacune sur une dimension distincte
    }

    // La requête est proche de la slide dont le chunk est sur la dimension 3.
    $query = array_fill(0, 1024, 0.0);
    $query[3] = 1.0;
    $embedder = Mockery::mock(EmbeddingClient::class);
    $embedder->shouldReceive('embed')->andReturn([$query]);

    $selector = new SlideSelector(new Retriever($embedder));
    $slides = $selector->select($document->id, 'Quelle est la stratégie ?');

    $indices = $slides->pluck('slide_index')->all();

    expect($slides->count())->toBeGreaterThanOrEqual(3)->toBeLessThanOrEqual(6)
        ->and($indices)->toBe(array_values($indices)) // pas de clés trouées
        ->and($indices)->toBe(collect($indices)->sort()->values()->all()) // trié croissant
        ->and(count($indices))->toBe(count(array_unique($indices))) // distinctes
        ->and($indices)->toContain(3); // la plus proche est incluse
});

it('complète avec le début du document si peu de slides pertinentes', function () {
    $document = Document::factory()->create();
    // 4 slides mais une seule indexée → repli pour atteindre le minimum.
    $document->slides()->create(['slide_index' => 1, 'raw_markdown' => 'A']);
    $document->slides()->create(['slide_index' => 2, 'raw_markdown' => 'B']);
    $document->slides()->create(['slide_index' => 3, 'raw_markdown' => 'C']);
    slideWithIndexedChunk($document, 4, 0);

    $query = array_fill(0, 1024, 0.0);
    $query[0] = 1.0;
    $embedder = Mockery::mock(EmbeddingClient::class);
    $embedder->shouldReceive('embed')->andReturn([$query]);

    $slides = (new SlideSelector(new Retriever($embedder)))->select($document->id, 'question');

    expect($slides->count())->toBeGreaterThanOrEqual(3);
});
