<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\SessionResource;
use App\Models\Document;
use App\Services\Session\SessionService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SessionController extends Controller
{
    public function store(Document $document, SessionService $sessions): JsonResponse
    {
        $session = $sessions->start($document);

        return SessionResource::make($session)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
