<?php

declare(strict_types=1);

use App\Enums\ChunkType;
use App\Models\Chunk;
use App\Models\Document;
use App\Models\FinancialMetric;
use App\Models\FinancialTable;
use App\Services\AI\Contracts\EmbeddingClient;
use App\Services\AI\Contracts\LlmClient;
use App\Services\AI\LlmManager;
use App\Services\Session\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('déroule un débat tour-par-tour, sourcé et avec chiffres vérifiés', function () {
    $document = Document::factory()->create();
    $session = app(SessionService::class)->start($document);

    // Une slide + un chunk indexé (contexte / sources).
    $slide = $document->slides()->create(['slide_index' => 1, 'section' => 'Finances', 'raw_markdown' => 'CA 2026 = 150']);
    $chunk = Chunk::factory()->for($document)->create([
        'document_slide_id' => $slide->id,
        'type' => ChunkType::Table,
        'section' => 'Finances',
        'metadata' => ['slide_index' => 1],
    ]);
    $vector = array_fill(0, 1024, 0.0);
    $vector[0] = 1.0;
    DB::update('UPDATE chunks SET embedding = ?::vector WHERE id = ?', ['['.implode(',', $vector).']', $chunk->id]);

    // Chiffre réel de référence.
    $table = FinancialTable::create(['document_id' => $document->id, 'raw_markdown' => '| ... |']);
    FinancialMetric::create([
        'financial_table_id' => $table->id,
        'document_id' => $document->id,
        'label' => "Chiffre d'affaires",
        'period_label' => '2026',
        'period_year' => 2026,
        'value' => 150.0,
        'source_ref' => ['slide_index' => 1],
    ]);

    // Embedder (Retriever) : requête proche du chunk.
    $embedder = Mockery::mock(EmbeddingClient::class);
    $embedder->shouldReceive('embed')->andReturn([$vector]);
    app()->instance(EmbeddingClient::class, $embedder);

    // LLM (débat) : réplique contenant un chiffre réel (150) et un faux (999).
    $client = Mockery::mock(LlmClient::class);
    $client->shouldReceive('complete')->andReturn(
        "Le CA 2026 atteint 150, mais certains avancent 999 [slide 1 · Finances]."
    );
    $client->shouldReceive('provider')->andReturn('claude');
    $llm = Mockery::mock(LlmManager::class);
    $llm->shouldReceive('for')->with('debate')->andReturn($client);
    app()->instance(LlmManager::class, $llm);

    // 1 tour = 4 personas.
    $debateId = $this->postJson("/api/sessions/{$session->uuid}/debates", [
        'question' => 'Le BP est-il crédible ?',
        'max_rounds' => 1,
    ])->assertStatus(202)->json('data.id');

    $payload = $this->getJson("/api/debates/{$debateId}")->assertOk()->json('data');

    expect($payload['status'])->toBe('completed')
        ->and($payload['turns'])->toHaveCount(4)
        ->and(collect($payload['turns'])->pluck('persona')->all())->toBe(['dg', 'investor', 'cfo', 'sales']);

    // Chaque réplique cite des sources et fait vérifier ses chiffres.
    $firstTurn = $payload['turns'][0];
    expect($firstTurn['sources'])->not->toBeEmpty();

    $figures = collect($firstTurn['verified_figures']);
    expect($figures->firstWhere('value', 150.0)['status'])->toBe('verifie')
        ->and($figures->firstWhere('value', 999.0)['status'])->toBe('a_verifier');

    $this->assertDatabaseHas('audit_logs', [
        'explorer_session_id' => $session->id,
        'mode' => 'debate',
        'model_used' => 'claude',
    ]);
});
