<?php

declare(strict_types=1);

namespace App\Services\Debate;

/**
 * Personas du débat pour un business plan (codés en dur au MVP).
 * L'abstraction par DocumentProfile (Epic 7) viendra au 2e type de document.
 */
final class DebatePersonas
{
    /**
     * @return array<int, array{key:string, name:string, posture:string, prompt:string}>
     */
    public function all(): array
    {
        return [
            [
                'key' => 'dg',
                'name' => 'Directeur Général',
                'posture' => 'Défend le BP',
                'prompt' => 'Tu es le Directeur Général. Tu défends le business plan : forces, vision, trajectoire. Tu restes crédible et factuel.',
            ],
            [
                'key' => 'investor',
                'name' => 'Investisseur',
                'posture' => 'Challenge / minimise',
                'prompt' => 'Tu es un Investisseur sceptique. Tu challenges les hypothèses optimistes, tu pointes les risques et les zones de fragilité.',
            ],
            [
                'key' => 'cfo',
                'name' => 'Directeur Financier',
                'posture' => 'Logique, factuel',
                'prompt' => 'Tu es le Directeur Financier. Tu es rigoureux : tu vérifies la cohérence des chiffres et traques les incohérences. Tu ne cites que des chiffres présents dans les données vérifiées.',
            ],
            [
                'key' => 'sales',
                'name' => 'Directrice Commerciale',
                'posture' => 'Croissance',
                'prompt' => 'Tu es la Directrice Commerciale. Tu défends les initiatives de croissance, le marché et le go-to-market.',
            ],
        ];
    }

    /** Persona pour un index de tour (cycle DG → Investisseur → DAF → Commerciale). */
    public function forTurn(int $turnIndex): array
    {
        $personas = $this->all();

        return $personas[$turnIndex % count($personas)];
    }

    public function count(): int
    {
        return count($this->all());
    }
}
