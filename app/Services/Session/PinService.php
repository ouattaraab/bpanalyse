<?php

declare(strict_types=1);

namespace App\Services\Session;

use App\Models\ExplorerSession;
use App\Models\Interaction;
use App\Models\PinnedItem;
use Illuminate\Database\Eloquent\Collection;

/**
 * Épinglage des réponses pertinentes d'une session (story 5.1).
 */
final class PinService
{
    public function pin(ExplorerSession $session, Interaction $interaction, ?string $note = null): PinnedItem
    {
        return PinnedItem::updateOrCreate(
            ['explorer_session_id' => $session->id, 'interaction_id' => $interaction->id],
            ['note' => $note],
        );
    }

    public function unpin(PinnedItem $item): void
    {
        $item->delete();
    }

    /** @return Collection<int, PinnedItem> */
    public function forSession(ExplorerSession $session): Collection
    {
        return $session->pinnedItems()->with('interaction')->get();
    }
}
