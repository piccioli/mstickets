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
