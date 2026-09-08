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


def test_scrape_runts_entity_returns_502_when_scraper_raises():
    with patch("app.main.run_scraper", new=AsyncMock(side_effect=RuntimeError("Playwright timeout"))):
        response = client.post("/scrape/runts-entity", params={"codice_fiscale": "01234567890"})

    assert response.status_code == 502
    assert "Playwright timeout" in response.json()["detail"]
