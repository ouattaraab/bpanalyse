#!/usr/bin/env python3
"""Génère des embeddings bge-m3 pour une liste de textes (mode souverain, local).

Entrée  : JSON sur stdin -> {"texts": ["...", "..."]}
Argument: nom du modèle (défaut BAAI/bge-m3).
Sortie  : JSON sur stdout -> {"vectors": [[...], [...]]}  (1 vecteur normalisé par texte)

Le modèle est chargé une fois par appel : l'appelant PHP envoie un lot de textes
en une seule invocation (voir EmbedChunksJob). En cas d'erreur : {"error": "..."}.
"""

import json
import sys


def fail(message: str, code: int = 1) -> "None":
    print(json.dumps({"error": message}, ensure_ascii=False))
    sys.exit(code)


def main() -> None:
    model_name = sys.argv[1] if len(sys.argv) > 1 else "BAAI/bge-m3"

    try:
        payload = json.load(sys.stdin)
    except Exception as exc:
        fail(f"entrée JSON invalide : {exc}")

    texts = payload.get("texts", []) if isinstance(payload, dict) else payload
    if not isinstance(texts, list):
        fail("'texts' doit être une liste")

    if not texts:
        print(json.dumps({"vectors": []}))
        return

    try:
        from sentence_transformers import SentenceTransformer
    except Exception as exc:
        fail(f"sentence-transformers indisponible : {exc}")

    try:
        model = SentenceTransformer(model_name)
        embeddings = model.encode(texts, normalize_embeddings=True, convert_to_numpy=True)
    except Exception as exc:
        fail(f"encodage échoué : {exc}")

    vectors = [[float(value) for value in row] for row in embeddings]
    print(json.dumps({"vectors": vectors}))


if __name__ == "__main__":
    main()
