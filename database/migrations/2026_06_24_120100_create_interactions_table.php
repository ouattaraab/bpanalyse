<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Échanges question/réponse d'une session, tous modes confondus
 * (chat, présentation, débat).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('explorer_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('assistant'); // user | assistant
            $table->string('mode')->default('chat');       // chat | presentation | debate
            $table->text('question')->nullable();
            $table->text('answer')->nullable();
            $table->json('meta')->nullable();              // sources citées, etc.
            $table->timestamps();

            $table->index(['explorer_session_id', 'mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
