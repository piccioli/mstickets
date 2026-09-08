from __future__ import annotations

import base64
import tempfile
from pathlib import Path
from typing import Any

from fastapi import FastAPI

from app.scraper import run_scraper

app = FastAPI(title="cai-runts-scraper")


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


@app.post("/scrape/runts-entity")
async def scrape_runts_entity(codice_fiscale: str) -> dict[str, Any]:
    with tempfile.TemporaryDirectory() as tmp_dir:
        entities, _retry_stats = await run_scraper(
            denominazione=None,
            headless=True,
            delay_ms=500,
            codice_fiscale=codice_fiscale,
            attachments_dir=Path(tmp_dir),
        )

        if not entities:
            return {"found": False}

        entity = entities[0]
        documents_raw = entity.pop("atti_documenti", [])
        board_members_raw = entity.pop("cariche", [])
        entity.pop("raw_json", None)

        entity_out = {
            "id_runts": entity.get("id_runts"),
            "codice_fiscale": entity.get("codice_fiscale"),
            "denominazione": entity.get("denominazione"),
            "forma_giuridica": entity.get("forma_giuridica"),
            "natura_giuridica": entity.get("natura_giuridica"),
            "sede_indirizzo": entity.get("sede_indirizzo"),
            "sede_civico": entity.get("sede_civico"),
            "sede_comune": entity.get("sede_comune"),
            "sede_provincia": entity.get("sede_provincia"),
            "sede_regione": entity.get("sede_regione"),
            "sede_cap": entity.get("sede_cap"),
            "data_iscrizione": entity.get("data_iscrizione"),
            "sezione_registro": entity.get("sezione_registro"),
            "settori_attivita": entity.get("settori_attivita"),
            "rappresentante_legale": entity.get("rappresentante_legale"),
            "sito_web": entity.get("sito_web"),
            "pec": entity.get("pec"),
            "url_dettaglio": entity.get("url_dettaglio"),
        }

        board_members = [
            {
                "ruolo": bm.get("ruolo"),
                "nome": bm.get("nome"),
                "cognome": bm.get("cognome"),
                "codice_fiscale": bm.get("codice_fiscale"),
                "valid_from": bm.get("valid_from"),
                "valid_to": bm.get("valid_to"),
            }
            for bm in board_members_raw
        ]

        documents = []
        for doc in documents_raw:
            skip_reason = doc.get("skip_reason")
            content_base64 = None
            path = doc.get("path")
            if skip_reason is None and path:
                content_base64 = base64.b64encode(Path(path).read_bytes()).decode("ascii")

            documents.append({
                "documento": doc.get("documento"),
                "codice_pratica": doc.get("codice_pratica"),
                "tipo": doc.get("tipo"),
                "anno": doc.get("anno"),
                "filename": doc.get("filename"),
                "mime": doc.get("mime"),
                "size": doc.get("size"),
                "hash_sha256": doc.get("hash_sha256"),
                "skip_reason": skip_reason,
                "content_base64": content_base64,
            })

        return {"found": True, "entity": entity_out, "board_members": board_members, "documents": documents}
