<x-filament-panels::page>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    @php
        $sections = $this->sections();
    @endphp

    <div
        id="cai-sections-map"
        style="height: 70vh; min-height: 24rem;"
        class="rounded-[var(--ms-radius-xl)] border border-[var(--ms-border-default)]"
        wire:ignore
    ></div>

    @if (empty($sections))
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
            Nessuna sezione con coordinate note da mostrare sulla mappa.
        </p>
    @endif

    <script>
        (() => {
            const sections = @json($sections);
            const container = document.getElementById('cai-sections-map');

            if (! container || container.dataset.leafletInitialized) {
                return;
            }

            container.dataset.leafletInitialized = 'true';

            const map = L.map(container).setView([42.5, 12.5], 6);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 18,
            }).addTo(map);

            const markers = sections.map((section) => {
                return L.marker([section.latitude, section.longitude])
                    .bindPopup(`<strong>${section.name}</strong><br>${section.region}`);
            });

            markers.forEach((marker) => marker.addTo(map));

            // Vista iniziale calcolata solo sui marker dentro un bounding box dell'Italia:
            // il datapack RUNTS-CAI (dato reale, US-805) contiene almeno una sezione con
            // coordinate palesemente errate (geocoding a monte, fuori scope pulire qui) — un
            // singolo outlier del genere non deve zoomare la vista iniziale fuori dall'Italia.
            // Il marker resta comunque sulla mappa, solo escluso dal calcolo dell'inquadratura.
            const italyBounds = L.latLngBounds([35, 6], [47.5, 19]);
            const markersInItaly = markers.filter((marker) => italyBounds.contains(marker.getLatLng()));
            const markersForFit = markersInItaly.length > 0 ? markersInItaly : markers;

            if (markersForFit.length > 0) {
                map.fitBounds(L.featureGroup(markersForFit).getBounds().pad(0.1));
            }
        })();
    </script>
</x-filament-panels::page>
