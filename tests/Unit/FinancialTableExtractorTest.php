<?php

declare(strict_types=1);

use App\Services\Ingestion\FinancialTableExtractor;

beforeEach(function () {
    $this->extractor = new FinancialTableExtractor;
});

it('extrait les mesures d\'un tableau financier avec valeurs exactes', function () {
    $markdown = <<<'MD'
    | Poste | 2024 | 2025 | 2026 |
    |---|---|---|---|
    | Chiffre d'affaires | 100 | 150 | 225 |
    | Marge | 20% | 22% | 25% |
    MD;

    $metrics = $this->extractor->extractMetrics($markdown);

    // 2 postes × 3 périodes = 6 mesures.
    expect($metrics)->toHaveCount(6);

    $ca2026 = collect($metrics)->first(
        fn ($m) => $m->label === "Chiffre d'affaires" && $m->periodYear === 2026
    );
    expect($ca2026->value)->toBe(225.0)
        ->and($ca2026->periodLabel)->toBe('2026')
        ->and($ca2026->unit)->toBeNull();

    $marge2025 = collect($metrics)->first(
        fn ($m) => $m->label === 'Marge' && $m->periodYear === 2025
    );
    expect($marge2025->value)->toBe(22.0)
        ->and($marge2025->unit)->toBe('%');
});

it('ignore les cellules non numériques (pas de mesure inventée)', function () {
    $markdown = <<<'MD'
    | Poste | 2024 | 2025 |
    |---|---|---|
    | EBITDA | N/A | 30 |
    MD;

    $metrics = $this->extractor->extractMetrics($markdown);

    // Seule la cellule 30 produit une mesure ; "N/A" est ignorée.
    expect($metrics)->toHaveCount(1)
        ->and($metrics[0]->value)->toBe(30.0)
        ->and($metrics[0]->periodYear)->toBe(2025);
});

it('ne produit aucune mesure pour un tableau non financier', function () {
    $markdown = <<<'MD'
    | Persona | Besoin |
    |---|---|
    | DG | Comprendre vite |
    | DAF | Vérifier les projections |
    MD;

    expect($this->extractor->extractMetrics($markdown))->toBe([]);
});
