<?php

declare(strict_types=1);

use App\Enums\ChunkType;
use App\Services\Ingestion\SemanticChunker;

beforeEach(function () {
    $this->chunker = new SemanticChunker();
});

it('transforme un tableau isolé en exactement un chunk de type table', function () {
    $markdown = "| Poste | 2025 | 2026 |\n|---|---|---|\n| CA | 100 | 150 |";

    $chunks = $this->chunker->chunkMarkdown($markdown);

    expect($chunks)->toHaveCount(1)
        ->and($chunks[0]->type)->toBe(ChunkType::Table)
        // Tableau intact : en-tête, séparateur et données préservés.
        ->and($chunks[0]->content)->toContain('| Poste | 2025 | 2026 |')
        ->and($chunks[0]->content)->toContain('| CA | 100 | 150 |');
});

it('attache la légende précédente au chunk du tableau sans la dupliquer en texte', function () {
    $markdown = <<<'MD'
    ## Projections financières

    Voici le tableau clé des projections.

    | Poste | 2025 |
    |---|---|
    | CA | 100 |
    MD;

    $chunks = $this->chunker->chunkMarkdown($markdown);

    // Un seul chunk : le tableau, avec sa légende. La légende n'est pas un chunk texte séparé.
    expect($chunks)->toHaveCount(1)
        ->and($chunks[0]->type)->toBe(ChunkType::Table)
        ->and($chunks[0]->section)->toBe('Projections financières')
        ->and($chunks[0]->caption)->toBe('Voici le tableau clé des projections.')
        ->and($chunks[0]->content)->toContain('Voici le tableau clé des projections.')
        ->and($chunks[0]->content)->toContain('| CA | 100 |');
});

it('découpe le texte en un chunk par section', function () {
    $markdown = <<<'MD'
    ## Marché

    Le marché croît de 10% par an.

    ## Stratégie

    Notre plan repose sur trois axes.
    MD;

    $chunks = $this->chunker->chunkMarkdown($markdown);

    expect($chunks)->toHaveCount(2)
        ->and($chunks[0]->type)->toBe(ChunkType::Text)
        ->and($chunks[0]->section)->toBe('Marché')
        ->and($chunks[0]->content)->toBe('Le marché croît de 10% par an.')
        ->and($chunks[1]->section)->toBe('Stratégie')
        ->and($chunks[1]->content)->toBe('Notre plan repose sur trois axes.');
});

it('sépare un tableau du texte qui le suit', function () {
    $markdown = <<<'MD'
    | Poste | 2025 |
    |---|---|
    | CA | 100 |

    Le chiffre d'affaires progresse fortement.
    MD;

    $chunks = $this->chunker->chunkMarkdown($markdown);

    $tables = array_values(array_filter($chunks, fn ($c) => $c->type === ChunkType::Table));
    $texts = array_values(array_filter($chunks, fn ($c) => $c->type === ChunkType::Text));

    expect($tables)->toHaveCount(1)
        ->and($tables[0]->content)->toContain('| CA | 100 |')
        ->and($texts)->toHaveCount(1)
        ->and($texts[0]->content)->toContain("Le chiffre d'affaires progresse fortement.");
});

it('propage la section par défaut et ignore le markdown vide', function () {
    expect($this->chunker->chunkMarkdown('   '))->toBe([]);

    $chunks = $this->chunker->chunkMarkdown('Un paragraphe simple.', 'Introduction');
    expect($chunks)->toHaveCount(1)
        ->and($chunks[0]->section)->toBe('Introduction');
});
