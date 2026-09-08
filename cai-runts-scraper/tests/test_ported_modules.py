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
