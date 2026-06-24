---
description: Implémente une user story du PRD en respectant les règles du projet
---

Implémente la user story **$ARGUMENTS** du PRD (docs/PRD.md).

Avant de coder :
1. Relis la story, ses critères d'acceptation et les patterns dans memory-bank/systemPatterns.md.
2. Vérifie les règles non négociables de CLAUDE.md (chiffres déterministes, clé API jamais au front, voix consentie, audit).

Puis :
- Crée/édite les fichiers nécessaires (migrations, services, contrôleurs, front).
- Passe les appels IA par les interfaces app/Services/AI/Contracts et le routage config/ai.php.
- Écris les tests Pest correspondants (services IA mockés).

Après :
- Mets à jour memory-bank/progress.md (coche la story) et activeContext.md (prochaine action).
- Propose un message de commit concis.
