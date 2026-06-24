# PRD — BP Explorer (méthodologie BMAD)

> Version 0.1 — MVP · OVERNETFLOW / REVIVATECH
> Version Word équivalente : `docs/PRD_BP_Explorer_BMAD.docx`. Diagramme : `docs/architecture.svg`.

## 1. Vision et problème

**Problème.** Dans les grandes entreprises, un business plan (BP) de ~150 pages truffé de tableaux financiers complexes est diffusé tel quel aux dirigeants de filiales et responsables de business units. La plupart le survolent : la substance stratégique ne se transmet pas, et les bonnes questions ne sont pas posées faute de savoir où regarder. Un simple chatbot ne résout pas le problème car il dépend de la capacité de l'utilisateur à formuler les bonnes questions.

**Vision.** BP Explorer transforme un BP statique en expérience IA active qui va chercher l'information à la place de l'utilisateur, la contextualise selon son profil, et la met en débat. Le chat n'est pas le cœur : la valeur est dans la **présentation express auto-pilotée** et le **débat multi-agents**.

**Positionnement.** Le BP n'est qu'un premier cas d'usage. Le même moteur s'étend à tout document complexe à expliquer (contrats, rapports, normes, appels d'offres, manuels) → plateforme générique d'explication de documents (voir Epic 7).

**Proposition de valeur.**
- Diffuser la substance d'un BP en le rendant intelligible par profil (DG, commercial, BU).
- Faire émerger les bonnes questions et les angles morts (calculs faux, hypothèses fragiles) via le débat du board.
- Expérience one-shot : l'utilisateur entre, comprend, repart avec un compte rendu, ne revient pas.
- Démontrer l'IA générative embarquée sur un cas d'usage réel — au-delà du chatbot.

## 2. Objectifs et indicateurs (KPIs)

| Objectif | Indicateur | Cible MVP |
|---|---|---|
| Compréhension du BP | % participants ayant utilisé ≥1 présentation express | ≥ 70% |
| Engagement | Questions moyennes par session | ≥ 5 |
| Détection de fragilités | Fragilités/calculs signalés par le débat par BP | ≥ 3 |
| Expérience one-shot | % sessions terminées par un export | ≥ 50% |
| Latence présentation express | Temps de génération | < 20 s |
| Fiabilité chiffres | % chiffres cités tracés vers une source | 100% |

## 3. Personas utilisateurs

| Persona | Besoin | Usage clé |
|---|---|---|
| DG de filiale | Comprendre vite l'impact du BP groupe | Présentation express, chat vocal |
| Responsable de BU | Savoir ce qui l'impacte, quels chiffres surveiller | Contextualisation par profil |
| Directeur financier | Vérifier la cohérence des projections | Débat du board, outil de calcul |
| Animateur de séminaire | Faire vivre une session interactive | Débat du board projeté en direct |

## 4. Personas du débat (agents IA)

Quatre agents débattent tour par tour sur une question. Chaque réplique cite ses sources (slide/tableau) et fait vérifier ses chiffres par l'outil déterministe.

| Agent | Posture | Comportement |
|---|---|---|
| DG | Défend le BP | Forces, vision, trajectoire |
| Investisseur | Minimise / challenge | Risques, hypothèses optimistes |
| Directeur financier | Logique, factuel | Recalcule, traque les incohérences |
| Directrice commerciale | Croissance | Initiatives, marché, go-to-market |

## 5. Epics et user stories

Format BMAD. SP = story points (Fibonacci). Prio : P0 = MVP, P1 = souhaitable, P2 = ultérieur.

### EPIC 1 — Ingestion et indexation du BP
| ID | User story | Critères d'acceptation | SP | Prio |
|---|---|---|---|---|
| 1.1 | Téléverser un BP (PDF/PPTX) | Upload sécurisé, isolement tenant, formats validés | 3 | P0 |
| 1.2 | Parser en préservant les tableaux | Docling → Markdown structuré, tableaux non cassés | 5 | P0 |
| 1.3 | Découper en chunks sémantiques | 1 tableau = 1 chunk, métadonnées (slide, section, type) | 5 | P0 |
| 1.4 | Indexer texte et slides en vecteurs | Embeddings FR en pgvector, 1 image/slide | 5 | P0 |
| 1.5 | Extraire les tableaux financiers en SQL | Tables dédiées requêtables | 8 | P0 |

### EPIC 2 — Chat vocal RAG
| ID | User story | Critères d'acceptation | SP | Prio |
|---|---|---|---|---|
| 2.1 | Question écrite, réponse sourcée | RAG + citation slide/tableau, FR | 5 | P0 |
| 2.2 | Question à l'oral | STT, transcription affichée | 5 | P0 |
| 2.3 | Réponse en voix clonée | TTS voix clonée, voix + texte sync | 5 | P1 |
| 2.4 | Mettre en doute une réponse du DG | Contestation, ré-analyse contradictoire | 3 | P1 |

### EPIC 3 — Présentation express
| ID | User story | Critères d'acceptation | SP | Prio |
|---|---|---|---|---|
| 3.1 | Question → présentation 1-2 min | Sélection 3-6 slides, ordre logique | 8 | P0 |
| 3.2 | Narration 2-3 phrases / slide | Script JSON {slide_id, narration, durée} | 5 | P0 |
| 3.3 | Défilement sync avec la voix | Switch sur fin d'audio / timestamps | 5 | P0 |
| 3.4 | Narration affichée à l'écrit | Texte sous chaque slide | 2 | P1 |

### EPIC 4 — Débat du board
| ID | User story | Critères d'acceptation | SP | Prio |
|---|---|---|---|---|
| 4.1 | Lancer un débat / "critique le BP" | Orchestrateur tour-par-tour, 4 personas | 8 | P0 |
| 4.2 | Chaque agent cite ses sources | Slide/tableau référencé | 5 | P0 |
| 4.3 | Vérification des chiffres | Appels outil de calcul, fragilités signalées | 8 | P0 |
| 4.4 | Arrêt propre du débat | Condition (N tours / consensus / divergence) | 3 | P1 |

### EPIC 5 — Session, audit et compte rendu
| ID | User story | Critères d'acceptation | SP | Prio |
|---|---|---|---|---|
| 5.1 | Épingler les réponses pertinentes | État de session, liste épinglée | 3 | P0 |
| 5.2 | Exporter un compte rendu IA | Synthèse en DOCX/PDF téléchargeable | 5 | P0 |
| 5.3 | Audit de toute réponse | Trace : question, sources, slides, horodatage | 3 | P0 |
| 5.4 | Session éphémère | Purge programmée | 2 | P1 |

### EPIC 6 — Gouvernance voix et conformité
| ID | User story | Critères d'acceptation | SP | Prio |
|---|---|---|---|---|
| 6.1 | Consentement écrit avant clonage | Explicite, limité, lié au modèle vocal | 5 | P0 |
| 6.2 | Modèle vocal isolé et révocable | Révocation = suppression | 5 | P0 |
| 6.3 | Conformité Loi 2013-450/ARTCI | Registre, finalité, durée | 3 | P1 |

### EPIC 7 — DocumentProfile / multi-types
> À engager après validation du MVP sur le BP.

| ID | User story | Critères d'acceptation | SP | Prio |
|---|---|---|---|---|
| 7.1 | Choisir un profil de document à l'ingestion | Profil paramètre l'ingestion | 5 | P1 |
| 7.2 | Personas du débat dépendant du profil | Contrat → Juriste/Parties/Risk ; Norme → Auditeur/Ops/DPO | 8 | P1 |
| 7.3 | Extracteurs structurés abstraits par type | StructuredDataService : financier, clauses, exigences | 8 | P1 |
| 7.4 | Rendu adapté aux documents sans slides | Mode "sections générées" pour texte | 8 | P1 |
| 7.5 | Questions et ton dédiés par profil | Paramétrés par DocumentProfile | 3 | P2 |
| 7.6 | Nouveau profil sans toucher au cœur | Profil par config/template, cœur RAG inchangé | 5 | P2 |

## 6. Architecture technique (synthèse)

Voir `docs/architecture.svg`.

| Couche | Choix principal | Variante souveraine |
|---|---|---|
| Backend | Laravel 11 (PHP 8.3) | — |
| Front | React 18 + Vite + Reveal.js | Flutter Web |
| Base vectorielle | PostgreSQL 16 + pgvector | pgvector |
| LLM (routage par feature) | Groq (Llama 3.3 70B) + Claude Sonnet 4.6 | Qwen 2.5 7B / vLLM |
| STT | Deepgram Nova | faster-whisper |
| TTS voix clonée | ElevenLabs | XTTS-v2 / F5-TTS |
| Embeddings | bge-m3 / e5-large | bge-m3 |
| Parsing BP | Docling | Docling |
| Multi-agent | Boucle maison (LangGraph option) | idem |
| File de jobs | Database queue / Redis | idem |

### 6.1 Routage LLM par feature

Le modèle est choisi par cas d'usage, derrière une interface unique. Groq pour le volume et la latence (séminaire live) ; Claude pour le raisonnement critique (détecter les calculs faux). Le mode souverain bascule tout sur Qwen/vLLM via config.

| Feature | Fournisseur | Raison |
|---|---|---|
| Chat vocal | Groq | Volume, latence |
| Présentation express | Groq | Narration rapide |
| Débat du board | Claude | Raisonnement, détection des fragilités |
| Vérification des chiffres | Claude | Fiabilité critique |
| Compte rendu | Groq | Synthèse économique |

## 7. Risques et points de vigilance

| Risque | Impact | Mitigation |
|---|---|---|
| Hallucination de chiffres | Crédibilité détruite | Calcul déterministe ; le LLM ne calcule jamais |
| Fraude au dirigeant (deepfake voix) | Juridique, réputationnel | Consentement écrit, modèle isolé/révocable, démo pédagogique |
| Tableaux cassés au chunking | RAG inexploitable | Docling + règle 1 tableau = 1 chunk |
| Latence présentation express | Mauvaise UX | Jobs asynchrones, pré-calcul embeddings slides |
| Dépendance cloud | Souveraineté, coût | Interfaces abstraites, variante auto-hébergée |

## 8. Roadmap MVP

1. **Phase 0** — Socle : Laravel + React + pgvector + pipeline d'ingestion (Epic 1).
2. **Phase 1** — Chat RAG sourcé + outil de calcul financier (Epics 2.1-2.2, 1.5).
3. **Phase 2** — Présentation express (Epic 3) — le différenciateur.
4. **Phase 3** — Débat du board (Epic 4).
5. **Phase 4** — Voix clonée + gouvernance + compte rendu (Epics 2.3, 5, 6).
6. **Phase 5 (post-MVP)** — Généralisation multi-types via DocumentProfile (Epic 7), au 2e type de document.

> Référence d'effort : la version originale a été produite en ~1,5 à 2 jours en se concentrant sur les features 3 et 4 plutôt que sur le chat.
