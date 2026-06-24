<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Présentation express : script narré JSON [{slide_id, narration, duree}],
 * généré à partir d'une question (Epic 3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('explorer_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->json('script');
            $table->string('status')->default('ready'); // pending | generating | ready | failed
            $table->unsignedInteger('duration_total')->nullable(); // secondes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presentations');
    }
};
