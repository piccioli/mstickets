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
        "totale_attivo": 5000.0,
        "totale_passivo": 5000.0,
        "patrimonio_netto": 3000.0,
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
    assert body["totale_attivo"] == 5000.0
    assert body["totale_passivo"] == 5000.0
    assert body["patrimonio_netto"] == 3000.0
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
