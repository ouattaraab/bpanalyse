<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ExplorerSession;
use App\Models\Interaction;
use App\Models\PinnedItem;
use App\Services\Session\PinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PinController extends Controller
{
    public function index(ExplorerSession $session, PinService $pins): JsonResponse
    {
        $items = $pins->forSession($session)->map(static fn (PinnedItem $item): array => [
            'id' => $item->id,
            'interaction_id' => $item->interaction_id,
            'note' => $item->note,
            'question' => $item->interaction?->question,
            'answer' => $item->interaction?->answer,
        ]);

        return response()->json(['data' => $items]);
    }

    public function store(Request $request, ExplorerSession $session, PinService $pins): JsonResponse
    {
        $validated = $request->validate([
            'interaction_id' => ['required', 'integer', 'exists:interactions,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $interaction = Interaction::where('explorer_session_id', $session->id)
            ->findOrFail($validated['interaction_id']);

        $item = $pins->pin($session, $interaction, $validated['note'] ?? null);

        return response()->json(['data' => ['id' => $item->id]], HttpResponse::HTTP_CREATED);
    }

    public function destroy(PinnedItem $pin, PinService $pins): Response
    {
        $pins->unpin($pin);

        return response()->noContent();
    }
}
