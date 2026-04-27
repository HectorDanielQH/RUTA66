<div class="col-span-full">
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    >
    <style>
        .leaflet-container {
            font: inherit;
        }
    </style>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Ubicacion de entrega</h3>
                <p class="text-xs text-slate-500">Haz clic en el mapa para marcar la ubicacion exacta del pedido.</p>
            </div>
            <button
                type="button"
                id="delivery-map-reset"
                class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
            >
                Limpiar punto
            </button>
        </div>

        <div
            id="delivery-map-status"
            class="mb-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700"
        >
            Aun no se marco una ubicacion en el mapa.
        </div>

        <div
            id="delivery-map-fallback"
            class="mb-3 hidden rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
        >
            No se pudo cargar el mapa. Verifica tu conexion a internet o recarga la pagina del panel.
        </div>

        <div id="delivery-map" class="h-[380px] w-full overflow-hidden rounded-xl border border-slate-200"></div>
    </div>
</div>

<script>
    (() => {
        if (window.ruta66DeliveryMapBooted) {
            window.ruta66InitDeliveryMap?.();
            return;
        }

        window.ruta66DeliveryMapBooted = true;
        let deliveryMapInstance = null;
        let deliveryMapMarker = null;
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
            const fallback = document.getElementById('delivery-map-fallback');
            if (!fallback) return;

            fallback.classList.toggle('hidden', !show);
        };

        const updateMarker = (lat, lng) => {
            const latInput = document.getElementById('order-latitude');
            const lngInput = document.getElementById('order-longitude');
            const status = document.getElementById('delivery-map-status');

            setInputValue(latInput, lat.toFixed(7));
            setInputValue(lngInput, lng.toFixed(7));

            if (status) {
                status.textContent = `Ubicacion seleccionada: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            }

            if (!deliveryMapInstance || typeof L === 'undefined') return;

            if (deliveryMapMarker) {
                deliveryMapMarker.setLatLng([lat, lng]);
            } else {
                deliveryMapMarker = L.marker([lat, lng]).addTo(deliveryMapInstance);
            }

            deliveryMapInstance.panTo([lat, lng]);
        };

        const resetMarker = () => {
            const latInput = document.getElementById('order-latitude');
            const lngInput = document.getElementById('order-longitude');
            const status = document.getElementById('delivery-map-status');

            setInputValue(latInput, '');
            setInputValue(lngInput, '');

            if (status) {
                status.textContent = 'Aun no se marco una ubicacion en el mapa.';
            }

            if (deliveryMapMarker && deliveryMapInstance) {
                deliveryMapInstance.removeLayer(deliveryMapMarker);
                deliveryMapMarker = null;
            }
        };

        const mountMap = () => {
            const mapElement = document.getElementById('delivery-map');

            if (!mapElement || typeof L === 'undefined') return;

            showFallback(false);

            const latInput = document.getElementById('order-latitude');
            const lngInput = document.getElementById('order-longitude');
            const resetButton = document.getElementById('delivery-map-reset');
            const status = document.getElementById('delivery-map-status');

            const initialLat = parseFloat(latInput?.value || '');
            const initialLng = parseFloat(lngInput?.value || '');
            const hasInitialPoint = !Number.isNaN(initialLat) && !Number.isNaN(initialLng);

            if (!deliveryMapInstance) {
                deliveryMapInstance = L.map(mapElement).setView(
                    hasInitialPoint ? [initialLat, initialLng] : defaultCenter,
                    hasInitialPoint ? 16 : defaultZoom
                );

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(deliveryMapInstance);

                deliveryMapInstance.on('click', (event) => {
                    updateMarker(event.latlng.lat, event.latlng.lng);
                });

                resetButton?.addEventListener('click', resetMarker);
            } else {
                deliveryMapInstance.invalidateSize();
            }

            if (hasInitialPoint) {
                if (deliveryMapMarker) {
                    deliveryMapMarker.setLatLng([initialLat, initialLng]);
                } else {
                    deliveryMapMarker = L.marker([initialLat, initialLng]).addTo(deliveryMapInstance);
                }

                deliveryMapInstance.setView([initialLat, initialLng], 16);

                if (status) {
                    status.textContent = `Ubicacion seleccionada: ${initialLat.toFixed(5)}, ${initialLng.toFixed(5)}`;
                }
            }

            setTimeout(() => deliveryMapInstance?.invalidateSize(), 250);
        };

        const ensureLeaflet = () => {
            if (typeof L !== 'undefined') {
                mountMap();
                return;
            }

            if (leafletScriptRequested) {
                return;
            }

            leafletScriptRequested = true;

            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
            script.crossOrigin = '';
            script.onload = () => mountMap();
            script.onerror = () => showFallback(true);
            document.head.appendChild(script);
        };

        window.ruta66InitDeliveryMap = () => {
            setTimeout(ensureLeaflet, 150);
        };

        document.addEventListener('livewire:navigated', window.ruta66InitDeliveryMap);
        document.addEventListener('DOMContentLoaded', window.ruta66InitDeliveryMap);
        document.addEventListener('livewire:initialized', window.ruta66InitDeliveryMap);

        window.ruta66InitDeliveryMap();
    })();
</script>
