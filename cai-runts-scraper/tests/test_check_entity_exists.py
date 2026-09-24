import asyncio
import inspect
from unittest.mock import AsyncMock, MagicMock, patch

from app.scraper import check_entity_exists


def _mock_playwright():
    page = AsyncMock()
    context = AsyncMock()
    context.new_page = AsyncMock(return_value=page)
    browser = AsyncMock()
    browser.new_context = AsyncMock(return_value=context)
    pw_instance = AsyncMock()
    pw_instance.chromium.launch = AsyncMock(return_value=browser)

    # `async_playwright()` is itself a plain (sync) call that returns an async context
    # manager — MagicMock (not AsyncMock) for the outer callable, AsyncMock only for
    # __aenter__/__aexit__ on what it returns.
    context_manager = MagicMock()
    context_manager.__aenter__ = AsyncMock(return_value=pw_instance)
    context_manager.__aexit__ = AsyncMock(return_value=False)

    mocked_playwright = MagicMock(return_value=context_manager)
    return mocked_playwright


def test_check_entity_exists_is_an_async_function_accepting_codice_fiscale():
    assert inspect.iscoroutinefunction(check_entity_exists)
    params = inspect.signature(check_entity_exists).parameters
    assert "codice_fiscale" in params


def test_check_entity_exists_returns_true_when_results_found():
    with patch("app.scraper.async_playwright", new=_mock_playwright()), \
         patch("app.scraper.search_enti", new=AsyncMock()), \
         patch("app.scraper._get_total_items", new=AsyncMock(return_value=3)):
        found = asyncio.run(check_entity_exists("01234567890"))

    assert found is True


def test_check_entity_exists_returns_false_when_no_results():
    with patch("app.scraper.async_playwright", new=_mock_playwright()), \
         patch("app.scraper.search_enti", new=AsyncMock()), \
         patch("app.scraper._get_total_items", new=AsyncMock(return_value=0)):
        found = asyncio.run(check_entity_exists("00000000000"))

    assert found is False


def test_check_entity_exists_retries_up_to_3_times_then_raises():
    with patch("app.scraper.async_playwright", new=_mock_playwright()), \
         patch("app.scraper.search_enti", new=AsyncMock(side_effect=RuntimeError("timeout"))), \
         patch("app.scraper.asyncio.sleep", new=AsyncMock()) as mocked_sleep:
        try:
            asyncio.run(check_entity_exists("01234567890"))
            raised = False
        except RuntimeError:
            raised = True

    assert raised is True
    assert mocked_sleep.await_count == 2
