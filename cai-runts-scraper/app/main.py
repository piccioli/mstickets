from __future__ import annotations

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

        # Success branch: implemented in Task 4.
        raise NotImplementedError
