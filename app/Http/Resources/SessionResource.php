<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ExplorerSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ExplorerSession
 */
class SessionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'document_id' => $this->document_id,
            'status' => $this->status,
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}
