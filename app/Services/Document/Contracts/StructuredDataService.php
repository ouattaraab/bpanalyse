<?php

declare(strict_types=1);

namespace App\Services\Document\Contracts;

/**
 * Accès DÉTERMINISTE aux données structurées extraites d'un document.
 *
 * Principe central anti-hallucination : tout ce qui est factuel/chiffré
 * (projections financières, clauses, dates, montants, exigences) est extrait
 * à l'ingestion et requêté ici SANS passer par le LLM. Le LLM commente le
 * résultat, il ne le produit pas.
 *
 * Au MVP, l'implémentation concrète est FinancialQueryService (BP).
 * À l'Epic 7, des extracteurs par type de document implémentent cette interface
 * (clauses pour un contrat, table d'exigences pour une norme, etc.).
 */
interface StructuredDataService
{
    /**
     * Exécute une requête structurée déterministe sur les données extraites.
     *
     * @param  int  $documentId
     * @param  string  $query  requête paramétrée/validée (jamais du SQL libre du LLM)
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>  lignes résultat, traçables vers la source
     */
    public function query(int $documentId, string $query, array $params = []): array;

    /**
     * Liste les capacités de requête disponibles pour ce type de document.
     * Sert à exposer au LLM les "outils" qu'il peut demander (function calling).
     *
     * @return array<int, array{name:string, description:string, parameters:array<string,mixed>}>
     */
    public function capabilities(): array;
}
