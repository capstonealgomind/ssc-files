<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import iconUrl from 'leaflet/dist/images/marker-icon.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl,
    iconUrl,
    shadowUrl,
});

const MAP_MIN_HEIGHT_PX = 380;
const MAP_MAX_HEIGHT_PX = 600;
const MAP_HEIGHT_VH = 55;

const props = defineProps({
    latitude: {
        type: [Number, String],
        default: null,
    },
    longitude: {
        type: [Number, String],
        default: null,
    },
    rangeMeters: {
        type: [Number, String],
        default: null,
    },
});

const mapContainer = ref(null);

let map = null;
let tileLayer = null;
let marker = null;
let circle = null;
let resizeObserver = null;
let resizeTimer = null;
let mapInitialized = false;
let lastCenterKey = '';

const parsedLatitude = computed(() => {
    const value = Number(props.latitude);
    return Number.isFinite(value) ? value : null;
});

const parsedLongitude = computed(() => {
    const value = Number(props.longitude);
    return Number.isFinite(value) ? value : null;
});

const parsedRangeMeters = computed(() => {
    const value = Number(props.rangeMeters);
    return Number.isFinite(value) && value > 0 ? value : null;
});

const hasLocation = computed(() =>
    parsedLatitude.value !== null && parsedLongitude.value !== null,
);

const openMapUrl = computed(() => {
    if (!hasLocation.value) {
        return 'https://www.openstreetmap.org';
    }

    return `https://www.openstreetmap.org/?mlat=${parsedLatitude.value}&mlon=${parsedLongitude.value}#map=17/${parsedLatitude.value}/${parsedLongitude.value}`;
});

const mapContainerStyle = computed(() => ({
    height: `clamp(${MAP_MIN_HEIGHT_PX}px, ${MAP_HEIGHT_VH}vh, ${MAP_MAX_HEIGHT_PX}px)`,
    minHeight: `${MAP_MIN_HEIGHT_PX}px`,
    borderColor: 'hsl(240 5.9% 90%)',
}));

function formatRangeLabel(meters) {
    if (meters >= 1000) {
        const km = meters / 1000;
        return km % 1 === 0 ? `${km} km` : `${km.toFixed(1)} km`;
    }

    return `${meters} m`;
}

function centerKey() {
    return `${parsedLatitude.value},${parsedLongitude.value},${parsedRangeMeters.value ?? 0}`;
}

function getCenterLatLng() {
    return L.latLng(parsedLatitude.value, parsedLongitude.value);
}

function defaultZoomForRange(rangeMeters) {
    if (!rangeMeters) {
        return 16;
    }

    if (rangeMeters <= 200) {
        return 17;
    }

    if (rangeMeters <= 500) {
        return 16;
    }

    if (rangeMeters <= 1000) {
        return 15;
    }

    return 14;
}

function scheduleResize() {
    if (!map || !mapInitialized) {
        return;
    }

    if (resizeTimer) {
        clearTimeout(resizeTimer);
    }

    resizeTimer = window.setTimeout(() => {
        map?.invalidateSize({ animate: false });
    }, 200);
}

function addTileLayer() {
    if (!map || tileLayer) {
        return;
    }

    tileLayer = L.tileLayer(
        'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
        {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20,
        },
    ).addTo(map);
}

function clearLayers() {
    if (marker) {
        marker.remove();
        marker = null;
    }

    if (circle) {
        circle.remove();
        circle = null;
    }
}

function syncMapLayers(shouldRefit = false) {
    if (!map || !mapInitialized || !hasLocation.value) {
        return;
    }

    const center = getCenterLatLng();
    const range = parsedRangeMeters.value;
    const keyChanged = centerKey() !== lastCenterKey;

    if (!marker) {
        marker = L.marker(center).addTo(map);
    } else {
        marker.setLatLng(center);
    }

    if (range) {
        if (!circle) {
            circle = L.circle(center, {
                radius: range,
                color: '#2563eb',
                fillColor: '#2563eb',
                fillOpacity: 0.15,
                weight: 2,
            }).addTo(map);
        } else {
            circle.setLatLng(center);
            circle.setRadius(range);
        }
    } else if (circle) {
        circle.remove();
        circle = null;
    }

    if (shouldRefit || keyChanged) {
        map.setView(center, defaultZoomForRange(range), { animate: false });
        lastCenterKey = centerKey();
    }
}

