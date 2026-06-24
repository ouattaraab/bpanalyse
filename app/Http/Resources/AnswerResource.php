<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Interaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Réponse du chat : texte + sources citées + identifiant d'interaction (audit).
 *
 * @mixin Interaction
 */
class AnswerResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'interaction_id' => $this->id,
            'question' => $this->question,
            'answer' => $this->answer,
            'sources' => $this->meta['sources'] ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
