<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modèle vocal cloné, isolé et LIÉ à son consentement. Sa révocation le supprime
 * (chez le provider) et le marque révoqué localement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voice_consent_id')->constrained()->cascadeOnDelete();
            $table->string('provider');           // elevenlabs | xtts
            $table->string('external_voice_id');  // identifiant de voix chez le provider
            $table->string('status')->default('active'); // active | revoked
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_models');
    }
};
