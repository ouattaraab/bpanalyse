<?php

declare(strict_types=1);

namespace App\Services\AI\Exceptions;

use RuntimeException;

/**
 * Levée quand la génération d'embeddings échoue (process en erreur, sortie
 * invalide, dimension non conforme à la colonne pgvector...).
 */
final class EmbeddingException extends RuntimeException
{
}
