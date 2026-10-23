-- Estrazione dei sentieri dal dump osm2cai (Fase 10.6, US-1051), eseguita da `bin/update-osm2cai-datapack` nel
-- database PostGIS temporaneo in cui è stato caricato il dump. Crea lo schema `osm2cai_extract` con le sole colonne
-- necessarie: lo script le esporta in CSV (`\copy osm2cai_extract.<tabella> TO STDOUT`) e `cai:import-osm2cai-extract`
-- le scrive nelle tabelle `snap_osm2cai_*` del datapack. Nessun dato personale (users, cas_users, ...) esce da qui.
--
-- Le colonne e il loro ordine devono restare uguali alle costanti `*_COLUMNS` di `CaiOsm2caiExtractWriter` (una per
-- tabella esportata): il comando rifiuta un CSV con un'intestazione diversa.

SET client_min_messages = warning;

DROP SCHEMA IF EXISTS osm2cai_extract CASCADE;
CREATE SCHEMA osm2cai_extract;

-- Testo ripulito: spazi esterni tolti, stringa vuota → NULL.
CREATE FUNCTION osm2cai_extract.text_or_null(value text) RETURNS text
    LANGUAGE sql IMMUTABLE AS $$ SELECT NULLIF(btrim(value), '') $$;

-- Numero intero da un testo della fonte ("640", "640.5", "1.100 m" no): solo valori numerici puri, arrotondati.
CREATE FUNCTION osm2cai_extract.int_or_null(value text) RETURNS integer
    LANGUAGE sql IMMUTABLE AS $$
        SELECT CASE WHEN btrim(value) ~ '^[0-9]+([.,][0-9]+)?$' THEN round(replace(btrim(value), ',', '.')::numeric)::integer END
    $$;

-- `hiking_routes.name` è quasi sempre un JSON traducibile (`{"it": "P69A - Partenza - Arrivo"}`): testo italiano, o il
-- primo valore, o il testo così com'è se non è JSON.
CREATE FUNCTION osm2cai_extract.translated(value text) RETURNS text
    LANGUAGE plpgsql IMMUTABLE AS $$
        DECLARE
            parsed jsonb;
        BEGIN
            IF btrim(value) NOT LIKE '{%' THEN
                RETURN osm2cai_extract.text_or_null(value);
            END IF;
            parsed := value::jsonb;
            RETURN osm2cai_extract.text_or_null(COALESCE(parsed ->> 'it', (SELECT v FROM jsonb_each_text(parsed) AS e(k, v) LIMIT 1)));
        EXCEPTION WHEN others THEN
            RETURN osm2cai_extract.text_or_null(value);
        END
    $$;

-- Sentieri con stato di accatastamento 1–4 non eliminati su OSM. Geometria 2D semplificata con tolleranza 0.00002°
-- (~2 m in latitudine, ~1,6 m in longitudine: circa un pixel a zoom 16 alle nostre latitudini) e 6 decimali. Misurato sul
-- dump di settembre 2026 (26.056 sentieri): GeoJSON da 200 MB (senza semplificazione) a 108 MB; 0.00001° → 140 MB,
-- 0.00005° → 67 MB ma con scarti di 2–3 pixel a zoom 16 sui tornanti.
CREATE TABLE osm2cai_extract.trails AS
SELECT
    hr.id,
    -- `hiking_routes.osmid` è vuoto nel dump: l'id della relazione OSM sta in `osmfeatures_data.properties.osm_id`.
    (SELECT CASE WHEN v ~ '^[0-9]+$' THEN v::bigint END
     FROM btrim(COALESCE(hr.osmid, hr.osmfeatures_data -> 'properties' ->> 'osm_id')) AS v) AS osmid,
    osm2cai_extract.text_or_null(hr.osmfeatures_data -> 'properties' ->> 'ref') AS ref,
    COALESCE(osm2cai_extract.translated(hr.name), osm2cai_extract.text_or_null(hr.osmfeatures_data -> 'properties' ->> 'name')) AS name,
    osm2cai_extract.text_or_null(hr.osmfeatures_data -> 'properties' ->> 'from') AS from_place,
    osm2cai_extract.text_or_null(hr.osmfeatures_data -> 'properties' ->> 'to') AS to_place,
    osm2cai_extract.text_or_null(upper(hr.osmfeatures_data -> 'properties' ->> 'cai_scale')) AS cai_scale,
    round((ST_Length(hr.geometry) / 1000)::numeric, 3) AS length_km,
    osm2cai_extract.int_or_null(hr.osmfeatures_data -> 'properties' ->> 'ascent') AS ascent,
    osm2cai_extract.int_or_null(hr.osmfeatures_data -> 'properties' ->> 'descent') AS descent,
    hr.osm2cai_status AS status,
    osm2cai_extract.text_or_null(hr.issues_status) AS issues_status,
    hr.issues_last_update,
    hr.validation_date,
    ST_AsGeoJSON(ST_SimplifyPreserveTopology(ST_Force2D(hr.geometry::geometry), 0.00002), 6) AS geometry_geojson
