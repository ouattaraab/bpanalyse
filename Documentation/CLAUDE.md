# CLAUDE.md — BP Explorer

> Guide projet pour Claude Code. Lis ce fichier avant toute tâche.

## 1. Contexte produit

**BP Explorer** est un applicatif IA qui rend un business plan (BP) complexe (≈150 pages, tableaux financiers) intelligible et interrogeable pour les dirigeants lors d'un séminaire de direction. Le chat n'est PAS le cœur : la valeur est dans la **présentation express** et le **débat du board**.

Quatre capacités :
1. **Chat vocal RAG** — poser une question (oral/écrit), réponse en voix clonée du dirigeant + texte, sourcée.
2. **Présentation express** — à partir d'une question, sélection automatique des bonnes slides, narration synchronisée de 1min30–2min.
3. **Débat du board** — 4 personas (DG, Investisseur, DAF, Directrice commerciale) débattent tour par tour sur une question, répliques sourcées et chiffres vérifiés.
4. **Compte rendu one-shot** — épinglage des réponses pertinentes, export DOCX/PDF, l'utilisateur repart avec son livrable et ne revient pas.

## 2. Stack technique

- **Backend** : Laravel 11 (PHP 8.3), API REST + WebSocket (Laravel Reverb).
- **Front** : React 18 + Vite + Tailwind, Reveal.js pour les slides. (Variante Flutter Web possible.)
- **Base** : PostgreSQL 16 + extension `pgvector`.
- **File de jobs** : database queue (ou Redis) pour STT / génération / TTS asynchrones.
- **LLM (routage hybride par feature)** : Groq (Llama 3.3 70B / Qwen 2.5 32B) pour le chat, la présentation express et la narration — volume et latence. Claude Sonnet 4.6 (API Anthropic) pour le débat du board et la vérification critique des chiffres — fiabilité du raisonnement. Variante souveraine : Qwen 2.5 7B Instruct sur vLLM. Le choix se fait en config, voir §4bis.
- **STT** : Deepgram Nova. Souverain : faster-whisper (large-v3).
- **TTS voix clonée** : ElevenLabs. Souverain : XTTS-v2 / F5-TTS.
- **Embeddings** : `bge-m3` ou `multilingual-e5-large` (français).
- **Parsing BP** : Docling (préserve les tableaux).
- **Realtime conversationnel (option)** : LiveKit Agents ou Pipecat.

## 3. Conventions de code

- PHP : PSR-12, typed properties, `declare(strict_types=1)`. Services dans `app/Services`, jamais de logique métier dans les contrôleurs.
- Appels IA isolés dans `app/Services/AI/*` derrière des interfaces (`LlmClient`, `SttClient`, `TtsClient`, `EmbeddingClient`) pour permettre le swap cloud ↔ souverain via config.
- React : composants fonctionnels, hooks, pas de state global inutile. État de session local.
- Tout texte UI en **français**.
- Migrations versionnées, jamais de modif de schéma à la main.
- Tests : Pest (PHP) pour les services IA (avec mocks), Vitest (front).

## 4. Règles strictes (NE PAS ENFREINDRE)

- **Clé API jamais exposée au front.** Tous les appels IA transitent par le backend.
- **Chiffres financiers : jamais générés/devinés par le LLM.** Les projections, ratios et totaux viennent de l'outil de calcul déterministe (`FinancialQueryService`) branché sur les tableaux extraits en SQL. Le LLM commente, il ne calcule pas.
- **Clonage vocal = données biométriques.** Pas de clonage sans consentement écrit explicite, limité, révocable (Loi 2013-450 / ARTCI). Le modèle vocal est stocké isolé et lié au consentement ; sa révocation le supprime.
- **Toute réponse est auditée** : trace en base (question, sources citées, slides utilisées, horodatage).
- **Logique one-shot** : pas de rétention longue des sessions ; purge programmée.
- Paiement (si crédits payants) : Paystack / PawaPay / Wave / Orange Money / MTN MoMo. **Jamais CinetPay.**

## 4bis. Routage LLM par feature

Le LLM n'est pas choisi par projet mais par **cas d'usage**, derrière l'interface `LlmClient`. Configuration dans `config/ai.php` :

```php
'llm_routing' => [
    'chat'            => 'groq',   // volume, faible latence
    'presentation'    => 'groq',   // narration rapide
    'debate'          => 'claude', // raisonnement multi-étapes, trouve les calculs faux
    'financial_check' => 'claude', // vérification critique des chiffres
    'summary'         => 'groq',   // compte rendu
],
'providers' => [
    'groq'   => ['model' => 'llama-3.3-70b-versatile'],
    'claude' => ['model' => 'claude-sonnet-4-6'],
    'vllm'   => ['model' => 'qwen2.5-7b-instruct'], // mode souverain
],
```

Règle : le **débat du board** et toute **vérification de chiffres** vont sur Claude (fiabilité). Tout le reste peut aller sur Groq (vitesse, coût). Le mode souverain bascule tout sur `vllm` via une seule variable d'environnement. Tester le *function calling* de Groq sur de vrais tableaux avant de lui confier des appels d'outils.

## 4ter. DocumentProfile — généralisation multi-types

BP Explorer est un cas particulier d'un moteur générique d'explication de documents complexes (contrats, rapports, normes, appels d'offres, manuels…). Un **DocumentProfile** paramètre le comportement par type de document :

- **personas du débat** : BP → DG/Investisseur/DAF/Commerciale ; Contrat → Juriste/Partie A/Partie B/Risk ; Norme → Auditeur/Opérationnel/DPO.
- **extracteurs structurés** : abstraction `StructuredDataService` avec un extracteur par type (financier, clauses/dates/montants, table d'exigences…). Ce qui est factuel ne passe jamais par le LLM.
- **mode de rendu** : `slides natives` (BP, deck) vs `sections générées` (contrat, rapport texte sans slides).
- **questions suggérées** et ton.

Règle d'implémentation : **ne pas généraliser trop tôt.** Le MVP cible le BP. L'abstraction `DocumentProfile` est extraite au moment d'ajouter le **deuxième** type de document, pas avant.

## 5. Commandes utiles

```bash
php artisan serve              # backend dev
php artisan queue:work         # worker jobs IA
npm run dev                    # front Vite
php artisan migrate:fresh --seed
./vendor/bin/pest              # tests backend
php artisan bp:ingest {file}   # pipeline d'ingestion d'un BP
```

## 6. Pipeline d'ingestion (commande `bp:ingest`)

1. Upload PDF/PPTX → stockage isolé par tenant.
2. Docling → Markdown structuré (tableaux préservés).
3. Chunking sémantique : 1 tableau = 1 chunk, texte par section, métadonnées (slide_id, section, type).
4. Embeddings texte + 1 image/slide → pgvector.
5. Extraction des tableaux financiers → tables SQL dédiées (pour `FinancialQueryService`).
6. (Si consenti) clonage vocal → modèle isolé.

## 7. À NE PAS faire

- Ne pas mettre de données personnelles ou sensibles dans des query strings.
- Ne pas ajouter de framework agentique lourd au MVP — une boucle multi-agents maison suffit.
- Ne pas casser les tableaux financiers au chunking.
- Ne pas réutiliser un modèle vocal hors du contexte de son consentement.
