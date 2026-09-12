import re

from app.analyzer import _PATTERNS

# Estratto rappresentativo (non un documento reale scaricato, solo la STRUTTURA che ha
# causato il mismatch, verificata su un vero "Mod. B – Rendiconto Gestionale" RUNTS reale
# 2026-09-12): layout a due colonne (oneri a sinistra, proventi a destra) dove
# pdfplumber estrae l'etichetta del totale su una riga e i 4 importi (oneri anno
# corrente/precedente, proventi anno corrente/precedente) sulla riga successiva — E
# importi interi, MAI col formato ",00" atteso dai pattern "Mod.B standard" già
# esistenti. "Imposte" è seguito da un simbolo "€" prima del numero, non da uno spazio
# come assunto dal pattern originale.
SAMPLE_TWO_COLUMN_LAYOUT = """
                    Totale oneri e costi                 Totale proventi e ricavi
                               € 172.413 € 183.805                    € 200.664 € 187.676
                                          Avanzo/Disavanzo d'esercizio prima delle imposte (+/-) € 28.251 € 3.871
                                                                  Imposte € 3.842 € 3.871
                                                     Avanzo/Disavanzo d'esercizio (+/-) € 24.409 € -
"""


def _match(field: str) -> str | None:
    for pattern in _PATTERNS[field]:
        m = re.search(pattern, SAMPLE_TWO_COLUMN_LAYOUT, re.IGNORECASE | re.DOTALL)
        if m:
            return m.group(1).strip()
    return None


def test_totale_oneri_matches_across_the_line_break_in_a_two_column_layout():
    assert _match("totale_oneri") == "172.413"


def test_totale_proventi_matches_the_third_number_on_the_value_line():
    assert _match("totale_proventi") == "200.664"


def test_imposte_matches_when_a_euro_sign_separates_the_label_from_the_value():
    assert _match("imposte") == "3.842"


def test_risultato_fields_already_matched_before_this_fix_and_still_do():
    assert _match("risultato_ante_imposte") == "28.251"
    assert _match("risultato_esercizio") == "24.409"
