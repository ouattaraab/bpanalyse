<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\SttClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Speech-to-text Deepgram Nova (français par défaut).
 * Envoie l'audio brut à l'API et renvoie la transcription + les mots horodatés.
 */
final class DeepgramSttClient implements SttClient
{
    private const ENDPOINT = 'https://api.deepgram.com/v1/listen';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'nova-2',
        private readonly string $language = 'fr',
    ) {
    }

    public function transcribe(string $audioPath, array $options = []): array
    {
        if (! is_file($audioPath)) {
            throw new RuntimeException("Fichier audio introuvable : {$audioPath}");
        }

        $query = http_build_query([
            'model' => $options['model'] ?? $this->model,
            'language' => $options['language'] ?? $this->language,
            'punctuate' => 'true',
        ]);

        $response = Http::withHeaders(['Authorization' => 'Token '.$this->apiKey])
            ->withBody((string) file_get_contents($audioPath), mime_content_type($audioPath) ?: 'audio/wav')
            ->timeout(120)
            ->post(self::ENDPOINT.'?'.$query);

        if ($response->failed()) {
            throw new RuntimeException(
                "Transcription Deepgram échouée : HTTP {$response->status()} {$response->body()}"
            );
        }

        $alternative = $response->json('results.channels.0.alternatives.0', []);

        $words = array_map(
            static fn (array $w): array => [
                'word' => (string) ($w['word'] ?? ''),
                'start' => (float) ($w['start'] ?? 0),
                'end' => (float) ($w['end'] ?? 0),
            ],
            $alternative['words'] ?? [],
        );

        return [
            'text' => (string) ($alternative['transcript'] ?? ''),
            'words' => $words,
        ];
    }

    public function provider(): string
    {
        return 'deepgram';
    }
}
