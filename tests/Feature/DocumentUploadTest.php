<?php

declare(strict_types=1);

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('documents');
});

it('téléverse un BP PDF valide et le crée au statut uploaded', function () {
    $tenant = Tenant::factory()->create();

    $response = $this->postJson('/api/documents', [
        'tenant_id' => $tenant->id,
        'title' => 'BP Groupe 2026',
        'file' => UploadedFile::fake()->createWithContent('bp.pdf', "%PDF-1.4\n%bp-explorer\n"),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', DocumentStatus::Uploaded->value)
        ->assertJsonPath('data.title', 'BP Groupe 2026')
        ->assertJsonPath('data.tenant_id', $tenant->id);

    $document = Document::firstOrFail();
    expect($document->status)->toBe(DocumentStatus::Uploaded)
        ->and($document->type)->toBe('business_plan');

    Storage::disk('documents')->assertExists($document->original_path);
});

it('isole le fichier dans le dossier du tenant', function () {
    $tenant = Tenant::factory()->create();

    $this->postJson('/api/documents', [
        'tenant_id' => $tenant->id,
        'file' => UploadedFile::fake()->createWithContent('bp.pdf', "%PDF-1.4\n"),
    ])->assertCreated();

    $document = Document::firstOrFail();

    // Le chemin de stockage commence par l'identifiant du tenant.
    expect($document->original_path)->toStartWith($tenant->id.'/');
    Storage::disk('documents')->assertExists($document->original_path);
});

it('refuse un format non autorisé', function () {
    $tenant = Tenant::factory()->create();

    $this->postJson('/api/documents', [
        'tenant_id' => $tenant->id,
        'file' => UploadedFile::fake()->createWithContent('notes.txt', 'juste du texte'),
    ])->assertStatus(422)->assertJsonValidationErrorFor('file');

    expect(Document::count())->toBe(0);
});

it('refuse un fichier trop volumineux', function () {
    $tenant = Tenant::factory()->create();

    $this->postJson('/api/documents', [
        'tenant_id' => $tenant->id,
        'file' => UploadedFile::fake()->create('gros.pdf', 60_000, 'application/pdf'), // 60 Mo > 50 Mo
    ])->assertStatus(422)->assertJsonValidationErrorFor('file');

    expect(Document::count())->toBe(0);
});

it('rejette un tenant inexistant', function () {
    $this->postJson('/api/documents', [
        'tenant_id' => 99999,
        'file' => UploadedFile::fake()->createWithContent('bp.pdf', "%PDF-1.4\n"),
    ])->assertStatus(422)->assertJsonValidationErrorFor('tenant_id');
});

it("n'expose jamais le chemin de stockage interne", function () {
    $tenant = Tenant::factory()->create();

    $response = $this->postJson('/api/documents', [
        'tenant_id' => $tenant->id,
        'file' => UploadedFile::fake()->createWithContent('bp.pdf', "%PDF-1.4\n"),
    ])->assertCreated();

    expect($response->json('data'))->not->toHaveKey('original_path');
});
