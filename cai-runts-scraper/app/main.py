from __future__ import annotations

import base64
import tempfile
from pathlib import Path
from typing import Any

from fastapi import FastAPI, File, HTTPException, UploadFile

from app.analyzer import extract_bilancio_pdf
from app.scraper import run_scraper

app = FastAPI(title="cai-runts-scraper")

_BILANCIO_FIELDS = (
    "oneri_a_interesse_generale", "oneri_b_attivita_diverse", "oneri_c_raccolta_fondi",
    "oneri_d_finanziarie_patrimoniali", "oneri_e_supporto_generale", "totale_oneri",
    "proventi_a_interesse_generale", "proventi_b_attivita_diverse", "proventi_c_raccolta_fondi",
    "proventi_d_finanziarie_patrimoniali", "proventi_e_supporto_generale", "totale_proventi",
    "risultato_ante_imposte", "imposte", "risultato_esercizio",
)


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


@app.post("/analyze/bilancio")
async def analyze_bilancio(file: UploadFile = File(...)) -> dict[str, Any]:
    content = await file.read()

    with tempfile.NamedTemporaryFile(suffix=".pdf") as tmp_file:
        tmp_file.write(content)
        tmp_file.flush()
        result = extract_bilancio_pdf(tmp_file.name, ocr_fallback=False)

    response = {field: result.get(field) for field in _BILANCIO_FIELDS}
    response["raw_text"] = (result.get("_raw_text") or "")[:2000]
    response["ocr"] = bool(result.get("_ocr", False))

    return response


@app.post("/scrape/runts-entity")
async def scrape_runts_entity(codice_fiscale: str) -> dict[str, Any]:
    with tempfile.TemporaryDirectory() as tmp_dir:
        try:
            entities, _retry_stats = await run_scraper(
                denominazione=None,
                headless=True,
                delay_ms=500,
                codice_fiscale=codice_fiscale,
                attachments_dir=Path(tmp_dir),
            )
        except Exception as exc:
            raise HTTPException(status_code=502, detail=str(exc)) from exc

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
