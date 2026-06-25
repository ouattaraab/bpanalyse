<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Épinglage des réponses pertinentes d'une session (base du compte rendu one-shot).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pinned_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('explorer_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interaction_id')->constrained()->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['explorer_session_id', 'interaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pinned_items');
    }
};
