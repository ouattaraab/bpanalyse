<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consentement au clonage vocal (données biométriques).
 * Explicite, limité (finalité + durée), révocable — Loi 2013-450 / ARTCI.
 * Sert de registre de traitement (story 6.3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('person_name');                 // dirigeant dont la voix est clonée
            $table->string('purpose');                      // finalité (ex : narration BP séminaire X)
            $table->string('legal_basis')->default('consentement_ecrit');
            $table->string('signed_document_path')->nullable(); // preuve écrite du consentement
            $table->timestamp('granted_at');
            $table->date('retention_until')->nullable();    // durée de conservation
            $table->timestamp('revoked_at')->nullable();
            $table->string('status')->default('active');    // active | revoked
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_consents');
    }
};
