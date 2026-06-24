<?php

declare(strict_types=1);

/**
 * Configuration du pipeline d'ingestion (Epic 1).
 *
 * Docling est un outil Python : le backend PHP l'invoque via un process
 * (script tools/docling/parse.py exécuté dans un venv dédié) et lit un JSON
 * structuré sur stdout. Aucune dépendance Python n'est requise côté PHP ;
 * le contrat DocumentParser est mockable en test.
 */
return [
    'docling' => [
        // Binaire Python du venv dédié (voir tools/docling/requirements.txt).
        'python' => env('DOCLING_PYTHON', base_path('tools/docling/.venv/bin/python')),
        // Script d'entrée qui exécute Docling et émet le JSON.
        'script' => env('DOCLING_SCRIPT', base_path('tools/docling/parse.py')),
        // Délai max d'un parsing (secondes). Un BP de ~150 pages peut être long.
        'timeout' => (int) env('DOCLING_TIMEOUT', 600),
    ],
];
