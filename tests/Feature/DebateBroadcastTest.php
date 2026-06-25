<?php

declare(strict_types=1);

use App\Enums\ChunkType;
use App\Events\DebateCompleted;
use App\Events\DebateTurnCreated;
use App\Models\Chunk;
use App\Models\Debate;
use App\Models\Document;
use App\Services\AI\Contracts\EmbeddingClient;
use App\Services\AI\Contracts\LlmClient;
use App\Services\AI\LlmManager;
use App\Services\Debate\DebateOrchestrator;
use App\Services\Session\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('diffuse chaque réplique et la fin du débat en temps réel', function () {
    Event::fake([DebateTurnCreated::class, DebateCompleted::class]);

    $document = Document::factory()->create();
    $session = app(SessionService::class)->start($document);

    $slide = $document->slides()->create(['slide_index' => 1, 'section' => 'Finances', 'raw_markdown' => '...']);
    $chunk = Chunk::factory()->for($document)->create([
        'document_slide_id' => $slide->id,
        'type' => ChunkType::Table,
        'metadata' => ['slide_index' => 1],
    ]);
    $vector = array_fill(0, 1024, 0.0);
    $vector[0] = 1.0;
    DB::update('UPDATE chunks SET embedding = ?::vector WHERE id = ?', ['['.implode(',', $vector).']', $chunk->id]);

    $embedder = Mockery::mock(EmbeddingClient::class);
    $embedder->shouldReceive('embed')->andReturn([$vector]);
    app()->instance(EmbeddingClient::class, $embedder);

    $client = Mockery::mock(LlmClient::class);
    $client->shouldReceive('complete')->andReturn('Position du persona [slide 1].');
    $client->shouldReceive('provider')->andReturn('claude');
    $llm = Mockery::mock(LlmManager::class);
    $llm->shouldReceive('for')->with('debate')->andReturn($client);
    app()->instance(LlmManager::class, $llm);

    $debate = Debate::create([
        'explorer_session_id' => $session->id,
        'document_id' => $document->id,
        'question' => 'Le BP tient-il ?',
        'status' => 'pending',
        'stop_condition' => ['max_rounds' => 1],
    ]);

    app(DebateOrchestrator::class)->run($debate);

    // 1 tour = 4 personas → 4 répliques diffusées + 1 fin de débat.
    Event::assertDispatched(DebateTurnCreated::class, 4);
    Event::assertDispatched(DebateCompleted::class, 1);

    expect($debate->refresh()->status)->toBe('completed');
});
