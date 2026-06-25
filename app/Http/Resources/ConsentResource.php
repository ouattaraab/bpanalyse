<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\VoiceConsent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VoiceConsent
 */
class ConsentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'person_name' => $this->person_name,
            'purpose' => $this->purpose,
            'legal_basis' => $this->legal_basis,
            'status' => $this->status,
            'granted_at' => $this->granted_at?->toIso8601String(),
            'retention_until' => $this->retention_until?->toDateString(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'active' => $this->isActive(),
            'voice_models' => VoiceModelResource::collection($this->whenLoaded('voiceModels')),
        ];
    }
}
