{{-- Mappa con cartografia CAI (US-1033), unica per rifugi, sentieri e tappe SICAI. Leaflet 1.9.4 da CDN, caricato una
     sola volta per pagina (@once); tile CAI zoom 6–16.
     Props:
     - `name`: identificativo della mappa nella pagina, ripetuto negli eventi (più mappe nella stessa pagina);
     - `markers`: lista di CaiMapMarker (o array con `id`, `lat`, `lng`, `label`, `color`, `letter`, `popupHtml`);
     - `lines`: lista di CaiMapLine (o array con `id`, `geojson`, `color`, `label`, `weight`, `popupHtml`); sentiero →
       `CaiMapLine::forTrail()` (US-1053). Disegnate su canvas (centinaia di linee senza blocchi) con un margine di clic
       di qualche pixel; la linea evidenziata (hover) o selezionata diventa più spessa con un bordo bianco e sta sopra
       le altre, che con una linea selezionata si attenuano, come nel design "Sentieri di Sezione";
     - `selected`: id del marker o della linea selezionati all'apertura (popup aperto);
     - `height`: altezza CSS del riquadro;
     - `cluster` (US-1042): raggruppa i marker vicini sotto lo zoom 12 in un cerchio con il numero (tooltip
       "N {clusterNoun} · clic per ingrandire", clic → zoom +2 sul gruppo), con leaflet.markercluster 1.5.3 caricato
       una sola volta e solo se almeno una mappa della pagina lo chiede. Senza, il comportamento è quello della 10.4;
     - `clusterNoun`: sostantivo plurale del tooltip del gruppo (default "rifugi");
     - `fitMaxZoom`: zoom massimo dell'inquadratura iniziale (default 13; la scheda del sentiero usa 15);
     - `areas` (US-1064): lista di CaiMapArea (o array con `id`, `geojson`, `color`, `empty`, `label`, `popupHtml`), poligoni
       sotto le linee sullo stesso canvas; settore → `CaiMapArea::forSector()`. Sotto `linesMinZoom` (o sempre, senza
       `linesUrl`) pieni (opacità 0,74, bordo bianco), sopra quasi trasparenti (0,06, bordo grigio); vuoti rosa a bordo
       rosso tratteggiato; etichetta permanente da `areaLabelsMinZoom` (9). Nel riquadro, `data-cai-map-area-action`
       `zoom` inquadra l'area (almeno a `linesMinZoom` se non vuota), `list` emette `cai-map-area-listed`;
     - `linesUrl` (US-1064): da `linesMinZoom` (11) la mappa chiede a questo URL le linee dell'area visibile
       (`?bbox=ovest,sud,est,nord&zoom=z`, risposta `{ lines, total, limit, truncated }`, vedi
       CaiRegionalGroupTrailsMapController), con una cache per riquadro e la richiesta precedente annullata; sotto lo
       zoom le linee si tolgono. Stato reattivo per gli slot: `mode` (`areas`/`lines`), `lineCount` (linee in vista),
       `lineTotal`, `lineLimit`, `linesTruncated`, `linesLoading`, `linesFailed`; azioni `showLines()`, `showAreas()` (= `resetView()`);
     - slot `overlay` (sopra il riquadro, posizionato dallo slot) e `footer` (sotto), dentro lo scope Alpine della mappa;
     - `attribution`: testo dell'attribuzione (default "© CAI · © OpenStreetMap contributors").
     Vista: `fitBounds` con margine (zoom massimo `fitMaxZoom`) sui marker e le linee dentro l'Italia, un solo marker → zoom 13,
     niente da mostrare → Italia.
     Eventi (bubbling fino a `window`, `detail` = `{ map: name, id }`); marker e linee condividono lo spazio degli id:
     - emessi: `cai-map-marker-selected` / `cai-map-line-selected` / `cai-map-area-selected` (clic sul marker / sulla
       linea / sull'area; `id` null alla chiusura del popup), `cai-map-area-listed` ("Elenco sentieri →" di un'area), `cai-map-marker-hovered` / `cai-map-line-hovered` (`id` null all'uscita);
     - ascoltati su `window`: `cai-map-highlight` (marker ingrandito o linea evidenziata, es. hover sulla riga
       dell'elenco) e `cai-map-select` (selezione con popup aperto, es. clic sulla riga; una linea fuori vista viene
       inquadrata, un'area viene inquadrata come con "Zooma sul settore").
     Dopo un aggiornamento Livewire (filtri) il riquadro Leaflet resta (`wire:ignore`), mentre il contenitore dei dati ha
     un `wire:key` con l'hash dei dati: cambiando i dati Livewire lo sostituisce e il suo `x-init` ridisegna la mappa.
     Il componente va reso sempre (anche senza marker), non dentro un `@if` che compare solo dopo un aggiornamento:
     lo script del @once non verrebbe eseguito. Stili in resources/css/filament/admin/cai-map.css. --}}
@props([
    'name',
    'markers' => [],
    'lines' => [],
    'selected' => null,
    'height' => '440px',
    'cluster' => false,
    'clusterNoun' => 'rifugi',
    'fitMaxZoom' => 13,
    'areas' => [],
    'linesUrl' => null,
    'linesMinZoom' => 11,
    'areaLabelsMinZoom' => 9,
    'attribution' => '© CAI · © OpenStreetMap contributors',
])
@use('App\Domain\CaiDirectory\Support\CaiMapArea')
@use('App\Domain\CaiDirectory\Support\CaiMapLine')
@use('App\Domain\CaiDirectory\Support\CaiMapMarker')
@php
    $payload = [
        'markers' => collect($markers)
            ->map(fn (CaiMapMarker|array $marker): array => $marker instanceof CaiMapMarker ? $marker->toArray() : $marker)
            ->values()
            ->all(),
        'lines' => collect($lines)
            ->map(fn (CaiMapLine|array $line): array => $line instanceof CaiMapLine ? $line->toArray() : $line)
            ->values()
            ->all(),
    ];
    // Solo se ci sono aree: le mappe di rifugi e sentieri (10.4–10.6) mantengono payload e `wire:key` di prima.
    if (collect($areas)->isNotEmpty()) {
        $payload['areas'] = collect($areas)
            ->map(fn (CaiMapArea|array $area): array => $area instanceof CaiMapArea ? $area->toArray() : $area)
            ->values()
            ->all();
    }
    $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $config = [
        'name' => $name,
        'selected' => $selected,
        'tiles' => 'https://r3-it.storage.cloud.it/tiles/{z}/{x}/{y}.png',
        'attribution' => $attribution,
        'minZoom' => 6,
        'maxZoom' => 16,
        'fitMaxZoom' => (int) $fitMaxZoom,
        'singleZoom' => 13,
        'cluster' => (bool) $cluster,
        'clusterNoun' => $clusterNoun,
        // Come nel design: sotto lo zoom 12 i marker vicini si raggruppano, il clic sul gruppo ingrandisce di 2.
        'clusterUntilZoom' => 11,
        'clusterRadius' => 40,
        'clusterZoomStep' => 2,
        // Linee (US-1053), misure del design: a riposo lo spessore della linea, hover 5 px su bordo bianco di 8,
        // selezionata 5,5 px su bordo di 10, le altre a metà opacità mentre una linea è selezionata. Margine di clic
        // del renderer canvas in pixel oltre metà spessore (≈ 14 px di area cliccabile come nel design).
        'lineHoverWeight' => 5,
        'lineSelectedWeight' => 5.5,
        'lineHoverCasing' => 8,
        'lineSelectedCasing' => 10,
        'lineDimmedOpacity' => 0.5,
        'lineTolerance' => 6,
        // Aree (US-1064), misure del design "Sentieri del GR": piene sotto lo zoom dei sentieri, quasi trasparenti sopra.
        'linesUrl' => $linesUrl,
        'linesMinZoom' => (int) $linesMinZoom,
        'linesDebounce' => 250,
        'linesPad' => 0.1,
        'linesCacheSize' => 40,
        'areaLabelsMinZoom' => (int) $areaLabelsMinZoom,
        'areaFillOpacity' => ['low' => 0.74, 'high' => 0.06, 'emptyLow' => 0.85, 'emptyHigh' => 0.4],
        'areaStroke' => ['low' => '#ffffff', 'high' => '#5b6475', 'selected' => '#1f2633', 'empty' => CaiMapArea::EMPTY_STROKE],
        'areaWeight' => ['low' => 0.8, 'high' => 1, 'selected' => 3, 'empty' => 1.5],
        'areaEmptyFill' => CaiMapArea::EMPTY_FILL,
        'areaEmptyDash' => '5 3',
        'areaZoomMax' => 14,
    ];
@endphp
@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" data-navigate-once />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" data-navigate-once></script>
    <script data-navigate-once>
        window.caiMapComponent ??= (config) => {
            // Oggetti Leaflet fuori dallo stato Alpine: un Proxy reattivo attorno alla mappa la romperebbe.
            let map = null;
            let markerLayer = null;
            let clusterLayer = null;
            let lineLayer = null;
            let lineRenderer = null;
            let areaLayer = null;
            let linesTimer = null;
            let linesRequest = null;
            let remoteKey = null;
            let casingColor = '#ffffff';
            let resizeObserver = null;
            let payload = { markers: [], lines: [], areas: [] };
            let selected = config.selected ?? null;
            let hovered = null;
            const markers = new Map();
            const lines = new Map();
            const areas = new Map();
            const remoteIds = new Set();
            const linesCache = new Map();
            const listeners = [];
            const italy = [[35, 6], [47.5, 19]];
            const key = (id) => (id === null || id === undefined ? null : String(id));
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => `&#${c.charCodeAt(0)};`);
            const safeColor = (value) => (/^#[0-9a-f]{3,8}$/i.test(String(value)) ? value : '#5b6475');

            const icon = (marker) => {
                const active = key(marker.id) === key(selected) || key(marker.id) === key(hovered);
                const size = active ? 34 : 24;

                return L.divIcon({
                    className: 'ms-cai-map__marker-icon',
                    html: `<span class="ms-cai-map__marker${active ? ' is-active' : ''}" style="--ms-cai-map-marker-color: ${safeColor(marker.color)}">${escapeHtml(marker.letter)}</span>`,
                    iconSize: [size, size],
                    iconAnchor: [size / 2, size / 2],
                    popupAnchor: [0, -(size / 2) - 4],
                });
            };

            const lineStyle = (line) => {
                const isSelected = key(line.id) === key(selected);
                const isHovered = key(line.id) === key(hovered);
                const dimmed = lines.has(key(selected)) && ! isSelected && ! isHovered;

                return {
                    color: safeColor(line.color),
                    weight: isSelected ? config.lineSelectedWeight : isHovered ? config.lineHoverWeight : (line.weight ?? 4),
                    opacity: dimmed ? config.lineDimmedOpacity : 1,
                    lineCap: 'round',
                    lineJoin: 'round',
                };
            };

            // Bordo bianco sotto la linea evidenziata o selezionata: un secondo tracciato non cliccabile, creato solo
            // quando serve (con centinaia di linee non si raddoppiano i tracciati).
            const refreshLine = (entry) => {
                const isSelected = key(entry.line.id) === key(selected);
                const casing = isSelected ? config.lineSelectedCasing : key(entry.line.id) === key(hovered) ? config.lineHoverCasing : 0;
                entry.layer.setStyle(lineStyle(entry.line));
                entry.casing?.remove();
                entry.casing = null;

                if (casing > 0) {
                    entry.casing = L.geoJSON(entry.line.geojson, {
                        renderer: lineRenderer,
                        interactive: false,
                        style: { color: casingColor, weight: casing, opacity: 1, lineCap: 'round', lineJoin: 'round' },
                    }).addTo(lineLayer);
                }
            };

            // Ordine di disegno: linea evidenziata sopra le altre, selezionata sopra tutte (ciascuna sopra il suo bordo).
            const raiseLines = () => {
                [hovered, selected].forEach((id) => {
                    const entry = lines.get(key(id));
                    entry?.casing?.bringToFront();
                    entry?.layer.bringToFront();
                });
            };

            // Sotto lo zoom dei sentieri (o sempre, senza `linesUrl`) le aree sono piene; sopra fanno da sfondo alle linee.
            const areasLow = () => ! config.linesUrl || ! map || map.getZoom() < config.linesMinZoom;

            const areaStyle = (area) => {
                const isSelected = key(area.id) === key(selected) || key(area.id) === key(hovered);
                const low = areasLow();
                const level = isSelected ? 'selected' : area.empty ? 'empty' : low ? 'low' : 'high';

                return {
                    color: config.areaStroke[level],
                    weight: config.areaWeight[level],
                    opacity: 1,
                    dashArray: area.empty ? config.areaEmptyDash : null,
                    fillColor: area.empty ? config.areaEmptyFill : safeColor(area.color),
                    fillOpacity: config.areaFillOpacity[area.empty ? (low ? 'emptyLow' : 'emptyHigh') : (low ? 'low' : 'high')],
                    lineJoin: 'round',
                };
            };

            // Area selezionata sopra le altre, ma sempre sotto le linee (stesso canvas: conta l'ordine di disegno).
            const refreshArea = (entry, raise = false) => {
                entry.layer.setStyle(areaStyle(entry.area));

                if (raise) {
                    entry.layer.bringToFront();
                    lineLayer.eachLayer((layer) => layer.bringToFront());
                    raiseLines();
                }
            };

            const refresh = (id) => {
                const entry = markers.get(key(id));

                if (entry) {
                    const active = key(id) === key(selected) || key(id) === key(hovered);
                    entry.layer.setIcon(icon(entry.marker));
                    entry.layer.setZIndexOffset(active ? 1000 : 0);
                }

                const line = lines.get(key(id));

                if (line) {
                    refreshLine(line);
                    raiseLines();
                }

                const area = areas.get(key(id));

                if (area) {
                    refreshArea(area, key(id) === key(selected) || key(id) === key(hovered));
                }
            };

            return {
                // Stato reattivo per gli slot `overlay`/`footer` (US-1064).
                mode: config.linesUrl ? 'areas' : null,
                lineCount: 0,
                lineTotal: 0,
                lineLimit: 0,
                linesTruncated: false,
                linesLoading: false,
                linesFailed: false,

                init() {
                    const ready = () => window.L && (! config.cluster || L.markerClusterGroup);
                    const boot = () => (ready() ? this.boot() : setTimeout(boot, 50));
                    boot();
                },

                boot() {
                    if (map) {
                        return;
                    }

                    map = L.map(this.$refs.canvas, { zoomControl: false, minZoom: config.minZoom, maxZoom: config.maxZoom });
                    L.control.zoom({ position: 'topright' }).addTo(map);
                    L.tileLayer(config.tiles, {
                        minZoom: config.minZoom,
                        maxZoom: config.maxZoom,
                        attribution: config.attribution,
                    }).addTo(map);
                    map.attributionControl.setPrefix(false);
                    lineRenderer = L.canvas({ tolerance: config.lineTolerance });
                    casingColor = getComputedStyle(this.$el).getPropertyValue('--ms-map-line-casing').trim() || casingColor;
                    areaLayer = L.featureGroup().addTo(map);
                    lineLayer = L.featureGroup().addTo(map);
                    markerLayer = config.cluster ? this.clusterGroup() : L.featureGroup();
                    markerLayer.addTo(map);
                    map.on('popupclose', (event) => {
                        if (event.popup._caiId !== undefined && key(event.popup._caiId) === key(selected)) {
                            this.select(null, true, event.popup._caiKind);
                        }
                    });
                    // Azioni del riquadro di un'area ("Zooma sul settore", "Elenco sentieri →").
                    map.on('popupopen', (event) => {
                        if (event.popup._caiKind !== 'area') {
                            return;
                        }

                        const id = event.popup._caiId;
                        event.popup.getElement()?.querySelectorAll('[data-cai-map-area-action]').forEach((button) => {
                            button.onclick = (clickEvent) => {
                                clickEvent.preventDefault();

                                if (button.dataset.caiMapAreaAction === 'zoom') {
                                    this.zoomToArea(id);
                                } else {
                                    this.$dispatch('cai-map-area-listed', { map: config.name, id });
                                }
                            };
                        });
                    });
                    map.on('zoomend', () => {
                        areas.forEach((entry) => refreshArea(entry));
                        this.$refs.canvas.classList.toggle('is-area-labels-hidden', map.getZoom() < config.areaLabelsMinZoom);
                    });
                    map.on('moveend', () => this.scheduleLines());

                    const on = (type, handler) => {
                        const listener = (event) => {
                            if (event.detail?.map === config.name) {
                                handler(event.detail.id ?? null);
                            }
                        };
                        window.addEventListener(type, listener);
                        listeners.push([type, listener]);
                    };
                    on('cai-map-highlight', (id) => this.highlight(id));
                    on('cai-map-select', (id) => this.select(id, false));

                    // Un riquadro nascosto o ridimensionato (layout mobile/desktop) va rimisurato.
                    resizeObserver = new ResizeObserver(() => map.invalidateSize());
                    resizeObserver.observe(this.$refs.canvas);

                    this.render();
                },

                // Gruppo di marker del design: cerchio verde con il numero, diametro 30–48 px secondo la quantità.
                clusterGroup() {
                    clusterLayer = L.markerClusterGroup({
                        maxClusterRadius: config.clusterRadius,
                        disableClusteringAtZoom: config.clusterUntilZoom + 1,
                        showCoverageOnHover: false,
                        spiderfyOnMaxZoom: false,
                        zoomToBoundsOnClick: false,
                        iconCreateFunction: (cluster) => {
                            const count = cluster.getChildCount();
                            const size = Math.round(30 + Math.min(18, Math.log2(count) * 4));

                            return L.divIcon({
                                className: 'ms-cai-map__cluster-icon',
                                html: `<span class="ms-cai-map__cluster" title="${escapeHtml(`${count} ${config.clusterNoun} · clic per ingrandire`)}">${count}</span>`,
                                iconSize: [size, size],
                                iconAnchor: [size / 2, size / 2],
                            });
                        },
                    });
                    clusterLayer.on('clusterclick', (event) => {
                        map.setView(event.layer.getLatLng(), Math.min(config.maxZoom, map.getZoom() + config.clusterZoomStep));
                    });

                    return clusterLayer;
                },

                load(data) {
                    payload = data;

                    if (map) {
                        this.render();
                    }
                },

                addLine(line, remote = false) {
                    const layer = L.geoJSON(line.geojson, { renderer: lineRenderer, style: lineStyle(line) });
                    layer._caiRemote = remote;

                    if (line.popupHtml) {
                        layer.bindPopup(line.popupHtml, { className: 'ms-cai-map__popup ms-cai-map__popup--line', maxWidth: 260, minWidth: 260, autoPanPadding: [16, 16] });
                        layer.getPopup()._caiId = line.id;
                        layer.getPopup()._caiKind = 'line';
                    } else if (line.label) {
                        layer.bindTooltip(escapeHtml(line.label), { sticky: true });
                    }

                    layer.on('click', () => this.select(line.id, true, 'line'));
                    layer.on('mouseover', () => this.hover(line.id, 'line'));
                    layer.on('mouseout', () => this.hover(null, 'line'));
                    layer.addTo(lineLayer);
                    lines.set(key(line.id), { line, layer, casing: null });
                },

                removeLine(id) {
                    const entry = lines.get(key(id));
                    entry?.casing?.remove();
                    entry?.layer.remove();
                    lines.delete(key(id));
                },

                addArea(area) {
                    // Stesso canvas delle linee, aggiunto prima: le linee restano sopra e vincono il clic.
                    const layer = L.geoJSON(area.geojson, { renderer: lineRenderer, style: areaStyle(area) });

                    if (area.popupHtml) {
                        layer.bindPopup(area.popupHtml, { className: 'ms-cai-map__popup ms-cai-map__popup--area', maxWidth: 280, minWidth: 280, autoPanPadding: [16, 16] });
                        layer.getPopup()._caiId = area.id;
                        layer.getPopup()._caiKind = 'area';
                    }

                    if (area.label) {
                        layer.bindTooltip(escapeHtml(area.label), { permanent: true, direction: 'center', className: 'ms-cai-map__area-label', interactive: false });
                    }

                    layer.on('click', () => this.select(area.id, true, 'area'));
                    layer.addTo(areaLayer);
                    areas.set(key(area.id), { area, layer });
                },

                render() {
                    markerLayer.clearLayers();
                    lineLayer.clearLayers();
                    areaLayer.clearLayers();
                    markers.clear();
                    lines.clear();
                    areas.clear();
                    remoteIds.clear();
                    remoteKey = null;

                    (payload.areas ?? []).forEach((area) => this.addArea(area));
                    (payload.lines ?? []).forEach((line) => this.addLine(line));

                    (payload.markers ?? []).forEach((marker) => {
                        const layer = L.marker([marker.lat, marker.lng], { icon: icon(marker), title: marker.label, riseOnHover: true });

                        if (marker.popupHtml) {
                            layer.bindPopup(marker.popupHtml, { className: 'ms-cai-map__popup', maxWidth: 240, minWidth: 240, autoPanPadding: [16, 16] });
                            layer.getPopup()._caiId = marker.id;
                            layer.getPopup()._caiKind = 'marker';
                        }

                        layer.on('click', () => this.select(marker.id, true, 'marker'));
                        layer.on('mouseover', () => this.hover(marker.id, 'marker'));
                        layer.on('mouseout', () => this.hover(null, 'marker'));
                        layer.addTo(markerLayer);
                        markers.set(key(marker.id), { marker, layer });
                    });

                    if (! markers.has(key(selected)) && ! lines.has(key(selected)) && ! areas.has(key(selected))) {
                        selected = null;
                    }

                    this.fit();
                    this.$refs.canvas.classList.toggle('is-area-labels-hidden', map.getZoom() < config.areaLabelsMinZoom);
                    this.scheduleLines(0);

                    if (lines.has(key(selected))) {
                        lines.forEach(refreshLine);
                        raiseLines();
                        lines.get(key(selected)).layer.openPopup();
                    } else if (areas.has(key(selected))) {
                        this.zoomToArea(selected);
                    } else if (selected !== null) {
                        const entry = markers.get(key(selected));
                        clusterLayer && ! entry.layer._map
                            ? clusterLayer.zoomToShowLayer(entry.layer, () => entry.layer.openPopup())
                            : entry.layer.openPopup();
                    }
                },

                fit() {
                    const points = [];
                    markers.forEach(({ layer }) => points.push(layer.getLatLng()));
                    [lineLayer, areaLayer].forEach((group) => group.eachLayer((layer) => {
                        // Le linee caricate per area visibile non spostano l'inquadratura della regione.
                        if (group === lineLayer && layer._caiRemote) {
                            return;
                        }

                        const bounds = layer.getBounds();

                        if (bounds.isValid()) {
                            points.push(bounds.getSouthWest(), bounds.getNorthEast());
                        }
                    }));

                    // Un punto con coordinate palesemente errate (fuori dall'Italia) resta sulla mappa ma non sposta la vista.
                    const italyBounds = L.latLngBounds(italy);
                    const inItaly = points.filter((point) => italyBounds.contains(point));
                    const fitPoints = inItaly.length > 0 ? inItaly : points;

                    if (fitPoints.length === 0) {
                        map.setView([42.5, 12.5], config.minZoom);
                    } else if (markers.size === 1 && lines.size === 0) {
                        map.setView(fitPoints[0], config.singleZoom);
                    } else {
                        map.fitBounds(L.latLngBounds(fitPoints), { padding: [30, 40], maxZoom: config.fitMaxZoom });
                    }
                },

                select(id, fromMap, kind = 'marker') {
                    const previous = selected;
                    selected = id;
                    refresh(previous);
                    refresh(id);

                    // Selezionare o deselezionare una linea cambia l'attenuazione di tutte le altre.
                    if (lines.has(key(previous)) || lines.has(key(id))) {
                        lines.forEach(refreshLine);
                        raiseLines();
                    }

                    if (! fromMap && id !== null && areas.has(key(id))) {
                        this.zoomToArea(id);
                    } else if (! fromMap && id !== null && lines.has(key(id))) {
                        const { layer } = lines.get(key(id));
                        const bounds = layer.getBounds();
                        const open = () => (layer.getPopup() ? layer.openPopup() : map.closePopup());

                        // Linea fuori vista: si inquadra (senza ingrandire), poi l'anteprima a metà tracciato. Con le linee
                        // per area visibile mai sotto `linesMinZoom` (sparirebbero): ci si centra sul tracciato.
                        if (bounds.isValid() && ! map.getBounds().contains(bounds) && config.linesUrl) {
                            map.once('moveend', open);
                            map.setView(bounds.getCenter(), Math.max(config.linesMinZoom, Math.min(map.getZoom(), map.getBoundsZoom(bounds, false, L.point(30, 40)))));
                        } else if (bounds.isValid() && ! map.getBounds().contains(bounds)) {
                            map.once('moveend', open);
                            map.fitBounds(bounds, { padding: [30, 40], maxZoom: map.getZoom() });
                        } else {
                            open();
                        }
                    } else if (! fromMap && id !== null) {
                        const entry = markers.get(key(id));

                        if (entry) {
                            if (! map.getBounds().contains(entry.layer.getLatLng())) {
                                map.panTo(entry.layer.getLatLng());
                            }

                            const open = () => (entry.layer.getPopup() ? entry.layer.openPopup() : map.closePopup());

                            // Un marker dentro un gruppo non è sulla mappa: prima si ingrandisce fino a mostrarlo.
                            if (clusterLayer && ! entry.layer._map) {
                                clusterLayer.zoomToShowLayer(entry.layer, open);
                            } else {
                                open();
                            }
                        }
                    }

                    if (fromMap) {
                        this.$dispatch(`cai-map-${kind}-selected`, { map: config.name, id });
                    }
                },

                // "Zooma sul settore": l'area intera (zoom massimo `areaZoomMax`), almeno allo zoom dei sentieri se non è
                // vuota, con il suo riquadro aperto al centro.
                zoomToArea(id) {
                    const entry = areas.get(key(id));

                    if (! entry) {
                        return;
                    }

                    const bounds = entry.layer.getBounds();
                    let zoom = Math.min(config.areaZoomMax, map.getBoundsZoom(bounds, false, L.point(40, 40)));

                    if (config.linesUrl && ! entry.area.empty) {
                        zoom = Math.max(config.linesMinZoom, zoom);
                    }

                    if (key(selected) !== key(id)) {
                        const previous = selected;
                        selected = id;
                        refresh(previous);
                        refresh(id);
                        this.$dispatch('cai-map-area-selected', { map: config.name, id });
                    }

                    map.once('moveend', () => entry.layer.getPopup() && entry.layer.openPopup(bounds.getCenter()));
                    map.setView(bounds.getCenter(), zoom);
                },

                showLines() {
                    map.closePopup();
                    map.setZoom(Math.max(config.linesMinZoom, map.getZoom()));
                },

                showAreas() {
                    this.resetView();
                },

                resetView() {
                    map.closePopup();
                    this.select(null, true, areas.has(key(selected)) ? 'area' : 'marker');
                    this.fit();
                },

                // Linee dell'area visibile (US-1064): dopo ogni spostamento, con un piccolo ritardo per non chiedere a
                // ogni passo del trascinamento.
                scheduleLines(delay = config.linesDebounce) {
                    if (! config.linesUrl || ! map) {
                        return;
                    }

                    clearTimeout(linesTimer);
                    linesTimer = setTimeout(() => this.loadLines(), delay);
                },

                async loadLines() {
                    if (map.getZoom() < config.linesMinZoom) {
                        linesRequest?.abort();
                        linesRequest = null;
                        this.applyLines(null, null, null);
                        this.mode = 'areas';
                        this.linesLoading = false;

                        return;
                    }

                    this.mode = 'lines';
                    // Riquadro allargato e arrotondato verso l'esterno: piccoli spostamenti riusano la stessa risposta.
                    const bounds = map.getBounds().pad(config.linesPad);
                    const round = (value, up) => (up ? Math.ceil(value * 100) : Math.floor(value * 100)) / 100;
                    const bbox = [round(bounds.getWest(), false), round(bounds.getSouth(), false), round(bounds.getEast(), true), round(bounds.getNorth(), true)].join(',');

                    // La geometria arriva semplificata per lo zoom: zoom diverso = risposta diversa.
                    const requestKey = `${bbox}|${map.getZoom()}`;

                    if (requestKey === remoteKey) {
                        this.countLines();

                        return;
                    }

                    if (linesCache.has(requestKey)) {
                        this.applyLines(requestKey, linesCache.get(requestKey), map.getZoom());

                        return;
                    }

                    linesRequest?.abort();
                    const zoom = map.getZoom();
                    const request = new AbortController();
                    linesRequest = request;
                    this.linesLoading = true;
                    this.linesFailed = false;

                    try {
                        const url = new URL(config.linesUrl, window.location.href);
                        url.searchParams.set('bbox', bbox);
                        url.searchParams.set('zoom', String(zoom));
                        const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: request.signal });

                        if (! response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }

                        const data = await response.json();
                        linesCache.set(requestKey, data);

                        if (linesCache.size > config.linesCacheSize) {
                            linesCache.delete(linesCache.keys().next().value);
                        }

                        // Nel frattempo si è tornati sotto lo zoom dei sentieri o è partita un'altra richiesta.
                        if (linesRequest === request && map && map.getZoom() >= config.linesMinZoom) {
                            this.applyLines(requestKey, data, zoom);
                        }
                    } catch (error) {
                        if (error.name !== 'AbortError' && linesRequest === request) {
                            this.linesFailed = true;
                        }
                    } finally {
                        if (linesRequest === request) {
                            linesRequest = null;
                            this.linesLoading = false;
                        }
                    }
                },

                // Sostituisce le linee caricate: restano quelle già sulla mappa allo stesso zoom (e la selezionata, col suo
                // popup aperto), si tolgono le altre; una linea caricata a un altro zoom si ridisegna con la geometria nuova.
                applyLines(requestKey, data, zoom) {
                    remoteKey = requestKey;
                    const incoming = new Map((data?.lines ?? []).map((line) => [key(line.id), line]));

                    [...remoteIds].forEach((id) => {
                        const stale = lines.get(id)?.zoom !== zoom && id !== key(selected);

                        if (! incoming.has(id) || stale) {
                            if (id === key(selected)) {
                                map.closePopup();
                            }

                            this.removeLine(id);
                            remoteIds.delete(id);
                        }
                    });

                    incoming.forEach((line, id) => {
                        if (! remoteIds.has(id) && ! lines.has(id)) {
                            this.addLine(line, true);
                            lines.get(id).zoom = zoom;
                            remoteIds.add(id);
                        }
                    });

                    raiseLines();
                    this.lineTotal = data?.total ?? 0;
                    this.lineLimit = data?.limit ?? 0;
                    this.linesTruncated = data?.truncated ?? false;
                    this.countLines();
                },

                // "N sentieri in vista": le linee caricate che toccano il riquadro visibile (non quello allargato).
                countLines() {
                    const view = map.getBounds();
                    let count = 0;
                    remoteIds.forEach((id) => {
                        const bounds = lines.get(id)?.layer.getBounds();

                        if (bounds?.isValid() && view.intersects(bounds)) {
                            count++;
                        }
                    });
                    this.lineCount = count;
                },

                hover(id, kind = 'marker') {
                    this.highlight(id);
                    this.$dispatch(`cai-map-${kind}-hovered`, { map: config.name, id });
                },

                highlight(id) {
                    const previous = hovered;
                    hovered = id;
                    refresh(previous);
                    refresh(id);
                },

                destroy() {
                    listeners.forEach(([type, listener]) => window.removeEventListener(type, listener));
                    clearTimeout(linesTimer);
                    linesRequest?.abort();
                    areas.clear();
                    linesCache.clear();
                    resizeObserver?.disconnect();
                    clusterLayer = null;
                    lines.clear();
                    map?.remove();
                    map = null;
                },
            };
        };
    </script>
@endonce
@if ($cluster)
    @once
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"
            integrity="sha256-YU3qCpj/P06tdPBJGPax0bm6Q1wltfwjsho5TR4+TYc=" crossorigin="" data-navigate-once />
        <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"
            integrity="sha256-Hk4dIpcqOSb0hZjgyvFOP+cEmDXUKKNE/tT542ZbNQg=" crossorigin="" data-navigate-once></script>
    @endonce
@endif
<div
    {{ $attributes->class(['ms-cai-map']) }}
    x-data="caiMapComponent(JSON.parse($el.dataset.caiMapConfig))"
    data-cai-map="{{ $name }}"
    data-cai-map-config="{{ json_encode($config, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
>
    <div class="ms-cai-map__frame">
        <div x-ref="canvas" class="ms-cai-map__canvas" style="height: {{ $height }}" wire:ignore></div>
        @isset($overlay)
            {{ $overlay }}
        @endisset
    </div>
    @isset($footer)
        {{ $footer }}
    @endisset
    <div
        hidden
        data-cai-map-payload="{{ $payloadJson }}"
        wire:key="cai-map-{{ $name }}-{{ md5($payloadJson) }}"
        x-init="load(JSON.parse($el.dataset.caiMapPayload))"
    ></div>
</div>
