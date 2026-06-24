<?php

declare(strict_types=1);

use App\Models\Chunk;
use App\Models\Document;
use App\Services\AI\Contracts\EmbeddingClient;
use App\Services\Rag\Retriever;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/** Crée un chunk avec un embedding pgvector donné. */
function chunkWithEmbedding(Document $document, string $content, array $vector): Chunk
{
    $chunk = Chunk::factory()->for($document)->create([
        'content' => $content,
        'metadata' => ['slide_index' => 2],
    ]);

    DB::update(
        'UPDATE chunks SET embedding = ?::vector WHERE id = ?',
        ['['.implode(',', $vector).']', $chunk->id],
    );

    return $chunk;
}

it('retourne les chunks les plus proches en cosine, triés par distance', function () {
    $document = Document::factory()->create();

    $near = array_fill(0, 1024, 0.0);
    $near[0] = 1.0;
    $far = array_fill(0, 1024, 0.0);
    $far[1] = 1.0;

    $chunkNear = chunkWithEmbedding($document, 'Très pertinent', $near);
    $chunkFar = chunkWithEmbedding($document, 'Hors sujet', $far);

    // La requête est embarquée comme le vecteur "near".
    $embedder = Mockery::mock(EmbeddingClient::class);
    $embedder->shouldReceive('embed')->once()->andReturn([$near]);

    $results = (new Retriever($embedder))->retrieve($document->id, 'question', 2);

    expect($results)->toHaveCount(2)
        ->and($results->first()->id)->toBe($chunkNear->id)
        ->and($results->last()->id)->toBe($chunkFar->id);
});

it('ne retourne rien si la requête ne produit pas de vecteur', function () {
    $document = Document::factory()->create();

    $embedder = Mockery::mock(EmbeddingClient::class);
    $embedder->shouldReceive('embed')->andReturn([]);

    expect((new Retriever($embedder))->retrieve($document->id, 'question'))->toHaveCount(0);
});
