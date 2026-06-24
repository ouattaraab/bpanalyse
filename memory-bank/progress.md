# Progress

## Fait
- Cadrage produit, PRD BMAD (8 sections, 7 epics, ~32 user stories).
- Diagramme d'architecture.
- Décisions techniques : routage LLM hybride Groq/Claude ; abstraction DocumentProfile cadrée pour plus tard.
- Kit de démarrage : CLAUDE.md, docs, interfaces IA, config/ai.php, memory-bank.

## En cours
- Étape 1 BOOTSTRAP terminée : plan détaillé dans `memory-bank/implementationPlan.md`.
- Étape 2 (scaffolding) **terminée et vérifiée** : Laravel 11.54, PostgreSQL 17 + pgvector 0.8.3,
  Reverb (WebSocket), routing API, Pest. Bindings IA câblés (managers + providers concrets).
  Front React 18 + Vite + Tailwind 3 + Reveal.js + Echo (build OK). Tests : 7 passés.
- Story 1.1 (upload BP) **terminée** : tenants/documents, disque privé `documents` isolé
  par tenant, DocumentController + StoreDocumentRequest (mimes pdf/pptx, max 50 Mo) +
  DocumentIntakeService + DocumentResource (sans chemin interne). Base de test pgsql dédiée
  `bp_explorer_testing`. Prochaine action : story 1.2 (parsing Docling).

## À faire (par phase — détail dans implementationPlan.md)

### Phase 0 — Socle + ingestion (Epic 1)
- [x] 0a — Scaffolding Laravel 11 + bindings interfaces (managers + providers concrets)
- [x] 1.1 — Upload BP (PDF/PPTX), isolé tenant `[tenants, documents]` — 6 tests verts
- [x] 1.2 — Parsing Docling (Python via process), tableaux préservés `[document_slides]` — 6 tests verts
      ✓ validé runtime : Docling 2.x installé (venv), parse.py + chaîne PHP→Python OK sur un vrai doc (tableaux préservés)
- [x] 1.3 — Chunking sémantique (1 tableau = 1 chunk) `[chunks]` — 7 tests verts ;
      validé sur le PRD réel (13 chunks tableau + 19 texte)
- [x] 1.4 — Embeddings bge-m3 (Python via process, souverain) → pgvector + index HNSW cosine — 6 tests verts
      (validation runtime du modèle réel bge-m3 en cours : 1er téléchargement ~2,2 Go)
- [ ] 1.5 — Extraction tableaux financiers en SQL `[financial_tables, financial_metrics]`
- [ ] 0-x — Commande `bp:ingest` (orchestration pipeline)

### Phase 1 — Chat RAG + outil de calcul (Epics 2.1-2.2, 1.5)
- [ ] Socle session/audit/retriever `[explorer_sessions, interactions, audit_logs]`
- [ ] 1.5b — `FinancialQueryService` (StructuredDataService, requêtes whitelistées)
- [ ] 2.1 — Question écrite → réponse sourcée (RAG, chiffres déterministes)
- [ ] 2.2 — Question orale (STT Deepgram, transcription affichée)

### Phase 2 — Présentation express (Epic 3) · différenciateur
- [ ] 3.1 — Sélection 3-6 slides
- [ ] 3.2 — Narration JSON `[{slide_id, narration, duree}]` `[presentations]`
- [ ] 3.3 — Défilement synchronisé voix (Reveal.js + TTS)
- [ ] 3.4 — Narration affichée à l'écrit (P1)

### Phase 3 — Débat du board (Epic 4)
- [ ] 4.1 — Orchestrateur tour-par-tour, 4 personas `[debates, debate_turns]`
- [ ] 4.2 — Chaque agent cite ses sources
- [ ] 4.3 — Vérification des chiffres (function calling → FinancialQueryService)
- [ ] 4.4 — Arrêt propre du débat (P1)

### Phase 4 — Voix / gouvernance / session / compte rendu (Epics 2.3, 5, 6)
- [ ] 6.1 — Consentement écrit avant clonage `[voice_consents]`
- [ ] 6.2 — Modèle vocal isolé/révocable `[voice_models]`
- [ ] 6.3 — Conformité Loi 2013-450/ARTCI (registre, P1)
- [ ] 2.3 — Réponse en voix clonée (P1, sous consentement)
- [ ] 5.1 — Épinglage de session `[pinned_items]`
- [ ] 5.2 — Export compte rendu DOCX/PDF
- [ ] 5.3 — Audit de toute réponse (couverture tous modes)
- [ ] 5.4 — Session éphémère + purge programmée (P1)

### Phase 5 (post-MVP) — Epic 7 DocumentProfile
- [ ] 7.1→7.6 — Extraction de l'abstraction au 2e type de document (PAS avant)

## Problèmes connus / décisions ouvertes
- Fiabilité function calling Groq à valider sur de vrais tableaux (sinon vérif chiffres → Claude, déjà défaut).
- Front React + Reveal.js vs Flutter Web : à trancher avant la Phase 2.
- Stratégie d'extraction `financial_metrics` (mapping périodes/unités) à éprouver sur un vrai BP.
