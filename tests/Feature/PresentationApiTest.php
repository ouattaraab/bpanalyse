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

function presoSlide(Document $document, int $index): int
{
    $slide = $document->slides()->create([
        'slide_index' => $index,
        'raw_markdown' => "Slide {$index} : contenu pertinent.",
    ]);

    $chunk = Chunk::factory()->for($document)->create([
        'document_slide_id' => $slide->id,
        'type' => ChunkType::Text,
        'metadata' => ['slide_index' => $index],
    ]);

    $vector = array_fill(0, 1024, 0.0);
    $vector[$index] = 1.0;
    DB::update('UPDATE chunks SET embedding = ?::vector WHERE id = ?', ['['.implode(',', $vector).']', $chunk->id]);

    return $slide->id;
}

it('génère une présentation express sourcée et narrée', function () {
    $document = Document::factory()->create();
    $session = app(SessionService::class)->start($document);

    $slideIds = [];
    for ($i = 1; $i <= 5; $i++) {
        $slideIds[] = presoSlide($document, $i);
    }

    // Embedder : requête proche de la dimension 2.
    $query = array_fill(0, 1024, 0.0);
    $query[2] = 1.0;
    $embedder = Mockery::mock(EmbeddingClient::class);
    $embedder->shouldReceive('embed')->andReturn([$query]);
    app()->instance(EmbeddingClient::class, $embedder);

    // LLM : renvoie une narration pour chaque slide demandée.
    $client = Mockery::mock(LlmClient::class);
    $client->shouldReceive('complete')->andReturnUsing(function (array $messages) use ($slideIds): string {
        return json_encode(array_map(
            static fn (int $id): array => ['slide_id' => $id, 'narration' => "Narration de la slide {$id}."],
            $slideIds,
        ));
    });
    $client->shouldReceive('provider')->andReturn('groq');
    $llm = Mockery::mock(LlmManager::class);
    $llm->shouldReceive('for')->with('presentation')->andReturn($client);
    $llm->shouldReceive('resolveProviderKey')->with('presentation')->andReturn('groq');
    app()->instance(LlmManager::class, $llm);

    $response = $this->postJson("/api/sessions/{$session->uuid}/presentations", [
        'question' => 'Présente la stratégie du groupe.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'ready');

    $slides = $response->json('data.slides');
    expect(count($slides))->toBeGreaterThanOrEqual(3)->toBeLessThanOrEqual(6)
        ->and($slides[0])->toHaveKeys(['slide_id', 'slide_index', 'narration', 'duree', 'markdown'])
        ->and($slides[0]['narration'])->not->toBeEmpty()
        ->and($slides[0]['duree'])->toBeGreaterThanOrEqual(4)
        ->and($response->json('data.duration_total'))->toBeGreaterThan(0);

    // Audit tracé en mode présentation.
    $this->assertDatabaseHas('audit_logs', [
        'explorer_session_id' => $session->id,
        'mode' => 'presentation',
        'model_used' => 'groq',
    ]);
});
