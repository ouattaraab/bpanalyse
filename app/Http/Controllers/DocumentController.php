<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Tenant;
use App\Services\Ingestion\DocumentIntakeService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Téléversement d'un BP. Aucune logique métier ici : la réception est déléguée
 * à DocumentIntakeService (convention projet : pas de métier dans les contrôleurs).
 */
class DocumentController extends Controller
{
    public function store(StoreDocumentRequest $request, DocumentIntakeService $intake): JsonResponse
    {
        $tenant = Tenant::findOrFail($request->integer('tenant_id'));

        $document = $intake->store(
            tenant: $tenant,
            file: $request->file('file'),
            title: $request->input('title'),
        );

        return DocumentResource::make($document)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