function destroyMap() {
    mapInitialized = false;
    clearLayers();
    tileLayer = null;

    if (resizeTimer) {
        clearTimeout(resizeTimer);
        resizeTimer = null;
    }

    if (resizeObserver && mapContainer.value) {
        resizeObserver.unobserve(mapContainer.value);
    }

    resizeObserver = null;

    if (map) {
        map.stop();
        map.remove();
        map = null;
    }

    lastCenterKey = '';
}

function initMap() {
    const container = mapContainer.value;
    if (!container || map || !hasLocation.value) {
        return;
    }

    if (container.offsetWidth === 0 || container.offsetHeight === 0) {
        window.setTimeout(() => initMap(), 50);
        return;
    }

    const center = getCenterLatLng();
    const range = parsedRangeMeters.value;

    map = L.map(container, {
        center,
        zoom: defaultZoomForRange(range),
        scrollWheelZoom: true,
        dragging: true,
        zoomControl: true,
        attributionControl: true,
    });

    addTileLayer();

    map.whenReady(() => {
        mapInitialized = true;
        lastCenterKey = centerKey();
        map.invalidateSize({ animate: false });
        syncMapLayers(false);
    });

    resizeObserver = new ResizeObserver(() => {
        scheduleResize();
    });
    resizeObserver.observe(container);
}

async function ensureMapReady() {
    await nextTick();

    if (!mapContainer.value || !hasLocation.value) {
        return;
    }

    if (!map) {
        initMap();
        return;
    }

    if (!mapInitialized) {
        return;
    }

    const keyChanged = centerKey() !== lastCenterKey;
    syncMapLayers(keyChanged);
}

onMounted(() => {
    ensureMapReady();
});

onUnmounted(() => {
    destroyMap();
});

watch(
    () => [parsedLatitude.value, parsedLongitude.value, parsedRangeMeters.value],
    () => {
        if (!hasLocation.value) {
            destroyMap();
            return;
        }

        ensureMapReady();
    },
);
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-medium" style="color: hsl(240 10% 3.9%);">Location map</p>
                <p class="text-xs mt-0.5" style="color: hsl(240 3.8% 46.1%);">
                    Zoom and pan freely — the range circle stays locked on the campus pin.
                </p>
            </div>
            <span
                v-if="hasLocation && parsedRangeMeters"
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium shrink-0"
                style="background-color: hsl(214 100% 97%); color: hsl(215 85% 42%);"
            >
                {{ formatRangeLabel(parsedRangeMeters) }} radius
            </span>
        </div>

        <div
            class="relative overflow-hidden rounded-lg border"
            :style="mapContainerStyle"
        >
            <div
                ref="mapContainer"
                class="leaflet-range-map h-full w-full"
            />

            <div
                v-if="!hasLocation"
                class="absolute inset-0 z-20 flex items-center justify-center px-6 text-center"
                style="background-color: hsl(240 4.8% 98%);"
            >
                <div class="space-y-1">
                    <p class="text-sm font-medium" style="color: hsl(240 10% 3.9%);">No location set yet</p>
                    <p class="text-xs" style="color: hsl(240 3.8% 46.1%);">
                        Use <span class="font-medium">Set location</span> or enter coordinates to preview the map.
                    </p>
                </div>
            </div>
        </div>

        <p
            v-if="hasLocation"
            class="text-xs"
            style="color: hsl(240 3.8% 46.1%);"
        >
            Blue circle shows the {{ parsedRangeMeters ? formatRangeLabel(parsedRangeMeters) : '' }} search radius centered on the campus pin.
            <a
                :href="openMapUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="font-medium underline underline-offset-2"
                style="color: hsl(215 85% 42%);"
            >Open full map</a>
        </p>
    </div>
</template>

<style scoped>
.leaflet-range-map {
    min-height: 380px;
}
</style>
