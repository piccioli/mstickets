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


# --- Stato patrimoniale (Mod. A) -----------------------------------------------------
# Testi sintetici che riproducono la STRUTTURA di righe osservata su PDF reali.

SAMPLE_MOD_A_STANDARD = """
C) Attivo circolante
Totale attivo circolante                                   331.632
Totale attivo                                                  331.632
A) Patrimonio netto
Totale patrimonio netto                             83.702
Totale passivo                                               331.632,00
"""

SAMPLE_MOD_A_TWO_COLUMNS = """
TOTALE ATTIVO                               44.009,72 TOTALE PASSIVO                        44.009,72
"""

SAMPLE_MOD_A_APOSTROPHE = """
Totale Attivo                                   1'684.079 642.552
TOTALE PATRIMONIO NETTO                         1'570.506 559.481
Totale Passivo                                  1'684.079 642.552
"""

SAMPLE_NO_BALANCE_SHEET = """
Totale oneri e costi   172.413
Totale proventi e ricavi   200.664
"""


def _value(field: str, text: str) -> float | None:
    raw = _match(field, text)
    return parse_italian_number(raw) if raw is not None else None


def test_balance_sheet_standard_mod_a_skips_attivo_circolante_subtotal():
    assert _value("totale_attivo", SAMPLE_MOD_A_STANDARD) == 331632.0
    assert _value("totale_passivo", SAMPLE_MOD_A_STANDARD) == 331632.0
    assert _value("patrimonio_netto", SAMPLE_MOD_A_STANDARD) == 83702.0


def test_balance_sheet_two_column_layout_matches_attivo_and_passivo_on_the_same_line():
    assert _value("totale_attivo", SAMPLE_MOD_A_TWO_COLUMNS) == 44009.72
    assert _value("totale_passivo", SAMPLE_MOD_A_TWO_COLUMNS) == 44009.72


def test_balance_sheet_numbers_with_apostrophe_thousands_separator():
    assert _value("totale_attivo", SAMPLE_MOD_A_APOSTROPHE) == 1684079.0
    assert _value("patrimonio_netto", SAMPLE_MOD_A_APOSTROPHE) == 1570506.0
    assert _value("totale_passivo", SAMPLE_MOD_A_APOSTROPHE) == 1684079.0


def test_text_without_balance_sheet_yields_none_for_all_three_fields():
    for field in ("totale_attivo", "totale_passivo", "patrimonio_netto"):
        assert _match(field, SAMPLE_NO_BALANCE_SHEET) is None


def test_balance_sheet_label_variants_with_colon_parentheses_and_stato_patrimoniale_prefix():
    text = """
TOTALE ATTIVITA:               193.532,30 204.015,42
TOTALE PASSIVITA:              193.532,30 204.015,42
TOTALE ATTIVO (A+B+C+D)         21.055
TOTALE STATO PATRIMONIALE - PASSIVO                   7.282.848   7.317.124
"""
    assert _value("totale_attivo", text) == 193532.30
    assert _value("totale_passivo", text) == 193532.30
    assert _value("totale_attivo", "TOTALE ATTIVO (A+B+C+D)         21.055") == 21055.0
    assert _value("totale_passivo", "TOTALE STATO PATRIMONIALE - PASSIVO   7.282.848   7.317.124") == 7282848.0
