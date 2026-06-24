<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StartDebateRequest;
use App\Http\Resources\DebateResource;
use App\Jobs\RunDebateJob;
use App\Models\Debate;
use App\Models\ExplorerSession;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DebateController extends Controller
{
    public function start(StartDebateRequest $request, ExplorerSession $session): JsonResponse
    {
        $debate = Debate::create([
            'explorer_session_id' => $session->id,
            'document_id' => $session->document_id,
            'question' => (string) $request->string('question'),
            'status' => 'pending',
            'stop_condition' => ['max_rounds' => (int) ($request->integer('max_rounds') ?: 2)],
        ]);

        // Asynchrone : déroulé via queue:work (live), ou immédiat en mode sync.
        RunDebateJob::dispatch($debate->id);

        return DebateResource::make($debate->fresh()->load('turns'))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function show(Debate $debate): DebateResource
    {
        return DebateResource::make($debate->load('turns'));
    }
}
