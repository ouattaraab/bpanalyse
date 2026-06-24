<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\AI\Contracts\EmbeddingClient;
use App\Services\AI\Providers\BgeM3EmbeddingClient;
use InvalidArgumentException;

/**
 * Résout l'EmbeddingClient concret. Défaut : bge-m3 (auto-hébergé, FR, dim 1024).
 * Les embeddings restent locaux dans les deux modes (déjà souverains).
 */
final class EmbeddingManager
{
    /** @param array<string, mixed> $config  contenu de config('ai') */
    public function __construct(private readonly array $config)
    {
    }

    public function default(): EmbeddingClient
    {
        return $this->make((string) ($this->config['embeddings']['default'] ?? 'bge_m3'));
    }

    public function make(string $providerKey): EmbeddingClient
    {
        $cfg = $this->config['embeddings']['providers'][$providerKey]
            ?? throw new InvalidArgumentException("Provider embeddings inconnu : {$providerKey}");

        return match ($providerKey) {
            'bge_m3' => new BgeM3EmbeddingClient(
                python: (string) ($cfg['python'] ?? 'python3'),
                script: (string) ($cfg['script'] ?? ''),
                model: (string) ($cfg['model'] ?? 'BAAI/bge-m3'),
                dimensions: (int) ($cfg['dimensions'] ?? 1024),
                timeout: (int) ($cfg['timeout'] ?? 600),
            ),
            default => throw new InvalidArgumentException("Provider embeddings non supporté : {$providerKey}"),
        };
    }
}
