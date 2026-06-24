<?php

declare(strict_types=1);

use App\Models\Document;
use App\Services\Session\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('démarre une session éphémère liée au document et au tenant', function () {
    $document = Document::factory()->create();

    $session = (new SessionService())->start($document);

    expect($session->uuid)->not->toBeEmpty()
        ->and($session->document_id)->toBe($document->id)
        ->and($session->tenant_id)->toBe($document->tenant_id)
        ->and($session->status)->toBe('active')
        ->and($session->expires_at)->not->toBeNull()
        ->and($session->expires_at->isFuture())->toBeTrue();
});

it('résout une session par son uuid', function () {
    $document = Document::factory()->create();
    $service = new SessionService();
    $session = $service->start($document);

    expect($service->resolve($session->uuid)->id)->toBe($session->id);
});
