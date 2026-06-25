<?php

declare(strict_types=1);

namespace App\Services\Ingestion\Support;

/**
 * Parse DÉTERMINISTE d'une cellule de tableau en (valeur, unité).
 *
 * Faithful by design : la valeur est extraite VERBATIM (les suffixes d'échelle
 * comme « M€ » ou « % » deviennent l'unité, on ne multiplie jamais). Aucun LLM.
 * Gère les conventions FR (espace = séparateur de milliers, virgule décimale)
 * et EN (virgule = milliers, point décimal), les négatifs et les parenthèses.
 */
final class FinancialValueParser
{
    private const NON_VALUES = ['', '-', '—', '–', 'n/a', 'na', 'nd', 'n.d.', 'ns', '.', '...'];

    /**
     * @return array{value: float, unit: ?string}|null null si la cellule n'est pas numérique
     */
    public static function parse(string $cell): ?array
    {
        $raw = trim($cell);

        if (in_array(mb_strtolower($raw), self::NON_VALUES, true)) {
            return null;
        }

        $negative = false;

        // Parenthèses comptables = négatif.
        if (preg_match('/^\((.*)\)$/u', $raw, $m) === 1) {
            $negative = true;
            $raw = trim($m[1]);
        }
        if (str_starts_with($raw, '-')) {
            $negative = true;
        }

        // Retire espaces (y compris insécables fines/normales) pour isoler le nombre.
        $cleaned = preg_replace('/[\s\x{00A0}\x{202F}]/u', '', $raw) ?? '';

        $hasComma = str_contains($cleaned, ',');
        $hasDot = str_contains($cleaned, '.');

        if ($hasComma && $hasDot) {
            // Convention EN : la virgule sépare les milliers.
            $cleaned = str_replace(',', '', $cleaned);
        } elseif ($hasComma) {
            // Convention FR : la virgule est décimale.
            $cleaned = str_replace(',', '.', $cleaned);
        }

        if (preg_match('/-?\d+(?:\.\d+)?/', $cleaned, $nm) !== 1) {
            return null;
        }

        $value = (float) $nm[0];
        if ($negative) {
            $value = -abs($value);
        }

        // L'unité = ce qui reste après avoir retiré chiffres, séparateurs et signes.
        $unit = trim(preg_replace('/[\d\s.,()+\-\x{00A0}\x{202F}]/u', '', $raw) ?? '');

        return ['value' => $value, 'unit' => $unit === '' ? null : $unit];
    }

    /** Extrait l'année d'un libellé de période (« 2025 », « Exercice 2025 », « 2025e »). */
    public static function yearFrom(string $periodLabel): ?int
    {
        if (preg_match('/(19|20)\d{2}/', $periodLabel, $m) === 1) {
            return (int) $m[0];
        }

        return null;
    }
}
