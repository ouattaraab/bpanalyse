<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\DebateTurn;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Diffuse une réplique de débat dès sa création (canal public debate.{id}),
 * pour un affichage temps réel côté front (Reverb / Echo).
 */
final class DebateTurnCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public DebateTurn $turn) {}

    public function broadcastOn(): Channel
    {
        return new Channel('debate.'.$this->turn->debate_id);
    }

    public function broadcastAs(): string
    {
        return 'turn.created';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'turn_index' => $this->turn->turn_index,
            'persona' => $this->turn->persona,
            'persona_name' => $this->turn->persona_name,
            'content' => $this->turn->content,
            'sources' => $this->turn->sources ?? [],
            'verified_figures' => $this->turn->verified_figures ?? [],
        ];
    }
}
