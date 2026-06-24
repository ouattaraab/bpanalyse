<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\AI\Contracts\LlmClient;
use App\Services\AI\Providers\AnthropicClient;
use App\Services\AI\Providers\GroqClient;
use App\Services\AI\Providers\OpenAiCompatibleLlmClient;
use InvalidArgumentException;

/**
 * Résout le LlmClient concret par FEATURE (chat, presentation, debate,
 * financial_check, summary) selon config/ai.php (llm_routing + providers).
 *
 * En mode souverain (AI_SOVEREIGN=true), toutes les features basculent sur 'vllm'.
 * Le débat du board et la vérification des chiffres vont sur Claude (fiabilité).
 */
final class LlmManager
{
    /** @param array<string, mixed> $config  contenu de config('ai') */
    public function __construct(private readonly array $config)
    {
    }

    /** Client LLM adapté à la feature demandée. */
    public function for(string $feature): LlmClient
    {
        return $this->make($this->resolveProviderKey($feature));
    }

    /** Clé de provider à utiliser pour une feature (gère la bascule souveraine). */
    public function resolveProviderKey(string $feature): string
    {
        if ($this->config['sovereign'] ?? false) {
            return 'vllm';
        }

        $routing = $this->config['llm_routing'] ?? [];

        return $routing[$feature]
            ?? throw new InvalidArgumentException("Feature LLM inconnue : {$feature}");
    }

    /** Construit le client concret pour une clé de provider (groq|claude|vllm). */
    public function make(string $providerKey): LlmClient
    {
        $provider = $this->config['providers'][$providerKey]
            ?? throw new InvalidArgumentException("Provider LLM inconnu : {$providerKey}");

        return match ($provider['driver'] ?? null) {
            'groq' => new GroqClient(
                apiKey: (string) ($provider['api_key'] ?? ''),
                model: (string) $provider['model'],
                baseUrl: (string) $provider['base_url'],
            ),
            'anthropic' => new AnthropicClient(
                apiKey: (string) ($provider['api_key'] ?? ''),
                model: (string) $provider['model'],
            ),
            'openai_compatible' => new OpenAiCompatibleLlmClient(
                provider: $providerKey,
                apiKey: (string) ($provider['api_key'] ?? ''),
                model: (string) $provider['model'],
                baseUrl: (string) $provider['base_url'],
            ),
            default => throw new InvalidArgumentException(
                "Driver LLM non supporté : " . ($provider['driver'] ?? 'null')
            ),
        };
    }
}
