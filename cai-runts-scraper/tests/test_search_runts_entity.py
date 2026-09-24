from unittest.mock import AsyncMock, patch

from fastapi.testclient import TestClient

from app.main import app

client = TestClient(app)


def test_search_runts_entity_returns_found_true():
    with patch("app.main.check_entity_exists", new=AsyncMock(return_value=True)) as mocked:
        response = client.post("/search/runts-entity", params={"codice_fiscale": "01234567890"})

    assert response.status_code == 200
    assert response.json() == {"found": True}
    mocked.assert_awaited_once()
    _, kwargs = mocked.call_args
    assert kwargs["codice_fiscale"] == "01234567890"


def test_search_runts_entity_returns_found_false():
    with patch("app.main.check_entity_exists", new=AsyncMock(return_value=False)):
        response = client.post("/search/runts-entity", params={"codice_fiscale": "00000000000"})

    assert response.status_code == 200
    assert response.json() == {"found": False}


def test_search_runts_entity_returns_502_when_check_raises():
    with patch("app.main.check_entity_exists", new=AsyncMock(side_effect=RuntimeError("Playwright timeout"))):
        response = client.post("/search/runts-entity", params={"codice_fiscale": "01234567890"})

    assert response.status_code == 502
    assert "Playwright timeout" in response.json()["detail"]
