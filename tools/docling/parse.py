#!/usr/bin/env python3
"""Parse un document (PDF/PPTX) avec Docling et émet un JSON structuré sur stdout.

Contrat de sortie (consommé par App\\Services\\Ingestion\\DoclingParser) :
{
  "title": str | null,
  "page_count": int,
  "markdown": str,                       # Markdown global, tableaux préservés
  "slides": [
    {"index": int, "title": str|null, "section": str|null, "markdown": str}
  ]
}

En cas d'erreur, émet {"error": "..."} et sort avec un code non nul.
Aucune valeur n'est inventée : on ne fait qu'extraire ce que Docling produit.
"""

import json
import sys
from collections import defaultdict


def fail(message: str, code: int = 1) -> "None":
    print(json.dumps({"error": message}, ensure_ascii=False))
    sys.exit(code)


def page_of(item) -> "int | None":
    prov = getattr(item, "prov", None)
    if prov:
        try:
            return int(prov[0].page_no)
        except Exception:
            return None
    return None


def table_to_markdown(item, doc) -> str:
    # L'API d'export de tableau varie selon la version de Docling.
    for args in ((doc,), ()):
        try:
            md = item.export_to_markdown(*args)
            if md:
                return md
        except TypeError:
            continue
        except Exception:
            break
    try:
        return item.export_to_dataframe().to_markdown(index=False)
    except Exception:
        return ""


def main() -> None:
    if len(sys.argv) < 2:
        fail("usage: parse.py <fichier>", 2)

    source = sys.argv[1]

    try:
        from docling.document_converter import DocumentConverter
    except Exception as exc:  # docling absent / venv incomplet
        fail(f"docling indisponible : {exc}")

    try:
        converter = DocumentConverter()
        result = converter.convert(source)
        doc = result.document
    except Exception as exc:
        fail(f"conversion échouée : {exc}")

    try:
        full_markdown = doc.export_to_markdown()
    except Exception:
        full_markdown = ""

    try:
        page_count = len(doc.pages) if getattr(doc, "pages", None) else 0
    except Exception:
        page_count = 0

    pages_md: "dict[int, list[str]]" = defaultdict(list)
    pages_title: "dict[int, str]" = {}
    pages_section: "dict[int, str]" = {}
    document_title = None
    current_section = None

    try:
        nodes = list(doc.iterate_items())
    except Exception:
        nodes = []

    for node in nodes:
        item = node[0] if isinstance(node, tuple) else node
        label = str(getattr(item, "label", "") or "")
        pno = page_of(item) or 1

        # Tableau : préservé tel quel (critère d'acceptation clé).
        if item.__class__.__name__ == "TableItem" or hasattr(item, "export_to_dataframe"):
            md = table_to_markdown(item, doc)
            if md:
                pages_md[pno].append(md)
            pages_section[pno] = current_section
            continue

        text = getattr(item, "text", None)
        text = text.strip() if isinstance(text, str) else ""
        if not text:
            continue

        if label == "title":
            if document_title is None:
                document_title = text
            pages_title.setdefault(pno, text)
            pages_md[pno].append(f"# {text}")
        elif label in ("section_header", "subtitle_level_1"):
            current_section = text
            pages_md[pno].append(f"## {text}")
        else:
            pages_md[pno].append(text)

        pages_section[pno] = current_section

    if page_count:
        indices = sorted(set(list(pages_md.keys()) + list(range(1, page_count + 1))))
    else:
        indices = sorted(pages_md.keys())

    slides = [
        {
            "index": idx,
            "title": pages_title.get(idx),
            "section": pages_section.get(idx),
            "markdown": "\n\n".join(pages_md.get(idx, [])),
        }
        for idx in indices
    ]

    # Repli : aucune segmentation exploitable mais un Markdown global existe.
    if (not slides or all(not s["markdown"] for s in slides)) and full_markdown:
        slides = [{"index": 1, "title": document_title, "section": None, "markdown": full_markdown}]

    if not page_count:
        page_count = len(slides)

    print(json.dumps(
        {
            "title": document_title,
            "page_count": page_count,
            "markdown": full_markdown,
            "slides": slides,
        },
        ensure_ascii=False,
    ))


if __name__ == "__main__":
    main()