FROM public.hiking_routes hr
WHERE hr.osm2cai_status IN (1, 2, 3, 4)
  AND NOT hr.deleted_on_osm
ORDER BY hr.id;

-- Sezioni (club osm2cai, `cai_code` = `cai_sections.codice_cai`) dei sentieri estratti.
CREATE TABLE osm2cai_extract.trail_sections AS
SELECT DISTINCT hrc.hiking_route_id AS trail_id, btrim(c.cai_code) AS section_code
FROM public.hiking_route_club hrc
JOIN public.clubs c ON c.id = hrc.club_id
JOIN osm2cai_extract.trails t ON t.id = hrc.hiking_route_id
WHERE osm2cai_extract.text_or_null(c.cai_code) IS NOT NULL
ORDER BY 1, 2;

-- ---------------------------------------------------------------------------------------------------------------------
-- Territori (Fase 10.7, US-1061): regioni, province e settori del catasto con le statistiche dei sentieri estratti sopra.
-- Si esportano TUTTE le regioni della fonte (anche estere): `CaiOsm2caiExtractWriter` tiene solo le 20 italiane
-- abbinandole per nome all'enum `Region`, e scarta province, settori e statistiche delle regioni estere.
--
-- Regole di OSM2CAIReport (`app/src/Analysis/*`): km di un territorio = lunghezza dell'intersezione reale fra sentiero e
-- poligono (`ST_Length(ST_Intersection(hr.geometry::geometry, t.geometry)::geography)`), sentiero "presente" se almeno il
-- 50% della sua lunghezza ricade nel territorio. Differenza voluta: si contano solo i sentieri di `trails` (stato 1–4, non
-- `deleted_on_osm`), gli stessi dell'elenco nell'app; OSM2CAIReport include anche gli eliminati su OSM.
-- I poligoni si dividono con `ST_Subdivide` (la somma delle intersezioni con i pezzi è l'intersezione con il poligono):
-- con l'indice sui pezzi il calcolo di tutte le coppie sentiero–territorio scende da decine di minuti a meno di uno.

CREATE TABLE osm2cai_extract.regions AS
SELECT r.id, btrim(r.name) AS name
FROM public.regions r
WHERE osm2cai_extract.text_or_null(r.name) IS NOT NULL
ORDER BY r.id;

-- Sigla della provincia dai tag OSM (`ref`, o la parte finale di `ISO3166-2` "IT-SO").
CREATE TABLE osm2cai_extract.provinces AS
SELECT
    p.id,
    p.region_id,
    btrim(p.name) AS name,
    COALESCE(
        osm2cai_extract.text_or_null(p.osmfeatures_data -> 'properties' -> 'osm_tags' ->> 'ref'),
        osm2cai_extract.text_or_null(regexp_replace(p.osmfeatures_data -> 'properties' -> 'osm_tags' ->> 'ISO3166-2', '^[A-Z]+-', ''))
    ) AS code
FROM public.provinces p
WHERE p.region_id IS NOT NULL
ORDER BY p.id;

-- Settori (provincia tramite `areas`). `human_name` manca in molti settori: si ripiega sul nome tecnico (= `full_code`).
-- Geometria 2D semplificata a 0.0003° (~30 m, meno di mezzo pixel allo zoom 10, l'ultimo in cui la mappa mostra i settori)
-- e 5 decimali.
CREATE TABLE osm2cai_extract.sectors AS
SELECT
    s.id,
    a.province_id,
    btrim(s.code) AS code,
    btrim(s.full_code) AS full_code,
    COALESCE(osm2cai_extract.text_or_null(s.human_name), btrim(s.name)) AS name,
    ST_AsGeoJSON(ST_SimplifyPreserveTopology(ST_Force2D(s.geometry), 0.0003), 5) AS geometry_geojson
FROM public.sectors s
JOIN public.areas a ON a.id = s.area_id
WHERE a.province_id IS NOT NULL
ORDER BY s.id;

CREATE TABLE osm2cai_extract.territory_parts AS
SELECT 'region'::text AS territory_type, r.id AS territory_id, ST_Subdivide(r.geometry, 256) AS geom
FROM public.regions r JOIN osm2cai_extract.regions er ON er.id = r.id
UNION ALL
SELECT 'province', p.id, ST_Subdivide(p.geometry, 256)
FROM public.provinces p JOIN osm2cai_extract.provinces ep ON ep.id = p.id
UNION ALL
SELECT 'sector', s.id, ST_Subdivide(s.geometry, 256)
FROM public.sectors s JOIN osm2cai_extract.sectors es ON es.id = s.id;

CREATE INDEX ON osm2cai_extract.territory_parts USING gist (geom);

-- Una riga per coppia sentiero–territorio con intersezione non nulla: metri nel territorio e lunghezza del sentiero.
CREATE TABLE osm2cai_extract.trail_territory_m AS
SELECT
    tp.territory_type,
    tp.territory_id,
    hr.id AS trail_id,
    hr.osm2cai_status AS status,
    ST_Length(hr.geometry) AS length_m,
    SUM(ST_Length(ST_Intersection(hr.geometry::geometry, tp.geom)::geography)) AS inside_m
FROM osm2cai_extract.trails t
JOIN public.hiking_routes hr ON hr.id = t.id
JOIN osm2cai_extract.territory_parts tp ON ST_Intersects(hr.geometry::geometry, tp.geom)
GROUP BY tp.territory_type, tp.territory_id, hr.id, hr.osm2cai_status, hr.geometry
HAVING SUM(ST_Length(ST_Intersection(hr.geometry::geometry, tp.geom)::geography)) > 0;

-- Km intersecati e sentieri presenti (≥ 50%) per territorio e stato.
CREATE TABLE osm2cai_extract.territory_km AS
SELECT
    territory_type,
    territory_id,
    status,
    round((SUM(inside_m) / 1000)::numeric, 3) AS km,
    COUNT(*) FILTER (WHERE inside_m >= 0.5 * length_m) AS present_routes
FROM osm2cai_extract.trail_territory_m
GROUP BY territory_type, territory_id, status
ORDER BY 1, 2, 3;

-- Legame sentiero–territorio con la percentuale della lunghezza nel territorio, dalle stesse intersezioni di
-- `territory_km` (così "presente" nell'elenco e nei conteggi coincide; le `percentage` di `hiking_route_*` della fonte
-- sono precalcolate da osm2cai e possono divergere). Il livello è il più profondo id valorizzato: riga di regione
-- (solo `region_id`), di provincia (`region_id` + `province_id`), di settore (tutti e tre).
CREATE TABLE osm2cai_extract.trail_territory AS
SELECT
    m.trail_id,
    COALESCE(r.id, p.region_id, sp.region_id) AS region_id,
    COALESCE(p.id, sp.id) AS province_id,
    s.id AS sector_id,
    round((100 * m.inside_m / NULLIF(m.length_m, 0))::numeric, 2) AS percentage
FROM osm2cai_extract.trail_territory_m m
LEFT JOIN osm2cai_extract.regions r ON m.territory_type = 'region' AND r.id = m.territory_id
LEFT JOIN osm2cai_extract.provinces p ON m.territory_type = 'province' AND p.id = m.territory_id
LEFT JOIN osm2cai_extract.sectors s ON m.territory_type = 'sector' AND s.id = m.territory_id
LEFT JOIN osm2cai_extract.provinces sp ON sp.id = s.province_id
ORDER BY 1, 2, 3 NULLS FIRST, 4 NULLS FIRST;
