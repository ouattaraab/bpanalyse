<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\EmbeddingClient;
use App\Services\AI\Exceptions\EmbeddingException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Embeddings bge-m3 (FR, dim 1024) en mode souverain : exécute
 * tools/embeddings/embed.py dans un venv dédié via un process et lit les
 * vecteurs sur stdout. Aucune donnée ne quitte le backend.
 *
 * L'appelant envoie un LOT de textes en une seule invocation (le modèle est
 * chargé une fois par appel) — voir App\Jobs\EmbedChunksJob.
 */
final class BgeM3EmbeddingClient implements EmbeddingClient
{
    public function __construct(
        private readonly string $python,
        private readonly string $script,
        private readonly string $model = 'BAAI/bge-m3',
        private readonly int $dimensions = 1024,
        private readonly int $timeout = 600,
    ) {}

    public function embed(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        $texts = array_values($texts);

        $process = new Process([$this->python, $this->script, $this->model]);
        $process->setInput((string) json_encode(['texts' => $texts]));
        $process->setTimeout((float) $this->timeout);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            throw new EmbeddingException(
                'Échec du calcul des embeddings : '.trim($process->getErrorOutput() ?: $e->getMessage()),
                previous: $e,
            );
        } catch (Throwable $e) {
            throw new EmbeddingException('Impossible de lancer le service d\'embeddings : '.$e->getMessage(), previous: $e);
        }

        $payload = json_decode($process->getOutput(), true);

        if (! is_array($payload) || isset($payload['error'])) {
            throw new EmbeddingException('Sortie embeddings invalide : '.($payload['error'] ?? 'JSON attendu'));
        }

        $vectors = $payload['vectors'] ?? null;

        if (! is_array($vectors) || count($vectors) !== count($texts)) {
            throw new EmbeddingException('Nombre de vecteurs incohérent avec le nombre de textes.');
        }

        foreach ($vectors as $vector) {
            if (! is_array($vector) || count($vector) !== $this->dimensions) {
                throw new EmbeddingException(
                    "Dimension d'embedding non conforme (attendu {$this->dimensions})."
                );
            }
        }

        return $vectors;
    }

    public function dimensions(): int
    {
        return $this->dimensions;
    }

    public function provider(): string
    {
        return 'bge_m3';
    }
}
