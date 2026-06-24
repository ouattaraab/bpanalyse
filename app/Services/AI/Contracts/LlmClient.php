<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

/**
 * Abstraction de l'accès aux modèles de langage.
 *
 * Le provider concret est résolu par feature via config/ai.php (llm_routing).
 * Ne JAMAIS exposer de clé API au front : tous les appels passent par le backend.
 * Le LLM ne calcule JAMAIS de chiffres financiers — il commente des valeurs
 * fournies par App\Services\Document\Contracts\StructuredDataService.
 */
interface LlmClient
{
    /**
     * Complétion simple (un tour).
     *
     * @param  array<int, array{role:string, content:string}>  $messages
     * @param  array<string, mixed>  $options  ex: ['temperature' => 0.2, 'max_tokens' => 1000]
     */
    public function complete(array $messages, array $options = []): string;

    /**
     * Complétion avec outils (function calling). Utilisé par le débat du board
     * pour appeler l'outil de calcul. Vérifier la fiabilité du provider avant usage.
     *
     * @param  array<int, array{role:string, content:string}>  $messages
     * @param  array<int, array<string, mixed>>  $tools
     * @param  array<string, mixed>  $options
     * @return array{content:?string, tool_calls:array<int, array<string, mixed>>}
     */
    public function completeWithTools(array $messages, array $tools, array $options = []): array;

    /** Identifiant du provider concret (groq|claude|vllm). */
    public function provider(): string;
}
