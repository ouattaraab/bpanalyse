<?php

declare(strict_types=1);

namespace App\Services\Ingestion;

use App\Services\Ingestion\Data\ParsedMetric;
use App\Services\Ingestion\Support\FinancialValueParser;

/**
 * Extrait des lignes financial_metrics à partir du Markdown d'un tableau.
 *
 * DÉTERMINISTE et SANS LLM : on lit la grille (1re colonne = poste, en-têtes =
 * périodes) et chaque cellule numérique devient une mesure (poste, période) →
 * valeur + unité. Les tableaux non financiers (aucune cellule numérique)
 * produisent zéro mesure.
 */
final class FinancialTableExtractor
{
    /**
     * @return array<int, ParsedMetric>
     */
    public function extractMetrics(string $markdown): array
    {
        $rows = $this->tableRows($markdown);

        if (count($rows) < 2) {
            return [];
        }

        $header = array_shift($rows);
        $periods = array_slice($header, 1);

        $metrics = [];

        foreach ($rows as $row) {
            $label = trim($row[0] ?? '');
            if ($label === '') {
                continue;
            }

            foreach ($periods as $i => $periodLabel) {
                $cell = $row[$i + 1] ?? null;
                if ($cell === null) {
                    continue;
                }

                $parsed = FinancialValueParser::parse($cell);
                if ($parsed === null) {
                    continue;
                }

                $periodLabel = trim($periodLabel);

                $metrics[] = new ParsedMetric(
                    label: $label,
                    value: $parsed['value'],
                    periodLabel: $periodLabel !== '' ? $periodLabel : null,
                    periodYear: FinancialValueParser::yearFrom($periodLabel),
                    unit: $parsed['unit'],
                );
            }
        }

        return $metrics;
    }

    /**
     * Renvoie les lignes du tableau (cellules), en ignorant les lignes de
     * séparation et le texte hors tableau.
     *
     * @return array<int, array<int, string>>
     */
    private function tableRows(string $markdown): array
    {
        $rows = [];

        foreach (preg_split('/\r\n|\r|\n/', $markdown) ?: [] as $line) {
            if (! str_contains($line, '|')) {
                continue;
            }
            if ($this->isSeparatorRow($line)) {
                continue;
            }

            $rows[] = $this->splitRow($line);
        }

        return $rows;
    }

    /** @return array<int, string> */
    private function splitRow(string $line): array
    {
        $line = trim($line);
        $line = preg_replace('/^\|/', '', $line) ?? $line;
        $line = preg_replace('/\|$/', '', $line) ?? $line;

        return array_map('trim', explode('|', $line));
    }

    private function isSeparatorRow(string $line): bool
    {
        $trimmed = trim($line);

        return $trimmed !== ''
            && str_contains($trimmed, '-')
            && preg_match('/^[\s|:\-]+$/', $trimmed) === 1;
    }
}
