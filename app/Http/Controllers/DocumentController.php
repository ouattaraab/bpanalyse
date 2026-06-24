<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Tenant;
use App\Services\Ingestion\DocumentIntakeService;
use App\Services\Ingestion\IngestionPipeline;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Téléversement d'un BP. Aucune logique métier ici : la réception est déléguée
 * à DocumentIntakeService, puis le pipeline d'ingestion est lancé (asynchrone).
 */
class DocumentController extends Controller
{
    public function store(
        StoreDocumentRequest $request,
        DocumentIntakeService $intake,
        IngestionPipeline $pipeline,
    ): JsonResponse {
        $tenant = $this->resolveTenant($request->input('tenant_id'));

        $document = $intake->store(
            tenant: $tenant,
            file: $request->file('file'),
            title: $request->input('title'),
        );

        // Lance parse → chunk → embeddings → extraction financière (file de jobs).
        $pipeline->dispatch($document);

        return DocumentResource::make($document)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Document $document): DocumentResource
    {
        return DocumentResource::make($document);
    }

    private function resolveTenant(?int $tenantId): Tenant
    {
        if ($tenantId !== null) {
            return Tenant::findOrFail($tenantId);
        }

        return Tenant::firstOrCreate(['slug' => 'default'], ['name' => 'Default']);
    }
}
