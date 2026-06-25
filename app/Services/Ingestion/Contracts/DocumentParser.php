<?php

declare(strict_types=1);

namespace App\Services\Ingestion\Contracts;

use App\Services\Ingestion\Data\ParsedDocument;
use App\Services\Ingestion\Exceptions\DocumentParsingException;

/**
 * Parse un document (PDF/PPTX) en Markdown structuré, en PRÉSERVANT les tableaux.
 *
 * Implémentation au MVP : DoclingParser (process Python). Le contrat est isolé
 * pour permettre le mock en test et un éventuel swap d'outil de parsing.
 */
interface DocumentParser
{
    /**
     * @param  string  $absolutePath  chemin absolu du fichier source
     *
     * @throws DocumentParsingException
     */
    public function parse(string $absolutePath): ParsedDocument;
}
