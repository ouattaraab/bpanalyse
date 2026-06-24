<?php

declare(strict_types=1);

use App\Services\Ingestion\Support\FinancialValueParser;

it('parse les valeurs et unités de façon déterministe', function (string $cell, ?float $value, ?string $unit) {
    $parsed = FinancialValueParser::parse($cell);

    if ($value === null) {
        expect($parsed)->toBeNull();

        return;
    }

    expect($parsed)->not->toBeNull()
        ->and($parsed['value'])->toBe($value)
        ->and($parsed['unit'])->toBe($unit);
})->with([
    'entier simple' => ['100', 100.0, null],
    'pourcentage' => ['15%', 15.0, '%'],
    'décimale FR' => ['10,5', 10.5, null],
    'milliers FR (espace)' => ['1 200', 1200.0, null],
    'décimale FR + milliers' => ['1 200,50', 1200.5, null],
    'milliers EN' => ['1,200.50', 1200.5, null],
    'suffixe échelle (non multiplié)' => ['150 M€', 150.0, 'M€'],
    'négatif' => ['-50', -50.0, null],
    'parenthèses comptables' => ['(100)', -100.0, null],
    'non numérique' => ['N/A', null, null],
    'tiret' => ['-', null, null],
    'vide' => ['', null, null],
]);

it('extrait l\'année d\'un libellé de période', function () {
    expect(FinancialValueParser::yearFrom('2025'))->toBe(2025)
        ->and(FinancialValueParser::yearFrom('Exercice 2026'))->toBe(2026)
        ->and(FinancialValueParser::yearFrom('2027e'))->toBe(2027)
        ->and(FinancialValueParser::yearFrom('Poste'))->toBeNull();
});
