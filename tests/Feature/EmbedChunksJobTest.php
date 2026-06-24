<?php

declare(strict_types=1);

use App\Enums\DocumentStatus;
use App\Jobs\EmbedChunksJob;
use App\Models\Chunk;
use App\Models\Document;
use App\Services\AI\Contracts\EmbeddingClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('écrit les embeddings des chunks et passe le document en indexed', function () {
    $document = Document::factory()->create(['status' => DocumentStatus::Parsed]);
    Chunk::factory()->count(3)->create(['document_id' => $document->id]);

    $embedder = Mockery::mock(EmbeddingClient::class);
    $embedder->shouldReceive('embed')
        ->once()
        ->andReturnUsing(fn (array $texts): array => array_map(
            static fn (): array => array_fill(0, 1024, 0.1),
            $texts,
        ));

    (new EmbedChunksJob($document->id))->handle($embedder);

    expect($document->refresh()->status)->toBe(DocumentStatus::Indexed);

    $withEmbedding = DB::table('chunks')
        ->where('document_id', $document->id)
        ->whereNotNull('embedding')
        ->count();
    expect($withEmbedding)->toBe(3);

    // Dimension réellement stockée dans pgvector.
    $dims = DB::scalar(
        'SELECT vector_dims(embedding) FROM chunks WHERE document_id = ? LIMIT 1',
        [$document->id],
    );
    expect((int) $dims)->toBe(1024);
});

it('passe le document en failed si le calcul échoue', function () {
    $document = Document::factory()->create(['status' => DocumentStatus::Parsed]);
    Chunk::factory()->create(['document_id' => $document->id]);

    $job = new EmbedChunksJob($document->id);
    $job->failed(new RuntimeException('boom'));

    expect($document->refresh()->status)->toBe(DocumentStatus::Failed);
});
