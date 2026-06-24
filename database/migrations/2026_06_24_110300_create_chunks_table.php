<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chunks d'indexation. La colonne `embedding` (pgvector) est ajoutée séparément
 * car non gérée nativement par le Schema builder ; elle est peuplée à la story 1.4
 * (l'index vectoriel HNSW est créé à ce moment-là, une fois les vecteurs présents).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_slide_id')->nullable()->constrained()->nullOnDelete();
            $table->string('section')->nullable();
            $table->string('type'); // App\Enums\ChunkType : text | table
            $table->text('content');
            $table->text('caption')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'type']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE chunks ADD COLUMN embedding vector(1024)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chunks');
    }
};
