<?php

declare(strict_types=1);

namespace App\Services\Debate;

use App\Services\Document\Contracts\StructuredDataService;
use App\Services\Ingestion\Support\FinancialValueParser;

/**
 * Vérifie DÉTERMINISTEMENT les chiffres affirmés dans une réplique de débat :
 * chaque nombre est confronté aux valeurs réelles extraites des tableaux
 * (via StructuredDataService). Un nombre non adossé aux données est signalé
 * « à vérifier » — c'est ce qui permet de détecter les calculs faux.
 *
 * Aucun LLM : la vérification reste 100% factuelle.
 */
final class FinancialVerifier
{
    public function __construct(private readonly StructuredDataService $financial)
    {
    }

    /**
     * @return array<int, array{value:float, status:string, matched_label:?string}>
     */
    public function verify(int $documentId, string $turnText): array
    {
        $realValues = $this->realValues($documentId);
        if ($realValues === []) {
            return [];
        }

        $verdicts = [];
        foreach ($this->extractNumbers($turnText) as $number) {
            $matchedLabel = $this->matchLabel($number, $realValues);

            $verdicts[] = [
                'value' => $number,
                'status' => $matchedLabel !== null ? 'verifie' : 'a_verifier',
                'matched_label' => $matchedLabel,
            ];
        }

        return $verdicts;
    }

    /**
     * Valeurs réelles {label => [valeurs]} obtenues via StructuredDataService.
     *
     * @return array<string, array<int, float>>
     */
    private function realValues(int $documentId): array
    {
        $values = [];
        foreach ($this->financial->query($documentId, 'list_metrics') as $metric) {
            $label = (string) $metric['label'];
            foreach ($this->financial->query($documentId, 'get_metric', ['label' => $label]) as $row) {
                if ($row['value'] !== null) {
                    $values[$label][] = (float) $row['value'];
                }
            }
        }

        return $values;
    }

    /** @return array<int, float> */
    private function extractNumbers(string $text): array
    {
        $pattern = '/-?\d{1,3}(?:[ \x{00A0}\x{202F}]\d{3})+(?:[.,]\d+)?|-?\d+(?:[.,]\d+)?/u';
        preg_match_all($pattern, $text, $matches);

        $numbers = [];
        foreach ($matches[0] as $token) {
            $parsed = FinancialValueParser::parse($token);
            if ($parsed === null || $this->looksLikeYear($token, $parsed['value'])) {
                continue;
            }
            $numbers[(string) $parsed['value']] = $parsed['value'];
        }

        return array_values($numbers);
    }

    private function looksLikeYear(string $token, float $value): bool
    {
        $digits = preg_replace('/\D/', '', $token) ?? '';

        return strlen($digits) === 4 && $value >= 1900 && $value <= 2100 && floor($value) === $value;
    }

    /** @param array<string, array<int, float>> $realValues */
    private function matchLabel(float $number, array $realValues): ?string
    {
        foreach ($realValues as $label => $values) {
            foreach ($values as $value) {
                $tolerance = max(0.5, abs($value) * 0.005);
                if (abs($value - $number) <= $tolerance) {
                    return $label;
                }
            }
        }

        return null;
    }
}
