<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

/**
 * Groq (API compatible OpenAI). Utilisé pour le chat, la présentation express
 * et le compte rendu — volume et faible latence.
 *
 * ATTENTION : valider le function calling de Groq sur de vrais tableaux avant
 * de lui confier l'outil de calcul (sinon router la vérification vers Claude).
 */
final class GroqClient extends OpenAiCompatibleLlmClient
{
    public function __construct(string $apiKey, string $model, string $baseUrl)
    {
        parent::__construct(
            provider: 'groq',
            apiKey: $apiKey,
            model: $model,
            baseUrl: $baseUrl,
        );
    }
}
