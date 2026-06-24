<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\TtsClient;
use RuntimeException;

/**
 * Text-to-speech ElevenLabs, avec voix clonée.
 * Implémentation complète : story 2.3 (synthèse) et 6.2 (clonage).
 *
 * GOUVERNANCE : cloneVoice() et toute synthèse en voix clonée ne doivent être
 * appelées qu'après vérification d'un consentement valide
 * (App\Services\Voice\ConsentService). La révocation supprime le modèle vocal.
 */
final class ElevenLabsTtsClient implements TtsClient
{
    public function __construct(private readonly string $apiKey)
    {
    }

    public function synthesize(string $text, ?string $voiceId = null, array $options = []): string
    {
        throw new RuntimeException('ElevenLabsTtsClient::synthesize à implémenter (story 2.3).');
    }

    public function cloneVoice(array $samplePaths, string $consentReference): string
    {
        throw new RuntimeException('ElevenLabsTtsClient::cloneVoice à implémenter (story 6.2, consentement requis).');
    }

    public function provider(): string
    {
        return 'elevenlabs';
    }
}
