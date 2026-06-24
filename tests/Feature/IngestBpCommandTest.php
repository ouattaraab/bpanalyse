<?php

declare(strict_types=1);

use App\Enums\DocumentStatus;
use App\Jobs\ChunkDocumentJob;
use App\Jobs\EmbedChunksJob;
use App\Jobs\ExtractFinancialsJob;
use App\Jobs\ParseDocumentJob;
use App\Models\Document;
use App\Models\Tenant;
use App\Services\AI\Contracts\EmbeddingClient;
use App\Services\Ingestion\Contracts\DocumentParser;
use App\Services\Ingestion\Data\ParsedDocument;
use App\Services\Ingestion\Data\ParsedSlide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function tempBpFile(): string
{
    $path = tempnam(sys_get_temp_dir(), 'bp').'.pdf';
    file_put_contents($path, "%PDF-1.4\nBP de test\n");

    return $path;
}

it('crée le document et chaîne les jobs du pipeline', function () {
    Bus::fake();
    Storage::fake('documents');
    $tenant = Tenant::factory()->create();
    $file = tempBpFile();

    $this->artisan('bp:ingest', ['file' => $file, '--tenant' => $tenant->id])
        ->assertSuccessful();

    $document = Document::firstOrFail();
    expect($document->status)->toBe(DocumentStatus::Uploaded)
        ->and($document->tenant_id)->toBe($tenant->id);

    Bus::assertChained([
        ParseDocumentJob::class,
        ChunkDocumentJob::class,
        EmbedChunksJob::class,
        ExtractFinancialsJob::class,
    ]);

    @unlink($file);
});

it('échoue proprement si le fichier est introuvable', function () {
    $this->artisan('bp:ingest', ['file' => '/chemin/inexistant.pdf'])
        ->assertFailed();
});

it('déroule le pipeline complet de uploaded à indexed (parser et embedder mockés)', function () {
    Storage::fake('documents');

    $parsed = new ParsedDocument(
        markdown: 'BP',
        slides: [new ParsedSlide(
            index: 1,
            markdown: "Projections\n\n| Poste | 2025 |\n|---|---|\n| CA | 100 |",
            title: 'BP',
            section: 'Finances',
        )],
        pageCount: 1,
        title: 'BP',
    );

    $parser = Mockery::mock(DocumentParser::class);
    $parser->shouldReceive('parse')->once()->andReturn($parsed);
    app()->instance(DocumentParser::class, $parser);

    $embedder = Mockery::mock(EmbeddingClient::class);
    $embedder->shouldReceive('embed')->andReturnUsing(
        fn (array $texts): array => array_map(static fn (): array => array_fill(0, 1024, 0.1), $texts)
    );
    app()->instance(EmbeddingClient::class, $embedder);

    $tenant = Tenant::factory()->create();
    $file = tempBpFile();

    $this->artisan('bp:ingest', ['file' => $file, '--tenant' => $tenant->id])
        ->assertSuccessful();

    $document = Document::firstOrFail();
    expect($document->status)->toBe(DocumentStatus::Indexed)
        ->and($document->slides()->count())->toBe(1)
        ->and($document->chunks()->count())->toBeGreaterThan(0)
        ->and($document->financialMetrics()->where('label', 'CA')->where('period_year', 2025)->value('value'))->toBe(100.0);

    @unlink($file);
});
