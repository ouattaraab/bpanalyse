# BOOTSTRAP — Démarrer BP Explorer dans Claude Code

Ce fichier donne les prompts à copier dans Claude Code, dans l'ordre. Chaque bloc est un message. Attends la fin d'une étape avant la suivante.

---

## Étape 0 — Initialisation du contexte

> Lis `CLAUDE.md`, `docs/PRD.md` et tous les fichiers de `memory-bank/`. Fais-moi une synthèse de ta compréhension du projet en 10 lignes : objectif, 4 features clés, stack, et les 5 règles non négociables. Ne code rien pour l'instant.

Objectif : vérifier que Claude Code a bien chargé le contexte avant de produire quoi que ce soit.

---

## Étape 1 — Analyse et plan d'implémentation

> À partir du PRD et de l'architecture (`docs/architecture.svg`), établis un plan d'implémentation détaillé pour la **Phase 0** (Epic 1 — Ingestion) et la **Phase 1** (chat RAG + outil de calcul). Pour chaque user story, liste : fichiers à créer, migrations, services, et tests. Mets à jour `memory-bank/activeContext.md` et `memory-bank/progress.md` avec ce plan. N'écris pas encore le code applicatif.

---

## Étape 2 — Scaffolding Laravel

> Initialise un projet Laravel 11 dans ce dossier (sans écraser CLAUDE.md, docs/, memory-bank/, .claude/, ni les interfaces déjà présentes dans app/Services). Configure PostgreSQL + pgvector, les variables d'environnement pour Groq/Claude/Deepgram/ElevenLabs, et la file de jobs. Implémente la config `config/ai.php` déjà fournie et le binding des interfaces `LlmClient`, `SttClient`, `TtsClient`, `EmbeddingClient` vers des providers concrets.

---

## Étape 3 — Epic 1 : pipeline d'ingestion

> Implémente l'Epic 1 (stories 1.1 à 1.5) : commande `php artisan bp:ingest {file}`, parsing Docling, chunking sémantique (règle : 1 tableau = 1 chunk + métadonnées), embeddings vers pgvector, extraction des tableaux financiers en tables SQL. Écris les tests Pest avec mocks des services IA. Respecte la règle : aucun chiffre généré par le LLM.

---

## Étape 4 — Epic 2 (chat RAG) + outil de calcul

> Implémente l'Epic 2 stories 2.1-2.2 (chat RAG sourcé, écrit puis oral) et le `FinancialQueryService` (Epic 1.5) branché sur les tables financières. Toute réponse cite ses sources (slide/tableau) et trace un log d'audit. Le routage LLM suit `config/ai.php` (chat → groq).

---

## Étape 5 — Epic 3 : présentation express (le différenciateur)

> Implémente l'Epic 3 : à partir d'une question, sélection de 3-6 slides pertinentes, génération d'un script narré JSON `[{slide_id, narration, duree}]`, synchronisation du défilement avec le TTS. Front React + Reveal.js. Routage : présentation → groq.

---

## Étape 6 — Epic 4 : débat du board

> Implémente l'Epic 4 : orchestrateur tour-par-tour de 4 personas (DG, Investisseur, DAF, Commerciale), chaque réplique sourcée, vérification des chiffres via `FinancialQueryService`. Routage : débat et vérification → claude (raisonnement critique). Condition d'arrêt configurable.

---

## Étape 7 — Epics 5 & 6 : session, compte rendu, gouvernance voix

> Implémente l'épinglage de session, l'export du compte rendu (DOCX/PDF), l'audit complet, la purge des sessions, et la gouvernance du clonage vocal (consentement écrit, modèle isolé/révocable, registre ARTCI/Loi 2013-450).

---

## Plus tard — Epic 7 : multi-types (NE PAS faire au MVP)

> Quand on ajoutera un 2e type de document, extrais l'abstraction `DocumentProfile` : personas configurables, `StructuredDataService` avec extracteurs par type, mode de rendu "sections générées" pour les documents sans slides. Ne pas généraliser avant ce moment.

---

## Conseils d'usage

- Après chaque étape, demande à Claude Code de **mettre à jour `memory-bank/progress.md` et `activeContext.md`**. C'est ce qui maintient la continuité BMAD entre sessions.
- Commits fréquents, un par story idéalement.
- Avant de confier le *function calling* à Groq (outil de calcul appelé par un agent), teste-le sur tes vrais tableaux : la fiabilité varie selon le modèle. En cas de doute, route ces appels vers Claude.
- Slash-commandes disponibles dans `.claude/commands/` : `/implement-story`, `/new-document-profile`, `/review-ai-safety`.
