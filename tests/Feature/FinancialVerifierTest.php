<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\FinancialMetric;
use App\Models\FinancialTable;
use App\Services\Debate\FinancialVerifier;
use App\Services\Document\Contracts\StructuredDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function vMetric(FinancialTable $table, string $label, int $year, float $value, ?string $unit = null): void
{
    FinancialMetric::create([
        'financial_table_id' => $table->id,
        'document_id' => $table->document_id,
        'label' => $label,
        'period_label' => (string) $year,
        'period_year' => $year,
        'value' => $value,
        'unit' => $unit,
        'source_ref' => ['slide_index' => 1],
    ]);
}

beforeEach(function () {
    $this->document = Document::factory()->create();
    $table = FinancialTable::create(['document_id' => $this->document->id, 'raw_markdown' => '| ... |']);
    vMetric($table, "Chiffre d'affaires", 2026, 150.0);
    vMetric($table, 'Marge', 2024, 22.0, '%');

    $this->verifier = new FinancialVerifier(app(StructuredDataService::class));
});

it('marque « verifie » un chiffre adossé aux données', function () {
    $verdicts = $this->verifier->verify($this->document->id, "Le chiffre d'affaires 2026 atteint 150 [slide 1].");

    $hundredFifty = collect($verdicts)->firstWhere('value', 150.0);
    expect($hundredFifty['status'])->toBe('verifie')
        ->and($hundredFifty['matched_label'])->toBe("Chiffre d'affaires");
});

it('signale « a_verifier » un chiffre non adossé aux données', function () {
    $verdicts = $this->verifier->verify($this->document->id, 'Certains avancent un CA de 999.');

    $bogus = collect($verdicts)->firstWhere('value', 999.0);
    expect($bogus['status'])->toBe('a_verifier')
        ->and($bogus['matched_label'])->toBeNull();
});

it('ignore les années (pas de faux positif sur 2026)', function () {
    $verdicts = $this->verifier->verify($this->document->id, 'En 2026, la trajectoire est ambitieuse.');

    expect(collect($verdicts)->pluck('value'))->not->toContain(2026.0);
});
