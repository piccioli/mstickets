{{-- Mappa con cartografia CAI (US-1033), unica per rifugi, sentieri e tappe SICAI. Leaflet 1.9.4 da CDN, caricato una
     sola volta per pagina (@once); tile CAI zoom 6–16.
     Props:
     - `name`: identificativo della mappa nella pagina, ripetuto negli eventi (più mappe nella stessa pagina);
     - `markers`: lista di CaiMapMarker (o array con `id`, `lat`, `lng`, `label`, `color`, `letter`, `popupHtml`);
     - `lines`: lista di CaiMapLine (o array con `id`, `geojson`, `color`, `label`, `weight`, `popupHtml`);
     - `selected`: id del marker selezionato all'apertura (popup aperto);
     - `height`: altezza CSS del riquadro;
     - `cluster` (US-1042): raggruppa i marker vicini sotto lo zoom 12 in un cerchio con il numero (tooltip
       "N {clusterNoun} · clic per ingrandire", clic → zoom +2 sul gruppo), con leaflet.markercluster 1.5.3 caricato
       una sola volta e solo se almeno una mappa della pagina lo chiede. Senza, il comportamento è quello della 10.4;
     - `clusterNoun`: sostantivo plurale del tooltip del gruppo (default "rifugi").
     Vista: `fitBounds` con margine (zoom massimo 13) sui marker e le linee dentro l'Italia, un solo marker → zoom 13,
     niente da mostrare → Italia.
     Eventi (bubbling fino a `window`, `detail` = `{ map: name, id }`):
     - emessi: `cai-map-marker-selected` (clic sul marker; `id` null alla chiusura del popup), `cai-map-marker-hovered`
       (`id` null all'uscita);
     - ascoltati su `window`: `cai-map-highlight` (marker ingrandito, es. hover sulla riga dell'elenco) e `cai-map-select`
       (selezione con popup aperto, es. clic sulla riga).
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
])
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
    $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $config = [
        'name' => $name,
        'selected' => $selected,
        'tiles' => 'https://r3-it.storage.cloud.it/tiles/{z}/{x}/{y}.png',
        'attribution' => '© CAI · © OpenStreetMap contributors',
        'minZoom' => 6,
        'maxZoom' => 16,
        'fitMaxZoom' => 13,
        'singleZoom' => 13,
        'cluster' => (bool) $cluster,
        'clusterNoun' => $clusterNoun,
        // Come nel design: sotto lo zoom 12 i marker vicini si raggruppano, il clic sul gruppo ingrandisce di 2.
        'clusterUntilZoom' => 11,
        'clusterRadius' => 40,
        'clusterZoomStep' => 2,
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
            let resizeObserver = null;
            let payload = { markers: [], lines: [] };
            let selected = config.selected ?? null;
            let hovered = null;
            const markers = new Map();
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

            const refresh = (id) => {
                const entry = markers.get(key(id));

                if (! entry) {
                    return;
                }

                const active = key(id) === key(selected) || key(id) === key(hovered);
                entry.layer.setIcon(icon(entry.marker));
                entry.layer.setZIndexOffset(active ? 1000 : 0);
            };

            return {
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
                    lineLayer = L.featureGroup().addTo(map);
                    markerLayer = config.cluster ? this.clusterGroup() : L.featureGroup();
                    markerLayer.addTo(map);
                    map.on('popupclose', (event) => {
                        if (event.popup._caiMarkerId !== undefined && key(event.popup._caiMarkerId) === key(selected)) {
                            this.select(null, true);
                        }
                    });

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

                render() {
                    markerLayer.clearLayers();
                    lineLayer.clearLayers();
                    markers.clear();

                    (payload.lines ?? []).forEach((line) => {
                        const layer = L.geoJSON(line.geojson, {
                            style: { color: safeColor(line.color), weight: line.weight ?? 4, opacity: 0.9 },
                        });

                        if (line.popupHtml) {
                            layer.bindPopup(line.popupHtml, { className: 'ms-cai-map__popup', maxWidth: 240, minWidth: 240 });
                        } else if (line.label) {
                            layer.bindTooltip(escapeHtml(line.label), { sticky: true });
                        }

                        layer.addTo(lineLayer);
                    });

                    (payload.markers ?? []).forEach((marker) => {
                        const layer = L.marker([marker.lat, marker.lng], { icon: icon(marker), title: marker.label, riseOnHover: true });

                        if (marker.popupHtml) {
                            layer.bindPopup(marker.popupHtml, { className: 'ms-cai-map__popup', maxWidth: 240, minWidth: 240, autoPanPadding: [16, 16] });
                            layer.getPopup()._caiMarkerId = marker.id;
                        }

                        layer.on('click', () => this.select(marker.id, true));
                        layer.on('mouseover', () => this.hover(marker.id));
                        layer.on('mouseout', () => this.hover(null));
                        layer.addTo(markerLayer);
                        markers.set(key(marker.id), { marker, layer });
                    });

                    if (! markers.has(key(selected))) {
                        selected = null;
                    }

                    this.fit();

                    if (selected !== null) {
                        const entry = markers.get(key(selected));
                        clusterLayer && ! entry.layer._map
                            ? clusterLayer.zoomToShowLayer(entry.layer, () => entry.layer.openPopup())
                            : entry.layer.openPopup();
                    }
                },

                fit() {
                    const points = [];
                    markers.forEach(({ layer }) => points.push(layer.getLatLng()));
                    lineLayer.eachLayer((layer) => {
                        const bounds = layer.getBounds();

                        if (bounds.isValid()) {
                            points.push(bounds.getSouthWest(), bounds.getNorthEast());
                        }
                    });

                    // Un punto con coordinate palesemente errate (fuori dall'Italia) resta sulla mappa ma non sposta la vista.
                    const italyBounds = L.latLngBounds(italy);
                    const inItaly = points.filter((point) => italyBounds.contains(point));
                    const fitPoints = inItaly.length > 0 ? inItaly : points;

                    if (fitPoints.length === 0) {
                        map.setView([42.5, 12.5], config.minZoom);
                    } else if (markers.size === 1 && lineLayer.getLayers().length === 0) {
                        map.setView(fitPoints[0], config.singleZoom);
                    } else {
                        map.fitBounds(L.latLngBounds(fitPoints), { padding: [30, 40], maxZoom: config.fitMaxZoom });
                    }
                },

                select(id, fromMap) {
                    const previous = selected;
                    selected = id;
                    refresh(previous);
                    refresh(id);

                    if (! fromMap && id !== null) {
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
                        this.$dispatch('cai-map-marker-selected', { map: config.name, id });
                    }
                },

                hover(id) {
                    this.highlight(id);
                    this.$dispatch('cai-map-marker-hovered', { map: config.name, id });
                },

                highlight(id) {
                    const previous = hovered;
                    hovered = id;
                    refresh(previous);
                    refresh(id);
                },

                destroy() {
                    listeners.forEach(([type, listener]) => window.removeEventListener(type, listener));
                    resizeObserver?.disconnect();
                    clusterLayer = null;
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
    <div x-ref="canvas" class="ms-cai-map__canvas" style="height: {{ $height }}" wire:ignore></div>
    <div
        hidden
        data-cai-map-payload="{{ $payloadJson }}"
        wire:key="cai-map-{{ $name }}-{{ md5($payloadJson) }}"
        x-init="load(JSON.parse($el.dataset.caiMapPayload))"
    ></div>
</div>
