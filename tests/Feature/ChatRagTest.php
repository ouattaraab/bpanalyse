<?php

declare(strict_types=1);

use App\Enums\ChunkType;
use App\Models\Chunk;
use App\Models\Document;
use App\Services\AI\Contracts\EmbeddingClient;
use App\Services\AI\Contracts\LlmClient;
use App\Services\AI\LlmManager;
use App\Services\Session\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('démarre une session via l\'API', function () {
    $document = Document::factory()->create();

    $this->postJson("/api/documents/{$document->id}/sessions")
        ->assertCreated()
        ->assertJsonPath('data.document_id', $document->id)
        ->assertJsonStructure(['data' => ['uuid', 'status', 'expires_at']]);
});

it('répond en citant ses sources, persiste l\'interaction et trace l\'audit', function () {
    $document = Document::factory()->create();
    $session = app(SessionService::class)->start($document);

    // Chunk de tableau contenant un chiffre connu + embedding.
    $chunk = Chunk::factory()->for($document)->create([
        'type' => ChunkType::Table,
        'section' => 'Projections',
        'content' => "| Poste | 2026 |\n|---|---|\n| CA | 150 |",
        'metadata' => ['slide_index' => 4],
    ]);
    $vector = array_fill(0, 1024, 0.0);
    $vector[0] = 1.0;
    DB::update('UPDATE chunks SET embedding = ?::vector WHERE id = ?', ['['.implode(',', $vector).']', $chunk->id]);

    // Embedder : la requête est embarquée sur le même vecteur que le chunk.
    $embedder = Mockery::mock(EmbeddingClient::class);
    $embedder->shouldReceive('embed')->andReturn([$vector]);
    app()->instance(EmbeddingClient::class, $embedder);

    // LLM mocké : capture le prompt, renvoie une réponse sourcée.
    $captured = null;
    $client = Mockery::mock(LlmClient::class);
    $client->shouldReceive('complete')->once()->andReturnUsing(function (array $messages) use (&$captured): string {
        $captured = $messages;

        return "Le chiffre d'affaires 2026 atteint 150 [slide 4 · Projections].";
    });
    $client->shouldReceive('provider')->andReturn('groq');

    $llm = Mockery::mock(LlmManager::class);
    $llm->shouldReceive('for')->with('chat')->andReturn($client);
    app()->instance(LlmManager::class, $llm);

    $response = $this->postJson("/api/sessions/{$session->uuid}/chat", [
        'question' => 'Quel est le chiffre d\'affaires 2026 ?',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.answer', "Le chiffre d'affaires 2026 atteint 150 [slide 4 · Projections].")
        ->assertJsonPath('data.sources.0.slide_index', 4);

    $this->assertDatabaseHas('interactions', [
        'explorer_session_id' => $session->id,
        'mode' => 'chat',
    ]);
    $this->assertDatabaseHas('audit_logs', [
        'explorer_session_id' => $session->id,
        'mode' => 'chat',
        'model_used' => 'groq',
    ]);

    // Le chiffre vient du CONTEXTE (fourni au LLM), il n'est pas inventé.
    $userMessage = collect($captured)->firstWhere('role', 'user')['content'];
    expect($userMessage)->toContain('| CA | 150 |');
});

it('valide la question', function () {
    $document = Document::factory()->create();
    $session = app(SessionService::class)->start($document);

    $this->postJson("/api/sessions/{$session->uuid}/chat", ['question' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('question');
});
