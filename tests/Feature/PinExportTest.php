<?php

declare(strict_types=1);

use App\Models\Document;
use App\Services\Session\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function pinnableSession(): array
{
    $document = Document::factory()->create();
    $session = app(SessionService::class)->start($document);
    $interaction = $session->interactions()->create([
        'document_id' => $document->id,
        'role' => 'assistant',
        'mode' => 'chat',
        'question' => 'Quel est le CA 2026 ?',
        'answer' => 'Le chiffre d\'affaires 2026 atteint 150 [slide 4].',
    ]);

    return [$session, $interaction];
}

it('épingle une réponse et liste les épingles', function () {
    [$session, $interaction] = pinnableSession();

    $this->postJson("/api/sessions/{$session->uuid}/pins", [
        'interaction_id' => $interaction->id,
        'note' => 'Chiffre clé',
    ])->assertCreated();

    $this->getJson("/api/sessions/{$session->uuid}/pins")
        ->assertOk()
        ->assertJsonPath('data.0.note', 'Chiffre clé')
        ->assertJsonPath('data.0.answer', 'Le chiffre d\'affaires 2026 atteint 150 [slide 4].');
});

it('exporte un compte rendu DOCX des réponses épinglées', function () {
    [$session, $interaction] = pinnableSession();
    app(\App\Services\Session\PinService::class)->pin($session, $interaction, 'Important');

    $this->get("/api/sessions/{$session->uuid}/export?format=docx")
        ->assertOk()
        ->assertDownload('compte-rendu.docx');
});

it('exporte un compte rendu PDF', function () {
    [$session, $interaction] = pinnableSession();
    app(\App\Services\Session\PinService::class)->pin($session, $interaction);

    $this->get("/api/sessions/{$session->uuid}/export?format=pdf")
        ->assertOk()
        ->assertDownload('compte-rendu.pdf');
});

it('refuse un format d\'export inconnu', function () {
    [$session] = pinnableSession();

    $this->get("/api/sessions/{$session->uuid}/export?format=txt")->assertStatus(422);
});
