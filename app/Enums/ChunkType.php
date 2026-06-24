<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Nature d'un chunk d'indexation.
 * - table : un tableau (financier ou non), JAMAIS scindé (règle 1 tableau = 1 chunk).
 * - text  : un bloc de texte, découpé par section.
 */
enum ChunkType: string
{
    case Text = 'text';
    case Table = 'table';
}
