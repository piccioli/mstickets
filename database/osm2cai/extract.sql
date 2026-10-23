-- Estrazione dei sentieri dal dump osm2cai (Fase 10.6, US-1051), eseguita da `bin/update-osm2cai-datapack` nel
-- database PostGIS temporaneo in cui è stato caricato il dump. Crea lo schema `osm2cai_extract` con le sole colonne
-- necessarie: lo script le esporta in CSV (`\copy osm2cai_extract.<tabella> TO STDOUT`) e `cai:import-osm2cai-extract`
-- le scrive nelle tabelle `snap_osm2cai_*` del datapack. Nessun dato personale (users, cas_users, ...) esce da qui.
--
-- Le colonne e il loro ordine devono restare uguali a `CaiOsm2caiExtractWriter::TRAIL_COLUMNS` /
-- `TRAIL_SECTION_COLUMNS`: il comando rifiuta un CSV con un'intestazione diversa.

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
