---
description: (Epic 7) Crée un nouveau profil de document — à n'utiliser qu'après le MVP
---

Crée un DocumentProfile pour le type **$ARGUMENTS** (ex: contract, standard, rfp).

⚠️ Pré-requis : le MVP sur le BP doit être validé. Si c'est le tout premier profil après le BP, c'est ici qu'on EXTRAIT l'abstraction — refactore le code BP existant vers DocumentProfile au lieu de dupliquer.

Étapes :
1. Implémente App\Services\Document\Contracts\DocumentProfile pour ce type :
   - personas du débat adaptés (ex: contrat → Juriste/PartieA/PartieB/Risk),
   - structuredDataService dédié (extracteur de clauses/dates/montants, ou table d'exigences),
   - renderMode ('native_slides' ou 'generated_sections'),
   - questions suggérées et ton.
2. Enregistre le profil (config/registry) sans toucher au cœur RAG.
3. Tests Pest du nouveau profil.
4. Mets à jour le memory-bank.
