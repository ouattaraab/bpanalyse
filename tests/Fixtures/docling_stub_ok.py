#!/usr/bin/env python3
"""Stub de test : imite la sortie de tools/docling/parse.py sans Docling.
Émet 2 slides dont une contenant un tableau Markdown (vérification : non cassé)."""
import json
import sys

table = "| Poste | 2025 |\n|---|---|\n| CA | 100 |"

print(json.dumps({
    "title": "BP de test",
    "page_count": 2,
    "markdown": "# BP de test\n\n" + table,
    "slides": [
        {"index": 1, "title": "BP de test", "section": "Finances", "markdown": "Introduction\n\n" + table},
        {"index": 2, "title": None, "section": "Conclusion", "markdown": "Synthèse finale."},
    ],
}, ensure_ascii=False))
sys.exit(0)
