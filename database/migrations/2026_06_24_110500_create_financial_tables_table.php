<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tableaux financiers extraits (un par chunk de type `table` contenant des
 * valeurs chiffrées). Sert de parent aux lignes financial_metrics requêtées
 * de façon DÉTERMINISTE par FinancialQueryService (le LLM ne calcule jamais).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_slide_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('chunk_id')->nullable()->constrained('chunks')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('caption')->nullable();
            $table->text('raw_markdown');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_tables');
    }
};
