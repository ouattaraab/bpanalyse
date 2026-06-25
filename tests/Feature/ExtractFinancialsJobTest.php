<?php

declare(strict_types=1);

use App\Enums\ChunkType;
use App\Jobs\ExtractFinancialsJob;
use App\Models\Chunk;
use App\Models\Document;
use App\Services\AI\Contracts\LlmClient;
use App\Services\Ingestion\FinancialTableExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;

uses(RefreshDatabase::class);

it('extrait uniquement les tableaux financiers et trace la provenance', function () {
    // Garde-fou : le LLM ne doit JAMAIS être sollicité par l'extraction.
    app()->bind(LlmClient::class, function () {
        throw new RuntimeException('Le LLM ne doit pas être sollicité lors de l\'extraction des chiffres.');
    });

    $document = Document::factory()->create();
    $slide = $document->slides()->create(['slide_index' => 4, 'section' => 'Finances', 'raw_markdown' => '...']);

    // Tableau financier.
    Chunk::factory()->for($document)->create([
        'document_slide_id' => $slide->id,
        'type' => ChunkType::Table,
        'section' => 'Projections',
        'caption' => 'Projections financières',
        'content' => "| Poste | 2025 | 2026 |\n|---|---|---|\n| CA | 100 | 150 |",
    ]);
    // Tableau non financier (ignoré).
    Chunk::factory()->for($document)->create([
        'type' => ChunkType::Table,
        'content' => "| Persona | Besoin |\n|---|---|\n| DG | Vite |",
    ]);
    // Texte (ignoré).
    Chunk::factory()->for($document)->create([
        'type' => ChunkType::Text,
        'content' => 'Le marché est porteur.',
    ]);

    (new ExtractFinancialsJob($document->id))->handle(new FinancialTableExtractor);

    // Un seul tableau financier extrait.
    expect($document->financialTables()->count())->toBe(1)
        ->and($document->financialMetrics()->count())->toBe(2);

    $ca2026 = $document->financialMetrics()->where('label', 'CA')->where('period_year', 2026)->firstOrFail();
    expect($ca2026->value)->toBe(150.0)
        ->and($ca2026->source_ref['slide_index'])->toBe(4)
        ->and($ca2026->source_ref['section'])->toBe('Projections');
});

it('réindexe en remplaçant les tableaux financiers existants', function () {
    $document = Document::factory()->create();
    Chunk::factory()->for($document)->create([
        'type' => ChunkType::Table,
        'content' => "| Poste | 2025 |\n|---|---|\n| CA | 100 |",
    ]);

    $run = fn () => (new ExtractFinancialsJob($document->id))->handle(new FinancialTableExtractor);

    $run();
    expect($document->financialMetrics()->count())->toBe(1);

    $run();
    expect($document->financialTables()->count())->toBe(1)
        ->and($document->financialMetrics()->count())->toBe(1);
});
