<?php

declare(strict_types=1);

namespace App\Services\Ingestion;

use App\Services\Ingestion\Contracts\DocumentParser;
use App\Services\Ingestion\Data\ParsedDocument;
use App\Services\Ingestion\Exceptions\DocumentParsingException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Parser concret basé sur Docling (Python). Exécute tools/docling/parse.py dans
 * un venv dédié via un process et lit un JSON structuré sur stdout.
 *
 * Docling préserve les tableaux (rendus en Markdown pipe) : c'est le critère
 * d'acceptation clé de la story 1.2.
 */
final class DoclingParser implements DocumentParser
{
    public function __construct(
        private readonly string $python,
        private readonly string $script,
        private readonly int $timeout = 600,
    ) {}

    public function parse(string $absolutePath): ParsedDocument
    {
        if (! is_file($absolutePath)) {
            throw new DocumentParsingException("Fichier introuvable : {$absolutePath}");
        }

        $process = new Process([$this->python, $this->script, $absolutePath]);
        $process->setTimeout((float) $this->timeout);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            throw new DocumentParsingException(
                'Échec du parsing Docling : '.trim($process->getErrorOutput() ?: $e->getMessage()),
                previous: $e,
            );
        } catch (Throwable $e) {
            throw new DocumentParsingException('Impossible de lancer Docling : '.$e->getMessage(), previous: $e);
        }

        $payload = json_decode($process->getOutput(), true);

        if (! is_array($payload)) {
            throw new DocumentParsingException('Sortie Docling illisible (JSON attendu).');
        }

        if (isset($payload['error'])) {
            throw new DocumentParsingException('Docling : '.(string) $payload['error']);
        }

        return ParsedDocument::fromArray($payload);
    }
}
