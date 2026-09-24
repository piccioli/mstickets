# Servizio cai-runts-scraper (Fase 9, Storia 2) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the new Python `cai-runts-scraper` FastAPI service — a port of the RUNTS scraping logic already
proven in the standalone prototype — exposing two HTTP JSON endpoints (`POST /scrape/runts-entity`,
`POST /analyze/bilancio`) that Orchestrator (PHP, Story 3, separate plan) will call. This plan produces a
working, independently-testable service; it does **not** touch the mstickets PHP codebase at all.

**Architecture:** `scraper.py`/`analyzer.py` are ported verbatim (logic unchanged) from the prototype at
`/Users/alessiopiccioli/Documents/LAVORO/MS/SOFTWARE/RUNTS/scraper/`, only import paths adjusted for the new
package layout. A thin FastAPI wrapper (`app/main.py`) calls the ported library functions directly (never the
prototype's buggy CLI `main()`) and translates their in-memory dict shapes into stable JSON responses,
explicitly handling the "entity not found" case the prototype's CLI does not handle cleanly. Every endpoint
test mocks `run_scraper`/`extract_bilancio_pdf` — no real Playwright browser or real RUNTS network call in
automated tests; the final task adds a real, manual, one-time verification against the live RUNTS site.

**Tech Stack:** Python (version pinned by the chosen Playwright Docker base image, verified in Task 1),
FastAPI + Uvicorn, Playwright (already used headless by the ported `scraper.py`), pdfplumber, pytest +
`fastapi.testclient.TestClient` + `unittest.mock.patch` for isolation from Playwright/RUNTS.

**Spec:** `docs/superpowers/specs/2026-09-07-cai-runts-scraper-service-design.md` (§1-§3, §7-§8 — this plan
implements Story 2 only; Story 3, the PHP-side wiring, is a separate plan:
`docs/superpowers/plans/2026-09-08-cai-runts-orchestrator-wiring.md`).

## Global Constraints

- New top-level directory `cai-runts-scraper/` in the `mstickets` repo (sibling of `app/`, `docker/`) — a
  second language in the repo, not a second repository.
- Never call the prototype's CLI `main()` from the service — always import and call `run_scraper()`/
  `extract_bilancio_pdf()` directly, and handle their known rough edges (empty-result vs. timeout vs.
  exception) explicitly inside the FastAPI wrapper, never let them leak as an unhandled 500 with a Python
  traceback body.
- No SQLite/datapack persistence in this service at all — it is stateless per request. Every downloaded file
  lives only in an ephemeral per-request temp directory, deleted before the response is returned.
- `/scrape/runts-entity`'s JSON response builds every field explicitly via `.get(key)` with the exact key set
  from the design doc's schema (§3.3) — never spread a raw scraper dict verbatim into the response, so a key
  the prototype sometimes omits still always appears as `null` in JSON rather than being silently absent
  (silently-absent keys would cause `$row->forma_giuridica` to be an undefined-property warning on the PHP
  side in Story 3).
- `/analyze/bilancio`'s response uses the exact same Italian field names `extract_bilancio_pdf()` already
  returns (`oneri_a_interesse_generale`, ..., `risultato_esercizio`) — no relabeling in Python, so the PHP
  side (Story 3) can reuse a mapper shaped exactly like `CaiDatapackImporter::importFinancialStatements()`'s
  existing mapping.
- OCR fallback (`ocr_fallback` parameter of `extract_bilancio_pdf`) is explicitly disabled
  (`ocr_fallback=False`) in every call from this service — out of scope per design doc §1/§7 (no
  `tesseract`/`pdf2image`/`poppler-utils` installed).

---

### Task 1: Repo scaffold, Dockerfile, `/health` endpoint

**Files:**
- Create: `cai-runts-scraper/requirements.txt`
- Create: `cai-runts-scraper/Dockerfile`
- Create: `cai-runts-scraper/app/__init__.py` (empty)
- Create: `cai-runts-scraper/app/main.py`
- Create: `cai-runts-scraper/tests/__init__.py` (empty)
- Create: `cai-runts-scraper/tests/test_main.py`

**Interfaces:**
- Produces: `app.main:app` (FastAPI instance), `GET /health` → `{"status": "ok"}` — consumed by Task 2+
  (same `app` object gets more routes added) and by the Docker healthcheck.

- [ ] **Step 1: Write the failing test**

```python
from fastapi.testclient import TestClient

from app.main import app

client = TestClient(app)


def test_health_returns_ok():
    response = client.get("/health")
    assert response.status_code == 200
    assert response.json() == {"status": "ok"}
```

- [ ] **Step 2: Run test to verify it fails**

Run (from `cai-runts-scraper/`): `python3 -m pytest tests/test_main.py -v`
Expected: FAIL — `ModuleNotFoundError: No module named 'app'` (nothing created yet).

- [ ] **Step 3: Write `requirements.txt`**

```
fastapi>=0.111.0
uvicorn[standard]>=0.29.0
playwright>=1.44.0
pdfplumber>=0.11
pytest>=9.0
httpx>=0.27
```

(`httpx` is required by `fastapi.testclient.TestClient` under the hood, not used for outbound calls by this
service — the service never calls another HTTP service itself.)

- [ ] **Step 4: Write the minimal FastAPI app**

`cai-runts-scraper/app/main.py`:
```python
from __future__ import annotations

from fastapi import FastAPI

app = FastAPI(title="cai-runts-scraper")


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `python3 -m pytest tests/test_main.py -v`
Expected: PASS (1 test) — run this from inside `cai-runts-scraper/` with `app/` on the Python path (pytest
picks up the local directory automatically when `tests/__init__.py`/`app/__init__.py` exist and pytest is
invoked from `cai-runts-scraper/`).

- [ ] **Step 6: Write the Dockerfile**

Determine the exact Python version shipped by the chosen Playwright base image before finalizing the tag
comment: `docker run --rm mcr.microsoft.com/playwright/python:v1.47.0-jammy python3 --version`. Use the
Playwright version pinned by `requirements.txt` above (`>=1.44.0`) as a floor — pick the latest
`mcr.microsoft.com/playwright/python:vX.Y.Z-jammy` tag available at implementation time that satisfies it
(the exact patch version is not load-bearing for this plan; record whatever was verified in a code comment).

```dockerfile
FROM mcr.microsoft.com/playwright/python:v1.47.0-jammy

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY app ./app

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD python3 -c "import urllib.request; urllib.request.urlopen('http://localhost:8000/health', timeout=3)" || exit 1

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
```

- [ ] **Step 7: Build the image and verify the health endpoint over HTTP**

Run:
```bash
cd cai-runts-scraper
docker build -t cai-runts-scraper:dev .
docker run --rm -p 8010:8000 -d --name cai-runts-scraper-dev cai-runts-scraper:dev
sleep 2
curl -sf http://localhost:8010/health
docker stop cai-runts-scraper-dev
```
Expected: `curl` prints `{"status":"ok"}`, exit code 0.

- [ ] **Step 8: Commit**

```bash
cd "$(git rev-parse --show-toplevel)"
git add cai-runts-scraper/requirements.txt cai-runts-scraper/Dockerfile cai-runts-scraper/app/__init__.py cai-runts-scraper/app/main.py cai-runts-scraper/tests/__init__.py cai-runts-scraper/tests/test_main.py
git commit -m "feat: Fase 9 storia 2.1 - scaffold servizio cai-runts-scraper con endpoint /health"
```

---

### Task 2: Porting di `scraper.py`/`analyzer.py` dal prototipo

**Files:**
- Create: `cai-runts-scraper/app/scraper.py`
- Create: `cai-runts-scraper/app/analyzer.py`
- Test: `cai-runts-scraper/tests/test_ported_modules.py`

**Interfaces:**
- Produces: `app.scraper.run_scraper(denominazione, headless, delay_ms, codice_fiscale, attachments_dir) ->
  tuple[list[dict], dict]` (async), `app.scraper.classify_codice_pratica(codice_pratica: str) -> str`,
  `app.analyzer.extract_bilancio_pdf(path: str, ocr_fallback: bool = True) -> dict` — consumed by Task 3-6.

- [ ] **Step 1: Copy the two source files verbatim**

```bash
cp /Users/alessiopiccioli/Documents/LAVORO/MS/SOFTWARE/RUNTS/scraper/scraper.py cai-runts-scraper/app/scraper.py
cp /Users/alessiopiccioli/Documents/LAVORO/MS/SOFTWARE/RUNTS/scraper/analyzer.py cai-runts-scraper/app/analyzer.py
```

- [ ] **Step 2: Adjust import paths only**

Open both copied files and change any `from scraper.X import ...` / `from scraper import X` (referring to
sibling modules within the old `scraper` package, e.g. a shared `logger`/`geocoder`/`db` import) to
`from app.X import ...` / `from app import X`, matching the new package name `app`. Do **not** change any
other logic, regex pattern, timeout value, or control flow — this is a verbatim port, not a rewrite. Record
in a one-line comment at the top of each file which prototype commit/date it was ported from (use the
prototype's own `git log -1 --format=%H -- scraper/scraper.py` / `scraper/analyzer.py` run from
`/Users/alessiopiccioli/Documents/LAVORO/MS/SOFTWARE/RUNTS`, if that directory is a git repo — if it is not,
note "no git history available, ported 2026-09-08" instead).

If `scraper.py` imports a `db` module (used only for SQLite persistence, e.g. `from scraper.db import
upsert_ente`) that this service does not need (this service never writes SQLite), remove that specific
import and any call sites that reference it — `run_scraper()`/`extract_atti_documenti()`/`extract_cariche()`
themselves must not depend on `db.py` for their return value construction (verified in the Story 2 research:
`run_scraper()` returns `(entities, retry_stats)` as plain dicts, with no `db` calls inside it — the `db`
persistence calls happen only in the prototype's separate `main.py` CLI driver, which is not being ported).

- [ ] **Step 3: Write the smoke test**

```python
import inspect

from app.analyzer import extract_bilancio_pdf
from app.scraper import classify_codice_pratica, run_scraper


def test_run_scraper_is_an_async_function_with_expected_parameters():
    assert inspect.iscoroutinefunction(run_scraper)
    params = inspect.signature(run_scraper).parameters
    for name in ("codice_fiscale", "attachments_dir", "headless", "delay_ms"):
        assert name in params


def test_classify_codice_pratica_maps_known_codes():
    assert classify_codice_pratica("B00") == "bilancio_esercizio"
    assert classify_codice_pratica("XYZ_UNKNOWN") == "altro"


def test_extract_bilancio_pdf_is_a_sync_function_with_expected_parameters():
    assert not inspect.iscoroutinefunction(extract_bilancio_pdf)
    params = inspect.signature(extract_bilancio_pdf).parameters
    assert "path" in params
    assert "ocr_fallback" in params
```

- [ ] **Step 4: Run test to verify it fails, then passes**

Run: `python3 -m pytest tests/test_ported_modules.py -v`
Expected: FAILs first with `ModuleNotFoundError` before Step 1/2 are done in the working tree (if run out of
order); after Steps 1-2, run again and expect PASS (3 tests). This task is an exception to strict
write-test-first ordering because it ports existing, already-correct logic rather than inventing new
behavior — the "red" step here just confirms the port is importable and shaped as expected, not that new
logic works.

- [ ] **Step 5: Verify no leftover references to the old package name**

Run: `grep -rn "from scraper\.\|^import scraper\." cai-runts-scraper/app/`
Expected: no output (empty) — any remaining hit means an import path was missed in Step 2.

- [ ] **Step 6: Commit**

```bash
git add cai-runts-scraper/app/scraper.py cai-runts-scraper/app/analyzer.py cai-runts-scraper/tests/test_ported_modules.py
git commit -m "feat: Fase 9 storia 2.2 - porting scraper.py/analyzer.py dal prototipo RUNTS"
```

---

### Task 3: `POST /scrape/runts-entity` — caso "non trovato"

**Files:**
- Modify: `cai-runts-scraper/app/main.py`
- Test: `cai-runts-scraper/tests/test_scrape_runts_entity.py`

**Interfaces:**
- Consumes: `app.scraper.run_scraper` (Task 2), mocked in tests.
- Produces: `POST /scrape/runts-entity?codice_fiscale=<CF>` → `{"found": false}` when `run_scraper` returns
  `([], {})` — consumed by PHP Story 3 (`CaiRuntsScraperClient::scrapeEntity()`).

- [ ] **Step 1: Write the failing test**

```python
from unittest.mock import AsyncMock, patch

from fastapi.testclient import TestClient

from app.main import app

client = TestClient(app)


def test_scrape_runts_entity_returns_found_false_when_no_results():
    with patch("app.main.run_scraper", new=AsyncMock(return_value=([], {}))) as mocked:
        response = client.post("/scrape/runts-entity", params={"codice_fiscale": "00000000000"})

    assert response.status_code == 200
    assert response.json() == {"found": False}
    mocked.assert_awaited_once()
    _, kwargs = mocked.call_args
    assert kwargs["codice_fiscale"] == "00000000000"
```

- [ ] **Step 2: Run test to verify it fails**

Run: `python3 -m pytest tests/test_scrape_runts_entity.py -v`
Expected: FAIL — `404 Not Found` (route doesn't exist yet) or `AttributeError: module 'app.main' has no
attribute 'run_scraper'` once the route exists but the import isn't there yet.

- [ ] **Step 3: Add the route (found:false branch only for now)**

Add to `cai-runts-scraper/app/main.py`:
```python
import tempfile
from pathlib import Path
from typing import Any

from app.scraper import run_scraper

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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `python3 -m pytest tests/test_scrape_runts_entity.py -v`
Expected: PASS (1 test).

- [ ] **Step 5: Commit**

```bash
git add cai-runts-scraper/app/main.py cai-runts-scraper/tests/test_scrape_runts_entity.py
git commit -m "feat: Fase 9 storia 2.3 - POST /scrape/runts-entity, caso non trovato"
```

---

### Task 4: `POST /scrape/runts-entity` — caso di successo

**Files:**
- Modify: `cai-runts-scraper/app/main.py`
- Modify: `cai-runts-scraper/tests/test_scrape_runts_entity.py`

**Interfaces:**
- Produces: the full success-path JSON shape from design doc §3.3 (with the `documento` field added to each
  document, per this plan's correction), consumed by PHP Story 3.

- [ ] **Step 1: Write the failing test**

Append to `cai-runts-scraper/tests/test_scrape_runts_entity.py`:
```python
def _fake_entity_with_children(tmp_path):
    pdf_path = tmp_path / "bilancio_2024.pdf"
    pdf_path.write_bytes(b"%PDF-1.4 fixture bilancio content\n")

    entity = {
        "id_runts": "12345",
        "codice_fiscale": "01234567890",
        "denominazione": "Sezione di Como",
        "forma_giuridica": "Associazione",
        "natura_giuridica": None,
        "sede_indirizzo": "Via Roma",
        "sede_civico": "1",
        "sede_comune": "Como",
        "sede_provincia": "CO",
        "sede_regione": "LOMBARDIA",
        "sede_cap": "22100",
        "data_iscrizione": "Iscritto il 24/02/2023",
        "sezione_registro": "APS",
        "settori_attivita": None,
        "rappresentante_legale": "Mario Rossi",
        "sito_web": "https://caicomo.it",
        "pec": "como@pec.cai.it",
        "url_dettaglio": "https://servizi.lavoro.gov.it/detail/12345",
        "raw_json": "x" * 10000,
        "atti_documenti": [
            {
                "documento": "Bilancio di esercizio 2024",
                "codice_pratica": "B00",
                "tipo": "bilancio_esercizio",
                "anno": 2024,
                "filename": "B00_2024_bilancio.pdf",
                "path": str(pdf_path),
                "size": pdf_path.stat().st_size,
                "hash_sha256": "abc123",
                "mime": "application/pdf",
                "skip_reason": None,
            },
            {
                "documento": "Statuto",
                "codice_pratica": "C02",
                "tipo": "statuto",
                "anno": None,
                "filename": None,
                "path": None,
                "size": None,
                "hash_sha256": None,
                "mime": None,
                "skip_reason": "no_button",
            },
        ],
        "cariche": [
            {
                "ruolo": "presidente",
                "nome": "Mario",
                "cognome": "Rossi",
                "codice_fiscale": "RSSMRA80A01H501X",
                "valid_from": "24/02/2023",
                "valid_to": None,
            }
        ],
    }
    return entity


def test_scrape_runts_entity_returns_metadata_board_members_and_documents(tmp_path):
    entity = _fake_entity_with_children(tmp_path)

    with patch("app.main.run_scraper", new=AsyncMock(return_value=([entity], {"attempt_1": 1}))):
        response = client.post("/scrape/runts-entity", params={"codice_fiscale": "01234567890"})

    assert response.status_code == 200
    body = response.json()

    assert body["found"] is True
    assert body["entity"]["id_runts"] == "12345"
    assert body["entity"]["denominazione"] == "Sezione di Como"
    assert "raw_json" not in body["entity"]
    assert "atti_documenti" not in body["entity"]
    assert "cariche" not in body["entity"]

    assert body["board_members"] == [
        {
            "ruolo": "presidente",
            "nome": "Mario",
            "cognome": "Rossi",
            "codice_fiscale": "RSSMRA80A01H501X",
            "valid_from": "24/02/2023",
            "valid_to": None,
        }
    ]

    assert len(body["documents"]) == 2
    downloaded = body["documents"][0]
    assert downloaded["documento"] == "Bilancio di esercizio 2024"
    assert downloaded["tipo"] == "bilancio_esercizio"
    assert downloaded["anno"] == 2024
    assert downloaded["skip_reason"] is None
    assert downloaded["content_base64"] is not None

    import base64

    assert base64.b64decode(downloaded["content_base64"]) == b"%PDF-1.4 fixture bilancio content\n"

    skipped = body["documents"][1]
    assert skipped["skip_reason"] == "no_button"
    assert skipped["content_base64"] is None
```

- [ ] **Step 2: Run test to verify it fails**

Run: `python3 -m pytest tests/test_scrape_runts_entity.py -v`
Expected: FAIL — `NotImplementedError` raised by the placeholder from Task 3.

- [ ] **Step 3: Implement the success branch**

Replace the `raise NotImplementedError` line in `cai-runts-scraper/app/main.py`'s `scrape_runts_entity` with:

```python
        import base64

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
```

Move the `import base64` to the top of the file alongside the other imports rather than inline (shown inline
above only to make the diff self-contained to read).

- [ ] **Step 4: Run test to verify it passes**

Run: `python3 -m pytest tests/test_scrape_runts_entity.py -v`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add cai-runts-scraper/app/main.py cai-runts-scraper/tests/test_scrape_runts_entity.py
git commit -m "feat: Fase 9 storia 2.4 - POST /scrape/runts-entity, caso di successo"
```

---

### Task 5: `POST /scrape/runts-entity` — fallimento dello scrape

**Files:**
- Modify: `cai-runts-scraper/app/main.py`
- Modify: `cai-runts-scraper/tests/test_scrape_runts_entity.py`

**Interfaces:**
- Produces: HTTP 502 with a JSON `detail` message when `run_scraper` raises — consumed by PHP Story 3's
  error-notification path.

- [ ] **Step 1: Write the failing test**

```python
def test_scrape_runts_entity_returns_502_when_scraper_raises():
    with patch("app.main.run_scraper", new=AsyncMock(side_effect=RuntimeError("Playwright timeout"))):
        response = client.post("/scrape/runts-entity", params={"codice_fiscale": "01234567890"})

    assert response.status_code == 502
    assert "Playwright timeout" in response.json()["detail"]
```

- [ ] **Step 2: Run test to verify it fails**

Run: `python3 -m pytest tests/test_scrape_runts_entity.py -v`
Expected: FAIL — FastAPI's default unhandled-exception behavior returns a bare 500 with no structured
`detail` matching this assertion (or the test framework surfaces the raw exception, depending on TestClient
configuration) — either way, not a clean 502.

- [ ] **Step 3: Wrap the scraper call in a try/except**

In `cai-runts-scraper/app/main.py`, wrap the `await run_scraper(...)` call:
```python
from fastapi import FastAPI, HTTPException

...

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
        ...
```

- [ ] **Step 4: Run test to verify it passes, and no regression on the earlier two tests**

Run: `python3 -m pytest tests/test_scrape_runts_entity.py -v`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add cai-runts-scraper/app/main.py cai-runts-scraper/tests/test_scrape_runts_entity.py
git commit -m "feat: Fase 9 storia 2.5 - POST /scrape/runts-entity, errore scraper diventa 502 esplicito"
```

---

### Task 6: `POST /analyze/bilancio`

**Files:**
- Modify: `cai-runts-scraper/app/main.py`
- Test: `cai-runts-scraper/tests/test_analyze_bilancio.py`

**Interfaces:**
- Consumes: `app.analyzer.extract_bilancio_pdf` (Task 2), mocked in tests.
- Produces: `POST /analyze/bilancio` (multipart `file`) → the 15 Italian-named financial fields + `raw_text` +
  `ocr` — consumed by PHP Story 3's `AnalyzeCaiFinancialStatementDocument` job.

- [ ] **Step 1: Write the failing test**

```python
from unittest.mock import patch

from fastapi.testclient import TestClient

from app.main import app

client = TestClient(app)


def _fake_analysis_result():
    result = {
        "oneri_a_interesse_generale": 1000.0,
        "oneri_b_attivita_diverse": None,
        "oneri_c_raccolta_fondi": None,
        "oneri_d_finanziarie_patrimoniali": None,
        "oneri_e_supporto_generale": 200.0,
        "totale_oneri": 1200.0,
        "proventi_a_interesse_generale": 1500.0,
        "proventi_b_attivita_diverse": None,
        "proventi_c_raccolta_fondi": None,
        "proventi_d_finanziarie_patrimoniali": None,
        "proventi_e_supporto_generale": None,
        "totale_proventi": 1500.0,
        "risultato_ante_imposte": 300.0,
        "imposte": 50.0,
        "risultato_esercizio": 250.0,
        "_raw_text": "Rendiconto Gestionale...",
        "_ocr": False,
    }
    return result


def test_analyze_bilancio_returns_financial_fields():
    with patch("app.main.extract_bilancio_pdf", return_value=_fake_analysis_result()) as mocked:
        response = client.post(
            "/analyze/bilancio",
            files={"file": ("bilancio.pdf", b"%PDF-1.4 fixture", "application/pdf")},
        )

    assert response.status_code == 200
    body = response.json()
    assert body["totale_oneri"] == 1200.0
    assert body["totale_proventi"] == 1500.0
    assert body["risultato_esercizio"] == 250.0
    assert body["ocr"] is False
    assert "Rendiconto Gestionale" in body["raw_text"]

    (call_path,), call_kwargs = mocked.call_args
    assert call_path.endswith(".pdf")
    assert call_kwargs.get("ocr_fallback") is False


def test_analyze_bilancio_never_fails_even_on_unreadable_pdf():
    all_none_result = {k: None for k in _fake_analysis_result() if not k.startswith("_")}
    all_none_result["_raw_text"] = ""
    all_none_result["_ocr"] = False

    with patch("app.main.extract_bilancio_pdf", return_value=all_none_result):
        response = client.post(
            "/analyze/bilancio",
            files={"file": ("empty.pdf", b"not a real pdf", "application/pdf")},
        )

    assert response.status_code == 200
    assert response.json()["totale_oneri"] is None
```

- [ ] **Step 2: Run test to verify it fails**

Run: `python3 -m pytest tests/test_analyze_bilancio.py -v`
Expected: FAIL — 404 (route doesn't exist yet).

- [ ] **Step 3: Add the route**

Add to `cai-runts-scraper/app/main.py`:
```python
from fastapi import File, UploadFile

from app.analyzer import extract_bilancio_pdf

_BILANCIO_FIELDS = (
    "oneri_a_interesse_generale", "oneri_b_attivita_diverse", "oneri_c_raccolta_fondi",
    "oneri_d_finanziarie_patrimoniali", "oneri_e_supporto_generale", "totale_oneri",
    "proventi_a_interesse_generale", "proventi_b_attivita_diverse", "proventi_c_raccolta_fondi",
    "proventi_d_finanziarie_patrimoniali", "proventi_e_supporto_generale", "totale_proventi",
    "risultato_ante_imposte", "imposte", "risultato_esercizio",
)


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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `python3 -m pytest tests/test_analyze_bilancio.py -v`
Expected: PASS (2 tests).

- [ ] **Step 5: Run the full Python test suite**

Run: `python3 -m pytest -v` (from `cai-runts-scraper/`)
Expected: PASS, all tests from Tasks 1-6 (health, ported modules, scrape-entity x3, analyze-bilancio x2 = 8
tests).

- [ ] **Step 6: Commit**

```bash
git add cai-runts-scraper/app/main.py cai-runts-scraper/tests/test_analyze_bilancio.py
git commit -m "feat: Fase 9 storia 2.6 - POST /analyze/bilancio"
```

---

### Task 7: Wiring in `docker-compose.yml` e verifica reale contro RUNTS

**Files:**
- Modify: `docker-compose.yml`
- Modify: `.env.example`

**Interfaces:**
- Produces: a `cai-runts-scraper` service reachable from the `app`/`queue` containers at
  `http://cai-runts-scraper:8000` — consumed by PHP Story 3's `CaiRuntsScraperClient` base URL config.

- [ ] **Step 1: Add the service to `docker-compose.yml`**

Read the existing `docker-compose.yml` first to match its exact style (service ordering, `env_file`
usage, network configuration already used by `app`/`web`/`queue`). Add a new service block:

```yaml
  cai-runts-scraper:
    build: ./cai-runts-scraper
    ports:
      - "8010:8000"
    restart: unless-stopped
```

Do not add `env_file: .env` unless a real need for an environment variable inside the service emerges during
this task (none is needed yet — the service is fully self-contained, no external config). Publishing port
`8010` on the host is for the manual verification in Step 3 below; PHP (Story 3) will reach it via the
internal Compose network hostname `cai-runts-scraper:8000`, never via `localhost:8010`.

- [ ] **Step 2: Bring the service up and verify the container-to-container hostname resolves**

Run:
```bash
docker compose up -d --build cai-runts-scraper
docker compose exec app curl -sf http://cai-runts-scraper:8000/health
```
Expected: `{"status":"ok"}` printed from inside the `app` container, confirming the two containers can reach
each other by service name over the Compose network — this is the exact path Story 3's PHP code will use.

- [ ] **Step 3: Manual, one-time verification against the real RUNTS site**

This step cannot be automated (no real RUNTS access in CI, and hitting the real government site from an
automated test suite would be inappropriate load/reliability risk). Perform it once, by hand, and record the
outcome in this plan's execution notes (or in `progress.txt` if run via the ralph loop):

```bash
# A codice fiscale known to exist in RUNTS (use a real CAI section's tax_code from the local dev DB,
# e.g. `docker compose exec app php artisan tinker --execute="echo \App\Domain\CaiDirectory\Models\CaiSection::whereNotNull('tax_code')->first()->tax_code;"`)
curl -sf -X POST "http://localhost:8010/scrape/runts-entity?codice_fiscale=<CF_REALE>" | python3 -m json.tool | head -50

# A syntactically valid but nonexistent codice fiscale
curl -sf -X POST "http://localhost:8010/scrape/runts-entity?codice_fiscale=00000000000" 
```
Expected: the first call returns `"found": true` with real entity data (or `"found": false"` if that
specific tax code genuinely has no RUNTS registration — not every `CaiSection` necessarily has one); the
second call returns `{"found": false}` cleanly, HTTP 200, never a 500/502 traceback. If the second call
instead returns a 502 or hangs, this is the exact "not found" ambiguity flagged as a risk in the design doc
§7 — investigate with `docker compose logs cai-runts-scraper` before considering this task done; do not
mark this task's regression checklist complete on a guess.

- [ ] **Step 4: Add `.env.example` documentation (informational only, no runtime env var needed yet)**

If Step 3 revealed no need for a configurable base URL override, skip this step — Story 3 owns the PHP-side
`CAI_RUNTS_SCRAPER_BASE_URL` env var and will add it to `.env.example` itself when it exists, not this plan.

- [ ] **Step 5: Commit**

```bash
docker compose down cai-runts-scraper
git add docker-compose.yml
git commit -m "feat: Fase 9 storia 2.7 - wiring cai-runts-scraper in docker-compose.yml locale"
```
