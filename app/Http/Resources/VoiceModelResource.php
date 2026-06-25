<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\VoiceModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VoiceModel
 */
class VoiceModelResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'voice_consent_id' => $this->voice_consent_id,
            'provider' => $this->provider,
            'status' => $this->status,
            'active' => $this->isActive(),
            'created_at' => $this->created_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
        ];
    }
}
