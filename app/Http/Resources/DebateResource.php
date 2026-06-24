<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Debate;
use App\Models\DebateTurn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Debate
 */
class DebateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'status' => $this->status,
            'turns' => $this->turns->map(static fn (DebateTurn $turn): array => [
                'turn_index' => $turn->turn_index,
                'persona' => $turn->persona,
                'persona_name' => $turn->persona_name,
                'content' => $turn->content,
                'sources' => $turn->sources ?? [],
                'verified_figures' => $turn->verified_figures ?? [],
            ])->values(),
        ];
    }
}
