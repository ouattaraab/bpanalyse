<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\SttClient;
use RuntimeException;

/**
 * Speech-to-text souverain : faster-whisper (large-v3), auto-hébergé.
 * Implémentation complète : story 2.2 (variante souveraine).
 */
final class WhisperSttClient implements SttClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $model = 'large-v3',
    ) {}

    public function transcribe(string $audioPath, array $options = []): array
    {
        throw new RuntimeException('WhisperSttClient::transcribe à implémenter (story 2.2).');
    }

    public function provider(): string
    {
        return 'whisper';
    }
}
