<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cycle de vie d'un document dans le pipeline d'ingestion.
 * uploaded → parsing → parsed → indexed (ou failed à toute étape).
 */
enum DocumentStatus: string
{
    case Uploaded = 'uploaded';
    case Parsing = 'parsing';
    case Parsed = 'parsed';
    case Indexed = 'indexed';
    case Failed = 'failed';
}
