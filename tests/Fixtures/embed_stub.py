#!/usr/bin/env python3
"""Stub de test : imite tools/embeddings/embed.py sans charger de modèle.
Émet un vecteur de dimension 1024 (valeurs constantes) par texte reçu."""
import json
import sys

DIM = 1024

payload = json.load(sys.stdin)
texts = payload.get("texts", []) if isinstance(payload, dict) else payload

print(json.dumps({"vectors": [[0.1] * DIM for _ in texts]}))
