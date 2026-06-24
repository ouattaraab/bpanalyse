<?php

declare(strict_types=1);

use App\Services\Ingestion\DoclingParser;
use App\Services\Ingestion\Exceptions\DocumentParsingException;

/** Chemin du python3 système, ou skip si absent. */
function python3OrSkip(): string
{
    $bin = trim((string) shell_exec('command -v python3'));
    if ($bin === '') {
        test()->markTestSkipped('python3 absent de cet environnement.');
    }

    return $bin;
}

it('exécute le process et mappe le JSON en ParsedDocument (tableau préservé)', function () {
    $parser = new DoclingParser(python3OrSkip(), base_path('tests/Fixtures/docling_stub_ok.py'), 30);

    $doc = $parser->parse(__FILE__); // n'importe quel fichier existant

    expect($doc->pageCount)->toBe(2)
        ->and($doc->title)->toBe('BP de test')
        ->and($doc->slides)->toHaveCount(2)
        ->and($doc->slides[0]->section)->toBe('Finances')
        // Le tableau Markdown est intact (pipes + ligne de données).
        ->and($doc->slides[0]->markdown)->toContain('| CA | 100 |')
        ->and($doc->slides[0]->markdown)->toContain('| Poste | 2025 |');
});

it('lève DocumentParsingException quand le process échoue', function () {
    $parser = new DoclingParser(python3OrSkip(), base_path('tests/Fixtures/docling_stub_fail.py'), 30);

    expect(fn () => $parser->parse(__FILE__))->toThrow(DocumentParsingException::class);
});

it('lève DocumentParsingException si le fichier est introuvable', function () {
    $parser = new DoclingParser('python3', 'parse.py', 5);

    expect(fn () => $parser->parse('/chemin/inexistant.pdf'))->toThrow(DocumentParsingException::class);
});
