#!/usr/bin/env python3
"""Stub de test : renvoie une dimension non conforme (3 au lieu de 1024)
pour vérifier la validation côté PHP."""
import json
import sys

payload = json.load(sys.stdin)
texts = payload.get("texts", []) if isinstance(payload, dict) else payload

print(json.dumps({"vectors": [[0.1, 0.2, 0.3] for _ in texts]}))
