<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\SttClient;
use RuntimeException;

/**
 * Speech-to-text Deepgram Nova (français par défaut).
 * Implémentation complète : story 2.2.
 */
final class DeepgramSttClient implements SttClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'nova-2',
        private readonly string $language = 'fr',
    ) {
    }

    public function transcribe(string $audioPath, array $options = []): array
    {
        throw new RuntimeException('DeepgramSttClient::transcribe à implémenter (story 2.2).');
    }

    public function provider(): string
    {
        return 'deepgram';
    }
}
