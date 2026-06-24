<?php

declare(strict_types=1);

namespace App\Services\Ingestion;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Tenant;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Ingestion depuis un fichier local (commande bp:ingest).
     */
    public function storeFromPath(Tenant $tenant, string $absolutePath, ?string $title = null): Document
    {
        $originalName = basename($absolutePath);
        $extension = Str::lower(pathinfo($absolutePath, PATHINFO_EXTENSION) ?: 'bin');
        $storedName = (string) Str::ulid().'.'.$extension;

        $path = Storage::disk(self::DISK)->putFileAs((string) $tenant->id, new File($absolutePath), $storedName);

        return Document::create([
            'tenant_id' => $tenant->id,
            'title' => $title ?: pathinfo($originalName, PATHINFO_FILENAME),
            'type' => 'business_plan',
            'original_filename' => $originalName,
            'original_path' => $path,
            'mime' => mime_content_type($absolutePath) ?: null,
            'size_bytes' => filesize($absolutePath) ?: null,
            'status' => DocumentStatus::Uploaded,
        ]);
    }
}
