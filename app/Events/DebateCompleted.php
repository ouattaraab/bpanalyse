<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Debate;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Signale la fin d'un débat (canal public debate.{id}).
 */
final class DebateCompleted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Debate $debate) {}

    public function broadcastOn(): Channel
    {
        return new Channel('debate.'.$this->debate->id);
    }

    public function broadcastAs(): string
    {
        return 'debate.completed';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return ['status' => 'completed'];
    }
}
