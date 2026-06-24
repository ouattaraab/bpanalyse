<?php

declare(strict_types=1);

namespace App\Services\Ingestion\Exceptions;

use RuntimeException;

/**
 * Levée quand le parsing d'un document échoue (process Docling en erreur,
 * sortie JSON invalide, fichier illisible...).
 */
final class DocumentParsingException extends RuntimeException
{
}
