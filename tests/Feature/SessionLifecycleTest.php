<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\ExplorerSession;
use App\Models\Interaction;
use App\Services\Audit\AuditLogger;
use App\Services\Session\SessionPurger;
use App\Services\Session\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('trace l\'audit de chaque mode (chat, présentation, débat)', function () {
    $document = Document::factory()->create();
    $session = app(SessionService::class)->start($document);
    $logger = app(AuditLogger::class);

    $logger->log($session, null, 'chat', 'q1', [], 'groq', 10);
    $logger->log($session, null, 'presentation', 'q2', [], 'groq', 20);
    $logger->log($session, null, 'debate', 'q3', [], 'claude', 30);

    $data = $this->getJson("/api/sessions/{$session->uuid}/audit")
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->json('data');

    expect(collect($data)->pluck('mode')->sort()->values()->all())
        ->toBe(['chat', 'debate', 'presentation']);
});

it('purge les sessions expirées et leurs données associées', function () {
    $document = Document::factory()->create();

    $expired = ExplorerSession::factory()->create([
        'document_id' => $document->id,
        'tenant_id' => $document->tenant_id,
        'expires_at' => now()->subDay(),
    ]);
    $expired->interactions()->create([
        'document_id' => $document->id,
        'role' => 'assistant',
        'mode' => 'chat',
        'question' => 'q',
        'answer' => 'a',
    ]);

    $active = app(SessionService::class)->start($document); // expire dans le futur

    $purged = app(SessionPurger::class)->purgeExpired();

    expect($purged)->toBe(1)
        ->and(ExplorerSession::find($expired->id))->toBeNull()
        ->and(ExplorerSession::find($active->id))->not->toBeNull()
        ->and(Interaction::where('explorer_session_id', $expired->id)->count())->toBe(0);
});

it('purge aussi via la commande artisan', function () {
    $document = Document::factory()->create();
    ExplorerSession::factory()->create([
        'document_id' => $document->id,
        'tenant_id' => $document->tenant_id,
        'expires_at' => now()->subHour(),
    ]);

    $this->artisan('sessions:purge')->assertSuccessful();

    expect(ExplorerSession::count())->toBe(0);
});
