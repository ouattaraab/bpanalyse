<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\AI\Contracts\SttClient;
use App\Services\AI\Providers\DeepgramSttClient;
use App\Services\AI\Providers\WhisperSttClient;
use InvalidArgumentException;

/**
 * Résout le SttClient concret. Défaut : Deepgram (cloud).
 * Mode souverain (AI_SOVEREIGN=true) → faster-whisper auto-hébergé.
 */
final class SttManager
{
    /** @param array<string, mixed> $config  contenu de config('ai') */
    public function __construct(private readonly array $config) {}

    public function default(): SttClient
    {
        $key = ($this->config['sovereign'] ?? false)
            ? 'whisper'
            : (string) ($this->config['stt']['default'] ?? 'deepgram');

        return $this->make($key);
    }

    public function make(string $providerKey): SttClient
    {
        $cfg = $this->config['stt']['providers'][$providerKey]
            ?? throw new InvalidArgumentException("Provider STT inconnu : {$providerKey}");

        return match ($providerKey) {
            'deepgram' => new DeepgramSttClient(
                apiKey: (string) ($cfg['api_key'] ?? ''),
                model: (string) ($cfg['model'] ?? 'nova-2'),
                language: (string) ($cfg['language'] ?? 'fr'),
            ),
            'whisper' => new WhisperSttClient(
                baseUrl: (string) ($cfg['base_url'] ?? ''),
                model: (string) ($cfg['model'] ?? 'large-v3'),
            ),
            default => throw new InvalidArgumentException("Provider STT non supporté : {$providerKey}"),
        };
    }
}
