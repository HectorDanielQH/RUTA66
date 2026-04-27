<div class="col-span-full">
    <style>
        .leaflet-container {
            font: inherit;
        }
    </style>
    <link rel="stylesheet" href="/vendor/leaflet/leaflet.css">

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
            <h3 class="text-sm font-semibold text-slate-900">Ubicacion referencial de la zona</h3>
                <p class="text-xs text-slate-500">Haz clic en el mapa para marcar un punto referencial de la zona de delivery.</p>
            </div>
            <button
                type="button"
                id="delivery-zone-map-reset"
                class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
            >
                Limpiar punto
            </button>
        </div>

        <div
            id="delivery-zone-map-status"
            class="mb-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700"
        >
            Aun no se marco una ubicacion para esta zona.
        </div>

        <div
            id="delivery-zone-map-fallback"
            class="mb-3 hidden rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
        >
            No se pudo cargar el mapa. Verifica tu conexion a internet o recarga la pagina del panel.
        </div>

        <div id="delivery-zone-map" class="h-[380px] w-full overflow-hidden rounded-xl border border-slate-200"></div>
    </div>
</div>

<script>
    (() => {
        if (window.ruta66DeliveryZoneMapBooted) {
            window.ruta66InitDeliveryZoneMap?.();
            return;
        }

        window.ruta66DeliveryZoneMapBooted = true;
        let deliveryZoneMapInstance = null;
        let deliveryZoneMarker = null;
        let leafletScriptRequested = false;
        const defaultCenter = [-16.4897, -68.1193];
        const defaultZoom = 13;

        const setInputValue = (input, value) => {
            if (!input) return;

            input.value = value;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const showFallback = (show = true) => {
            const fallback = document.getElementById('delivery-zone-map-fallback');
            if (!fallback) return;

            fallback.classList.toggle('hidden', !show);
        };

        const updateMarker = (lat, lng) => {
            const latInput = document.getElementById('delivery-zone-latitude');
            const lngInput = document.getElementById('delivery-zone-longitude');
            const status = document.getElementById('delivery-zone-map-status');

            setInputValue(latInput, lat.toFixed(7));
            setInputValue(lngInput, lng.toFixed(7));

            if (status) {
                status.textContent = `Punto seleccionado: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            }

            if (!deliveryZoneMapInstance || typeof L === 'undefined') return;

            if (deliveryZoneMarker) {
                deliveryZoneMarker.setLatLng([lat, lng]);
            } else {
                deliveryZoneMarker = L.marker([lat, lng]).addTo(deliveryZoneMapInstance);
            }

            deliveryZoneMapInstance.panTo([lat, lng]);
        };

        const resetMarker = () => {
            const latInput = document.getElementById('delivery-zone-latitude');
            const lngInput = document.getElementById('delivery-zone-longitude');
            const status = document.getElementById('delivery-zone-map-status');

            setInputValue(latInput, '');
            setInputValue(lngInput, '');

            if (status) {
                status.textContent = 'Aun no se marco una ubicacion para esta zona.';
            }

            if (deliveryZoneMarker && deliveryZoneMapInstance) {
                deliveryZoneMapInstance.removeLayer(deliveryZoneMarker);
                deliveryZoneMarker = null;
            }
        };

        const mountMap = () => {
            const mapElement = document.getElementById('delivery-zone-map');

            if (!mapElement || typeof L === 'undefined') return;

            showFallback(false);

            const latInput = document.getElementById('delivery-zone-latitude');
            const lngInput = document.getElementById('delivery-zone-longitude');
            const resetButton = document.getElementById('delivery-zone-map-reset');
            const status = document.getElementById('delivery-zone-map-status');

            const initialLat = parseFloat(latInput?.value || '');
            const initialLng = parseFloat(lngInput?.value || '');
            const hasInitialPoint = !Number.isNaN(initialLat) && !Number.isNaN(initialLng);

            if (!deliveryZoneMapInstance) {
                deliveryZoneMapInstance = L.map(mapElement).setView(
                    hasInitialPoint ? [initialLat, initialLng] : defaultCenter,
                    hasInitialPoint ? 16 : defaultZoom
                );

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(deliveryZoneMapInstance);

                deliveryZoneMapInstance.on('click', (event) => {
                    updateMarker(event.latlng.lat, event.latlng.lng);
                });

                resetButton?.addEventListener('click', resetMarker);
            } else {
                deliveryZoneMapInstance.invalidateSize();
            }

            if (hasInitialPoint) {
                if (deliveryZoneMarker) {
                    deliveryZoneMarker.setLatLng([initialLat, initialLng]);
                } else {
                    deliveryZoneMarker = L.marker([initialLat, initialLng]).addTo(deliveryZoneMapInstance);
                }

                deliveryZoneMapInstance.setView([initialLat, initialLng], 16);

                if (status) {
                    status.textContent = `Punto seleccionado: ${initialLat.toFixed(5)}, ${initialLng.toFixed(5)}`;
                }
            }

            setTimeout(() => deliveryZoneMapInstance?.invalidateSize(), 250);
        };

        window.ruta66InitDeliveryZoneMap = () => {
            const loadLeaflet = () => {
                if (typeof L !== 'undefined') {
                    mountMap();
                    return;
                }

                if (leafletScriptRequested) {
                    return;
                }

                leafletScriptRequested = true;

                const script = document.createElement('script');
                script.src = '/vendor/leaflet/leaflet.js';
                script.onload = () => mountMap();
                script.onerror = () => showFallback(true);
                document.head.appendChild(script);
            };

            setTimeout(loadLeaflet, 150);
        };

        document.addEventListener('livewire:navigated', window.ruta66InitDeliveryZoneMap);
        document.addEventListener('DOMContentLoaded', window.ruta66InitDeliveryZoneMap);
        document.addEventListener('livewire:initialized', window.ruta66InitDeliveryZoneMap);

        window.ruta66InitDeliveryZoneMap();
    })();
</script>
