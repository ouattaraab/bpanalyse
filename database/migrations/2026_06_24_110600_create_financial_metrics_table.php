<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Données chiffrées en format long : une ligne = (poste, période) → valeur+unité.
 * Extraites verbatim des tableaux (jamais générées). `source_ref` trace la
 * provenance (slide, tableau, chunk) pour l'audit et la citation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_table_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('category')->nullable();
            $table->string('period_label')->nullable();
            $table->unsignedSmallInteger('period_year')->nullable();
            $table->decimal('value', 20, 4)->nullable();
            $table->string('unit')->nullable();
            $table->json('source_ref')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'label']);
            $table->index(['document_id', 'period_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_metrics');
    }
};
