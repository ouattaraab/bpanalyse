<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\FinancialMetric;
use App\Models\FinancialTable;
use App\Services\Document\FinancialQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedMetric(FinancialTable $table, string $label, int $year, float $value, ?string $unit = null): void
{
    FinancialMetric::create([
        'financial_table_id' => $table->id,
        'document_id' => $table->document_id,
        'label' => $label,
        'period_label' => (string) $year,
        'period_year' => $year,
        'value' => $value,
        'unit' => $unit,
        'source_ref' => ['slide_index' => 3],
    ]);
}

beforeEach(function () {
    $this->document = Document::factory()->create();
    $this->table = FinancialTable::create([
        'document_id' => $this->document->id,
        'raw_markdown' => '| ... |',
    ]);

    seedMetric($this->table, "Chiffre d'affaires", 2024, 100.0);
    seedMetric($this->table, "Chiffre d'affaires", 2026, 150.0);
    seedMetric($this->table, 'Marge', 2024, 20.0, '%');

    $this->service = new FinancialQueryService();
});

it('liste les postes financiers disponibles', function () {
    $rows = $this->service->query($this->document->id, 'list_metrics');

    $ca = collect($rows)->firstWhere('label', "Chiffre d'affaires");
    expect($ca['first_year'])->toBe(2024)
        ->and($ca['last_year'])->toBe(2026)
        ->and($ca['points'])->toBe(2);
});

it('retourne la valeur exacte d\'un poste pour une année', function () {
    $rows = $this->service->query($this->document->id, 'get_metric', [
        'label' => "Chiffre d'affaires",
        'period_year' => 2026,
    ]);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['value'])->toBe(150.0)
        ->and($rows[0]['source_ref']['slide_index'])->toBe(3);
});

it('compare deux périodes avec une croissance calculée de façon déterministe', function () {
    $rows = $this->service->query($this->document->id, 'compare_periods', [
        'label' => "Chiffre d'affaires",
        'from_year' => 2024,
        'to_year' => 2026,
    ]);

    expect($rows[0]['from_value'])->toBe(100.0)
        ->and($rows[0]['to_value'])->toBe(150.0)
        ->and($rows[0]['delta'])->toBe(50.0)
        ->and($rows[0]['growth_pct'])->toBe(50.0);
});

it('rejette une requête non whitelistée', function () {
    $this->service->query($this->document->id, 'DROP TABLE financial_metrics');
})->throws(InvalidArgumentException::class);

it('exige le paramètre label pour get_metric', function () {
    $this->service->query($this->document->id, 'get_metric', []);
})->throws(InvalidArgumentException::class);

it('expose des capabilities pour le function calling', function () {
    $names = collect((new FinancialQueryService())->capabilities())->pluck('name');

    expect($names)->toContain('list_metrics', 'get_metric', 'compare_periods');
});
