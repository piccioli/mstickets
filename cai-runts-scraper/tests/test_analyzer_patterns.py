import re

from app.analyzer import _PATTERNS, parse_italian_number

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


# Stesso layout, ma il valore dell'anno CORRENTE è un trattino "-" (zero/assente, la
# convenzione italiana di questi bilanci), con il valore dell'anno precedente reale subito
# dopo sulla stessa riga — verificato su un vero documento RUNTS reale (CAI Como, anno
# 2024): "Avanzo/Disavanzo d'esercizio (+/-) € - € 27.265".
SAMPLE_WITH_DASH_FOR_CURRENT_YEAR = """
                                                     Avanzo/Disavanzo d'esercizio (+/-) € - € 27.265
"""


def _match(field: str, text: str = SAMPLE_TWO_COLUMN_LAYOUT) -> str | None:
    for pattern in _PATTERNS[field]:
        m = re.search(pattern, text, re.IGNORECASE | re.DOTALL)
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


def test_risultato_esercizio_recognizes_a_dash_as_the_current_year_value_instead_of_skipping_to_the_next_year():
    # Prima del fix: il carattere "-" non è una cifra, quindi la classe di "salto"
    # [^\d\n]{0,10} lo attraversava e catturava per errore il valore dell'ANNO
    # PRECEDENTE (27.265) come se fosse quello corrente — un dato silenziosamente
    # SBAGLIATO, non solo mancante.
    assert _match("risultato_esercizio", SAMPLE_WITH_DASH_FOR_CURRENT_YEAR) == "-"


def test_parse_italian_number_treats_a_lone_dash_as_zero():
    assert parse_italian_number("-") == 0.0
    assert parse_italian_number(" - ") == 0.0
