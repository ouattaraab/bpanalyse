<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trace d'audit de TOUTE réponse (règle CLAUDE.md §4) :
 * question + sources citées + slides + modèle + horodatage + latence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('explorer_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('interaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('mode')->default('chat');
            $table->text('question');
            $table->json('sources')->nullable();
            $table->string('model_used')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
