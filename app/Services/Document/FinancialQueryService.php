<?php

declare(strict_types=1);

namespace App\Services\Document;

use App\Models\FinancialMetric;
use App\Services\Document\Contracts\StructuredDataService;
use InvalidArgumentException;

/**
 * Implémentation BP de StructuredDataService : accès DÉTERMINISTE aux chiffres
 * via des requêtes whitelistées sur financial_metrics. Le LLM ne reçoit JAMAIS
 * de SQL libre — il demande une capability par son nom (function calling) et
 * commente le résultat. Les calculs (delta, croissance) sont faits ici, en PHP.
 */
final class FinancialQueryService implements StructuredDataService
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>
     */
    public function query(int $documentId, string $query, array $params = []): array
    {
        return match ($query) {
            'list_metrics' => $this->listMetrics($documentId),
            'get_metric' => $this->getMetric($documentId, $params),
            'compare_periods' => $this->comparePeriods($documentId, $params),
            default => throw new InvalidArgumentException("Requête financière inconnue : {$query}"),
        };
    }

    /**
     * @return array<int, array{name:string, description:string, parameters:array<string,mixed>}>
     */
    public function capabilities(): array
    {
        return [
            [
                'name' => 'list_metrics',
                'description' => 'Liste les postes financiers disponibles (libellé, unité, années couvertes).',
                'parameters' => ['type' => 'object', 'properties' => [], 'required' => []],
            ],
            [
                'name' => 'get_metric',
                'description' => 'Valeur(s) d\'un poste financier, éventuellement pour une année donnée.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'label' => ['type' => 'string', 'description' => 'Libellé exact du poste'],
                        'period_year' => ['type' => 'integer', 'description' => 'Année (optionnelle)'],
                    ],
                    'required' => ['label'],
                ],
            ],
            [
                'name' => 'compare_periods',
                'description' => 'Compare un poste entre deux années (valeurs, écart, croissance %). Calcul déterministe.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'label' => ['type' => 'string'],
                        'from_year' => ['type' => 'integer'],
                        'to_year' => ['type' => 'integer'],
                    ],
                    'required' => ['label', 'from_year', 'to_year'],
                ],
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function listMetrics(int $documentId): array
    {
        return FinancialMetric::query()
            ->where('document_id', $documentId)
            ->groupBy('label', 'unit')
            ->selectRaw('label, unit, MIN(period_year) as first_year, MAX(period_year) as last_year, COUNT(*) as points')
            ->orderBy('label')
            ->get()
            ->map(static fn ($r): array => [
                'label' => $r->label,
                'unit' => $r->unit,
                'first_year' => $r->first_year !== null ? (int) $r->first_year : null,
                'last_year' => $r->last_year !== null ? (int) $r->last_year : null,
                'points' => (int) $r->points,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>
     */
    private function getMetric(int $documentId, array $params): array
    {
        $label = $this->requireString($params, 'label');

        $query = FinancialMetric::query()
            ->where('document_id', $documentId)
            ->where('label', $label);

        if (isset($params['period_year']) && $params['period_year'] !== '') {
            $query->where('period_year', (int) $params['period_year']);
        }

        return $query->orderBy('period_year')->get()->map($this->rowMapper())->all();
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>
     */
    private function comparePeriods(int $documentId, array $params): array
    {
        $label = $this->requireString($params, 'label');
        $fromYear = (int) $this->require($params, 'from_year');
        $toYear = (int) $this->require($params, 'to_year');

        $from = $this->valueFor($documentId, $label, $fromYear);
        $to = $this->valueFor($documentId, $label, $toYear);

        $growth = ($from !== null && $to !== null && $from != 0.0)
            ? round(($to - $from) / abs($from) * 100, 2)
            : null;

        return [[
            'label' => $label,
            'unit' => $this->unitFor($documentId, $label),
            'from_year' => $fromYear,
            'from_value' => $from,
            'to_year' => $toYear,
            'to_value' => $to,
            'delta' => ($from !== null && $to !== null) ? round($to - $from, 4) : null,
            'growth_pct' => $growth,
        ]];
    }

    private function valueFor(int $documentId, string $label, int $year): ?float
    {
        $metric = FinancialMetric::query()
            ->where('document_id', $documentId)
            ->where('label', $label)
            ->where('period_year', $year)
            ->first();

        return $metric !== null ? (float) $metric->value : null;
    }

    private function unitFor(int $documentId, string $label): ?string
    {
        return FinancialMetric::query()
            ->where('document_id', $documentId)
            ->where('label', $label)
            ->value('unit');
    }

    /** @return callable(FinancialMetric): array<string, mixed> */
    private function rowMapper(): callable
    {
        return static fn (FinancialMetric $m): array => [
            'label' => $m->label,
            'period_label' => $m->period_label,
            'period_year' => $m->period_year,
            'value' => (float) $m->value,
            'unit' => $m->unit,
            'source_ref' => $m->source_ref,
        ];
    }

    /** @param array<string, mixed> $params */
    private function require(array $params, string $key): mixed
    {
        if (! isset($params[$key]) || $params[$key] === '') {
            throw new InvalidArgumentException("Paramètre requis manquant : {$key}");
        }

        return $params[$key];
    }

    /** @param array<string, mixed> $params */
    private function requireString(array $params, string $key): string
    {
        return (string) $this->require($params, $key);
    }
}
