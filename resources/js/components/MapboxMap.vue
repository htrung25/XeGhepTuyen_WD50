<script setup lang="ts">
import mapboxgl from 'mapbox-gl';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import 'mapbox-gl/dist/mapbox-gl.css';

export interface MapMarker {
    id: string;
    lat: number;
    lng: number;
    color?: string;
    label?: string;
}

const props = withDefaults(
    defineProps<{
        markers?: MapMarker[];
        center?: [number, number]; // [lng, lat]
        zoom?: number;
    }>(),
    {
        markers: () => [],
        center: () => [106.4, 20.9], // giữa Hà Nội – Hải Phòng
        zoom: 8.5,
    },
);

const emit = defineEmits<{ (e: 'select', id: string): void }>();

const token = import.meta.env.VITE_MAPBOX_TOKEN as string | undefined;
const hasToken = !!token;
const container = ref<HTMLElement | null>(null);
let map: mapboxgl.Map | null = null;
let loaded = false;
let markerObjs: mapboxgl.Marker[] = [];

function clearMarkers() {
    markerObjs.forEach((m) => m.remove());
    markerObjs = [];
}

function renderMarkers() {
    if (!map || !loaded) return;
    clearMarkers();
    const valid = props.markers.filter((m) => m.lat !== 0 || m.lng !== 0);
    if (valid.length === 0) return;

    const bounds = new mapboxgl.LngLatBounds();
    for (const m of valid) {
        const el = document.createElement('div');
        el.style.cssText = `width:20px;height:20px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.45);cursor:pointer;background:${m.color ?? '#16a34a'}`;
        el.addEventListener('click', () => emit('select', m.id));
        const marker = new mapboxgl.Marker({ element: el }).setLngLat([
            m.lng,
            m.lat,
        ]);
        if (m.label) {
            marker.setPopup(
                new mapboxgl.Popup({ offset: 16, closeButton: false }).setText(
                    m.label,
                ),
            );
        }
        marker.addTo(map);
        markerObjs.push(marker);
        bounds.extend([m.lng, m.lat]);
    }

    if (valid.length === 1) {
        map.easeTo({ center: [valid[0].lng, valid[0].lat], zoom: 12 });
    } else {
        map.fitBounds(bounds, { padding: 60, maxZoom: 13, duration: 400 });
    }
}

onMounted(() => {
    if (!hasToken || !container.value) return;
    mapboxgl.accessToken = token as string;
    map = new mapboxgl.Map({
        container: container.value,
        style: 'mapbox://styles/mapbox/streets-v12',
        center: props.center,
        zoom: props.zoom,
    });
    map.addControl(new mapboxgl.NavigationControl(), 'top-right');
    map.on('load', () => {
        loaded = true;
        renderMarkers();
    });
});

watch(() => props.markers, renderMarkers, { deep: true });

onUnmounted(() => {
    clearMarkers();
    map?.remove();
    map = null;
});
</script>

<template>
    <div class="relative h-full w-full">
        <div
            v-if="!hasToken"
            class="flex h-full items-center justify-center bg-slate-100 px-4 text-center text-sm text-gray-400"
        >
            Chưa cấu hình bản đồ (thiếu VITE_MAPBOX_TOKEN)
        </div>
        <div v-else ref="container" class="h-full w-full" />
    </div>
</template>
