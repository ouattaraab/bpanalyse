<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AskQuestionRequest;
use App\Http\Resources\PresentationResource;
use App\Models\ExplorerSession;
use App\Models\Presentation;
use App\Services\Presentation\PresentationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PresentationController extends Controller
{
    public function store(
        AskQuestionRequest $request,
        ExplorerSession $session,
        PresentationService $service,
    ): JsonResponse {
        $presentation = $service->create($session, (string) $request->string('question'));

        return PresentationResource::make($presentation)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Presentation $presentation): PresentationResource
    {
        return PresentationResource::make($presentation);
    }
}
