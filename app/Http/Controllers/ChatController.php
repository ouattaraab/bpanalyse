<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AskQuestionRequest;
use App\Http\Resources\AnswerResource;
use App\Models\ExplorerSession;
use App\Services\Rag\RagService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ChatController extends Controller
{
    public function ask(AskQuestionRequest $request, ExplorerSession $session, RagService $rag): JsonResponse
    {
        $interaction = $rag->answer($session, (string) $request->string('question'));

        // 200 explicite : répondre n'est pas "créer" une ressource côté client
        // (sinon Laravel renvoie 201 car l'interaction vient d'être créée).
        return AnswerResource::make($interaction)
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
}
