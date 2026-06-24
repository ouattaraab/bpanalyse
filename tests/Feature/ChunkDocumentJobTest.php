<?php

declare(strict_types=1);

use App\Enums\ChunkType;
use App\Jobs\ChunkDocumentJob;
use App\Models\Document;
use App\Services\Ingestion\SemanticChunker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function runChunkJob(Document $document): void
{
    (new ChunkDocumentJob($document->id))->handle(new SemanticChunker());
}

it('crée les chunks des slides en respectant 1 tableau = 1 chunk', function () {
    $document = Document::factory()->create();

    $document->slides()->create([
        'slide_index' => 1,
        'section' => 'Finances',
        'raw_markdown' => "Le tableau des projections.\n\n| Poste | 2025 |\n|---|---|\n| CA | 100 |",
    ]);
    $document->slides()->create([
        'slide_index' => 2,
        'section' => 'Marché',
        'raw_markdown' => 'Le marché est porteur.',
    ]);

    runChunkJob($document);

    expect($document->chunks()->count())->toBe(2);

    $tableChunks = $document->chunks()->where('type', ChunkType::Table->value)->get();
    expect($tableChunks)->toHaveCount(1);

    $table = $tableChunks->first();
    expect($table->content)->toContain('| CA | 100 |')
        ->and($table->caption)->toBe('Le tableau des projections.')
        ->and($table->metadata['slide_index'])->toBe(1)
        ->and($table->document_slide_id)->toBe($document->slides()->where('slide_index', 1)->first()->id);

    $textChunk = $document->chunks()->where('type', ChunkType::Text->value)->first();
    expect($textChunk->content)->toBe('Le marché est porteur.')
        ->and($textChunk->section)->toBe('Marché');
});

it('réindexe en remplaçant les chunks existants', function () {
    $document = Document::factory()->create();
    $document->slides()->create(['slide_index' => 1, 'raw_markdown' => 'Texte initial.']);

    runChunkJob($document);
    expect($document->chunks()->count())->toBe(1);

    runChunkJob($document);
    expect($document->chunks()->count())->toBe(1);
});
