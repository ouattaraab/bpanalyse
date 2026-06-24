<?php

declare(strict_types=1);

use App\Enums\DocumentStatus;
use App\Jobs\ParseDocumentJob;
use App\Models\Document;
use App\Services\Ingestion\Contracts\DocumentParser;
use App\Services\Ingestion\Data\ParsedDocument;
use App\Services\Ingestion\Data\ParsedSlide;
use App\Services\Ingestion\Exceptions\DocumentParsingException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('parse le document, persiste les slides et passe en parsed (tableau intact)', function () {
    $document = Document::factory()->create([
        'status' => DocumentStatus::Uploaded,
        'page_count' => null,
    ]);

    $table = "| Poste | 2025 |\n|---|---|\n| CA | 100 |";
    $parsed = new ParsedDocument(
        markdown: "# BP\n\n".$table,
        slides: [
            new ParsedSlide(index: 1, markdown: "Introduction\n\n".$table, title: 'BP', section: 'Finances'),
            new ParsedSlide(index: 2, markdown: 'Synthèse.', title: null, section: 'Conclusion'),
        ],
        pageCount: 2,
        title: 'BP',
    );

    $parser = Mockery::mock(DocumentParser::class);
    $parser->shouldReceive('parse')->once()->andReturn($parsed);

    (new ParseDocumentJob($document->id))->handle($parser);

    $document->refresh();
    expect($document->status)->toBe(DocumentStatus::Parsed)
        ->and($document->page_count)->toBe(2)
        ->and($document->slides()->count())->toBe(2);

    $slide1 = $document->slides()->where('slide_index', 1)->firstOrFail();
    expect($slide1->section)->toBe('Finances')
        // Tableau non cassé au passage parsing → base.
        ->and($slide1->raw_markdown)->toContain('| Poste | 2025 |')
        ->and($slide1->raw_markdown)->toContain('| CA | 100 |');
});

it('réindexe proprement en remplaçant les slides existantes', function () {
    $document = Document::factory()->create(['status' => DocumentStatus::Uploaded]);
    $document->slides()->create(['slide_index' => 1, 'raw_markdown' => 'ancienne']);

    $parsed = new ParsedDocument(
        markdown: 'nouveau',
        slides: [new ParsedSlide(index: 1, markdown: 'nouvelle slide')],
        pageCount: 1,
    );

    $parser = Mockery::mock(DocumentParser::class);
    $parser->shouldReceive('parse')->once()->andReturn($parsed);

    (new ParseDocumentJob($document->id))->handle($parser);

    expect($document->slides()->count())->toBe(1)
        ->and($document->slides()->first()->raw_markdown)->toBe('nouvelle slide');
});

it('passe le document en failed quand le parsing échoue', function () {
    $document = Document::factory()->create(['status' => DocumentStatus::Uploaded]);

    $parser = Mockery::mock(DocumentParser::class);
    $parser->shouldReceive('parse')->andThrow(new DocumentParsingException('boom'));

    $job = new ParseDocumentJob($document->id);

    expect(fn () => $job->handle($parser))->toThrow(DocumentParsingException::class);

    $job->failed(new DocumentParsingException('boom'));

    expect($document->refresh()->status)->toBe(DocumentStatus::Failed);
});
