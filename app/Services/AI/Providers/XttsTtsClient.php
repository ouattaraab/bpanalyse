<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\TtsClient;
use RuntimeException;

/**
 * Text-to-speech souverain : XTTS-v2 / F5-TTS, auto-hébergé.
 * Implémentation complète : story 2.3 / 6.2 (variante souveraine).
 * Mêmes garde-fous de consentement que le provider cloud.
 */
final class XttsTtsClient implements TtsClient
{
    public function __construct(private readonly string $baseUrl)
    {
    }

    public function synthesize(string $text, ?string $voiceId = null, array $options = []): string
    {
        throw new RuntimeException('XttsTtsClient::synthesize à implémenter (story 2.3).');
    }

    public function cloneVoice(array $samplePaths, string $consentReference): string
    {
        throw new RuntimeException('XttsTtsClient::cloneVoice à implémenter (story 6.2, consentement requis).');
    }

    public function deleteVoice(string $voiceId): void
    {
        // Souverain : la suppression du modèle local sera gérée à l'implémentation XTTS.
    }

    public function provider(): string
    {
        return 'xtts';
    }
}
