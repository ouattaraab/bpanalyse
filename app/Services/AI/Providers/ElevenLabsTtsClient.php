<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\TtsClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Text-to-speech ElevenLabs, avec voix clonée.
 *
 * GOUVERNANCE : cloneVoice() et toute synthèse en voix clonée ne doivent être
 * appelées qu'après vérification d'un consentement valide (ConsentService /
 * VoiceModelService). deleteVoice() supprime le modèle (révocation).
 */
final class ElevenLabsTtsClient implements TtsClient
{
    private const BASE = 'https://api.elevenlabs.io/v1';

    public function __construct(private readonly string $apiKey) {}

    public function synthesize(string $text, ?string $voiceId = null, array $options = []): string
    {
        $voiceId ??= (string) ($options['voice_id'] ?? '');
        if ($voiceId === '') {
            throw new RuntimeException('Aucune voix fournie pour la synthèse.');
        }

        $response = $this->client()
            ->withHeaders(['Accept' => 'audio/mpeg'])
            ->post(self::BASE."/text-to-speech/{$voiceId}", [
                'text' => $text,
                'model_id' => $options['model_id'] ?? 'eleven_multilingual_v2',
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Synthèse ElevenLabs échouée : HTTP {$response->status()}");
        }

        $path = 'tts/'.Str::ulid().'.mp3';
        Storage::disk('local')->put($path, $response->body());

        return Storage::disk('local')->path($path);
    }

    public function cloneVoice(array $samplePaths, string $consentReference): string
    {
        $request = $this->client()->asMultipart();
        foreach ($samplePaths as $index => $samplePath) {
            $request->attach("files[{$index}]", (string) file_get_contents($samplePath), basename($samplePath));
        }

        $response = $request->post(self::BASE.'/voices/add', [
            'name' => 'bp-explorer-'.$consentReference,
        ]);

        if ($response->failed()) {
            throw new RuntimeException("Clonage ElevenLabs échoué : HTTP {$response->status()}");
        }

        return (string) $response->json('voice_id');
    }

    public function deleteVoice(string $voiceId): void
    {
        $this->client()->delete(self::BASE."/voices/{$voiceId}");
    }

    public function provider(): string
    {
        return 'elevenlabs';
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders(['xi-api-key' => $this->apiKey])->timeout(120);
    }
}
