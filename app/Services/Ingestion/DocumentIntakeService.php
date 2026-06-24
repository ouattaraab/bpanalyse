<?php

declare(strict_types=1);

namespace App\Services\Ingestion;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Réception d'un document téléversé : stockage isolé par tenant (disque privé
 * `documents`) et création de l'enregistrement Document au statut `uploaded`.
 *
 * Le fichier n'est jamais exposé publiquement ; le pipeline d'ingestion
 * (Docling → chunking → embeddings → financials) prend le relais ensuite.
 */
final class DocumentIntakeService
{
    private const DISK = 'documents';

    public function store(Tenant $tenant, UploadedFile $file, ?string $title = null): Document
    {
        $extension = Str::lower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $storedName = (string) Str::ulid().'.'.$extension;

        // Isolation : un sous-dossier par tenant.
        $path = $file->storeAs((string) $tenant->id, $storedName, ['disk' => self::DISK]);

        return Document::create([
            'tenant_id' => $tenant->id,
            'title' => $title ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'type' => 'business_plan',
            'original_filename' => $file->getClientOriginalName(),
            'original_path' => $path,
            'mime' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'status' => DocumentStatus::Uploaded,
        ]);
    }
}
