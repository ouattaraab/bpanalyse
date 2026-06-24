<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation API d'un document. N'expose PAS le chemin de stockage interne
 * (`original_path`) : l'accès au fichier passe toujours par le backend.
 *
 * @mixin Document
 */
class DocumentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'title' => $this->title,
            'type' => $this->type,
            'original_filename' => $this->original_filename,
            'mime' => $this->mime,
            'size_bytes' => $this->size_bytes,
            'status' => $this->status->value,
            'page_count' => $this->page_count,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
