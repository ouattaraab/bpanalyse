<?php

declare(strict_types=1);

use App\Models\Document;
use App\Services\AI\Contracts\LlmClient;
use App\Services\AI\LlmManager;
use App\Services\Presentation\NarrationGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function narrationLlm(string $json): LlmManager
{
    $client = Mockery::mock(LlmClient::class);
    $client->shouldReceive('complete')->andReturn($json);
    $client->shouldReceive('provider')->andReturn('groq');

    $llm = Mockery::mock(LlmManager::class);
    $llm->shouldReceive('for')->with('presentation')->andReturn($client);

    return $llm;
}

it('génère un script JSON par slide avec une durée déterministe', function () {
    $document = Document::factory()->create();
    $s1 = $document->slides()->create(['slide_index' => 1, 'raw_markdown' => 'Marché en croissance']);
    $s2 = $document->slides()->create(['slide_index' => 2, 'raw_markdown' => 'Projections']);

    $json = json_encode([
        ['slide_id' => $s1->id, 'narration' => 'Le marché connaît une forte croissance cette année.'],
        ['slide_id' => $s2->id, 'narration' => 'Les projections confirment la trajectoire ambitieuse du groupe.'],
    ]);

    $script = (new NarrationGenerator(narrationLlm($json)))->generate('Quelle stratégie ?', collect([$s1, $s2]));

    expect($script)->toHaveCount(2)
        ->and($script[0]['slide_id'])->toBe($s1->id)
        ->and($script[0]['narration'])->toContain('marché')
        ->and($script[0]['duree'])->toBeGreaterThanOrEqual(4)
        ->and($script[1]['slide_id'])->toBe($s2->id);
});

it('applique une narration de repli si le LLM omet une slide', function () {
    $document = Document::factory()->create();
    $s1 = $document->slides()->create(['slide_index' => 1, 'raw_markdown' => 'Première slide']);
    $s2 = $document->slides()->create(['slide_index' => 2, 'raw_markdown' => 'Contenu de secours pour la slide deux']);

    // Le LLM ne renvoie que la slide 1 (et avec du texte autour du JSON).
    $json = "Voici le script :\n".json_encode([
        ['slide_id' => $s1->id, 'narration' => 'Narration de la première slide.'],
    ]);

    $script = (new NarrationGenerator(narrationLlm($json)))->generate('question', collect([$s1, $s2]));

    expect($script)->toHaveCount(2)
        ->and($script[1]['slide_id'])->toBe($s2->id)
        ->and($script[1]['narration'])->not->toBeEmpty();
});
