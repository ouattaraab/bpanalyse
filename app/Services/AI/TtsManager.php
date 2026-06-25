<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\AI\Contracts\TtsClient;
use App\Services\AI\Providers\ElevenLabsTtsClient;
use App\Services\AI\Providers\XttsTtsClient;
use InvalidArgumentException;

/**
 * Résout le TtsClient concret. Défaut : ElevenLabs (cloud).
 * Mode souverain (AI_SOVEREIGN=true) → XTTS auto-hébergé.
 *
 * GOUVERNANCE : aucune voix clonée sans consentement vérifié
 * (config ai.tts.require_consent_for_cloning). Le contrôle est appliqué
 * en amont par App\Services\Voice\ConsentService (Epic 6).
 */
class TtsManager
{
    /** @param array<string, mixed> $config  contenu de config('ai') */
    public function __construct(private readonly array $config)
    {
    }

    public function default(): TtsClient
    {
        $key = ($this->config['sovereign'] ?? false)
            ? 'xtts'
            : (string) ($this->config['tts']['default'] ?? 'elevenlabs');

        return $this->make($key);
    }

    public function make(string $providerKey): TtsClient
    {
        $cfg = $this->config['tts']['providers'][$providerKey]
            ?? throw new InvalidArgumentException("Provider TTS inconnu : {$providerKey}");

        return match ($providerKey) {
            'elevenlabs' => new ElevenLabsTtsClient(apiKey: (string) ($cfg['api_key'] ?? '')),
            'xtts' => new XttsTtsClient(baseUrl: (string) ($cfg['base_url'] ?? '')),
            default => throw new InvalidArgumentException("Provider TTS non supporté : {$providerKey}"),
        };
    }

    public function requiresConsentForCloning(): bool
    {
        return (bool) ($this->config['tts']['require_consent_for_cloning'] ?? true);
    }
}
