# System Patterns

## Architecture
Voir docs/architecture.svg. 5 zones : front (React/Reveal.js), backend Laravel 11, services IA, données (pgvector + tables structurées), pipeline d'ingestion.

## Patterns clés
- **Interfaces IA abstraites** : LlmClient, SttClient, TtsClient, EmbeddingClient. Provider concret résolu par config → swap cloud↔souverain trivial.
- **Routage LLM par feature** : chat/présentation/résumé → Groq ; débat/vérif chiffres → Claude. Config dans config/ai.php.
- **Données structurées déterministes** : StructuredDataService (MVP : FinancialQueryService). Le LLM commente, ne calcule jamais.
- **Jobs asynchrones** : STT/génération/TTS découplés via queue ; streaming progressif vers le front.
- **DocumentProfile** (Epic 7, plus tard) : personas + extracteurs + mode de rendu paramétrés par type de document. Ne pas généraliser avant le 2e type.

## Règles d'ingestion
1 tableau = 1 chunk avec sa légende. Métadonnées : slide_id, section, type. Tableaux financiers extraits en SQL séparé.

## Audit
Toute réponse tracée : question, sources citées, slides utilisées, horodatage.
