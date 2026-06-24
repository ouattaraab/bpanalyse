<?php

declare(strict_types=1);

namespace App\Services\Document\Contracts;

/**
 * Paramètre le comportement du moteur selon le type de document.
 *
 * NE PAS implémenter au MVP (qui cible le BP en dur). Cette interface est
 * fournie pour cadrer l'extraction de l'abstraction à l'Epic 7, au moment
 * d'ajouter le 2e type de document — pas avant.
 */
interface DocumentProfile
{
    /** Clé du profil : 'business_plan', 'contract', 'standard', ... */
    public function key(): string;

    /**
     * Personas du débat propres à ce type.
     * BP → DG/Investisseur/DAF/Commerciale ; Contrat → Juriste/PartieA/PartieB/Risk.
     *
     * @return array<int, array{name:string, posture:string, system_prompt:string}>
     */
    public function debatePersonas(): array;

    /** Service de données structurées associé (financier, clauses, exigences...). */
    public function structuredDataService(): StructuredDataService;

    /** Mode de rendu : 'native_slides' (BP/deck) ou 'generated_sections' (texte). */
    public function renderMode(): string;

    /**
     * Questions suggérées et ton pour ce type.
     *
     * @return array<int, string>
     */
    public function suggestedQuestions(): array;
}
