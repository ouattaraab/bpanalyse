<?php

declare(strict_types=1);

namespace App\Services\Voice\Exceptions;

use RuntimeException;

/**
 * Levée quand une opération vocale (clonage ou synthèse en voix clonée) est
 * tentée sans consentement valide. Garde-fou central de la gouvernance biométrique.
 */
final class ConsentException extends RuntimeException
{
}
