<script setup lang="ts">
import mapboxgl from 'mapbox-gl';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    shallowRef,
    useTemplateRef,
} from 'vue';
import 'mapbox-gl/dist/mapbox-gl.css';
import {
    boundaryBounds,
    boundaryCenter,
    isInsideBoundary,
} from '@/lib/location-geometry';
import type { ServiceAreaBoundary } from '@/lib/location-geometry';

const props = defineProps<{
    token?: string;
    initialAddress?: string;
    initialCoords?: [number, number] | null;
    cityBias?: string;
    boundary?: ServiceAreaBoundary | null;
}>();

const emit = defineEmits<{
    close: [];
    confirm: [payload: { address: string; lat: number; lng: number }];
}>();

const CITY_COORDS: Record<string, [number, number]> = {
    'Hà Nội': [105.8544, 21.0285],
    'Hải Phòng': [106.6881, 20.8449],
};

const mapContainer = useTemplateRef<HTMLDivElement>('mapContainer');
const currentAddress = shallowRef(
    props.initialAddress || 'Đang xác định địa chỉ...',
);
const currentCoords = shallowRef<[number, number] | null>(null);
const isResolvingAddress = shallowRef(false);
const mapLoadError = shallowRef('');
const selectionError = shallowRef('');
let mapInstance: mapboxgl.Map | null = null;
let reverseGeocodeRequest: AbortController | null = null;

const initialCenter = computed<[number, number]>(() => {
    return (
        props.initialCoords ??
        boundaryCenter(props.boundary) ??
        CITY_COORDS[props.cityBias ?? ''] ?? [105.8544, 21.0285]
    );
});

function addBoundaryLayer(map: mapboxgl.Map): void {
    if (!props.boundary || map.getSource('service-area')) return;

    map.addSource('service-area', {
        type: 'geojson',
        data: {
            type: 'Feature',
            properties: {},
            geometry: props.boundary,
        },
    });
    map.addLayer({
        id: 'service-area-fill',
        type: 'fill',
        source: 'service-area',
        paint: {
            'fill-color': '#16a34a',
            'fill-opacity': 0.08,
        },
    });
    map.addLayer({
        id: 'service-area-outline',
        type: 'line',
        source: 'service-area',
        paint: {
            'line-color': '#16a34a',
            'line-width': 2,
            'line-opacity': 0.8,
        },
    });
}

async function reverseGeocode(lng: number, lat: number): Promise<void> {
    if (!props.token) return;

    reverseGeocodeRequest?.abort();
    const controller = new AbortController();
    reverseGeocodeRequest = controller;
    isResolvingAddress.value = true;

    const params = new URLSearchParams({
        access_token: props.token,
        country: 'vn',
        limit: '1',
        language: 'vi',
    });

    try {
        const response = await fetch(
            `https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json?${params.toString()}`,
            { signal: controller.signal },
        );

        if (!response.ok) throw new Error('Reverse geocoding failed');

        const data = (await response.json()) as {
            features?: { place_name?: string }[];
        };
        currentAddress.value =
            data.features?.[0]?.place_name ??
            `Tọa độ: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
    } catch (error) {
        if ((error as DOMException).name !== 'AbortError') {
            currentAddress.value = `Tọa độ: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        }
    } finally {
        if (reverseGeocodeRequest === controller) {
            reverseGeocodeRequest = null;
            isResolvingAddress.value = false;
        }
    }
}

function initializeMap(): void {
    if (!props.token) {
        mapLoadError.value = 'Chưa cấu hình VITE_MAPBOX_TOKEN cho frontend.';
        return;
    }
    if (!mapContainer.value) return;

    try {
        mapboxgl.accessToken = props.token.trim();
        mapInstance = new mapboxgl.Map({
            container: mapContainer.value,
            style: 'mapbox://styles/mapbox/streets-v12',
            center: initialCenter.value,
            zoom: 15,
        });
    } catch (error) {
        console.error('Mapbox initialization error:', error);
        mapLoadError.value = 'Không thể khởi tạo bản đồ trên thiết bị này.';
        return;
    }

    mapInstance.addControl(new mapboxgl.NavigationControl(), 'top-right');
    mapInstance.on('load', () => {
        if (!mapInstance) return;

        mapLoadError.value = '';
        addBoundaryLayer(mapInstance);

        if (!props.initialCoords) {
            const bounds = boundaryBounds(props.boundary);
            if (bounds) {
                mapInstance.fitBounds(bounds, {
                    padding: 56,
                    maxZoom: 14,
                    duration: 0,
                });
            }
        }

        const center = mapInstance.getCenter();
        currentCoords.value = [center.lng, center.lat];
        void reverseGeocode(center.lng, center.lat);
    });
    mapInstance.on('movestart', () => {
        selectionError.value = '';
    });
    mapInstance.on('moveend', () => {
        if (!mapInstance) return;

        const center = mapInstance.getCenter();
        currentCoords.value = [center.lng, center.lat];

        // Người dùng luôn được kéo bản đồ tự do. Phạm vi chỉ được kiểm tra khi
        // bấm xác nhận để họ có thể kéo pin quay lại mà không bị khóa giao diện.
        void reverseGeocode(center.lng, center.lat);
    });
    mapInstance.on('error', (event) => {
        console.error('Mapbox error:', event.error);
        if (!mapInstance?.loaded()) {
            mapLoadError.value =
                'Không thể tải bản đồ. Vui lòng kiểm tra kết nối và thử lại.';
        }
    });
}

function close(): void {
    emit('close');
}

function confirmSelection(): void {
    if (!currentCoords.value || isResolvingAddress.value) return;

    const [lng, lat] = currentCoords.value;
    if (!isInsideBoundary(props.boundary, lng, lat)) {
        selectionError.value =
            'Điểm này nằm ngoài khu vực phục vụ. Hãy kéo ghim vào vùng viền xanh rồi xác nhận lại.';
        return;
    }

    selectionError.value = '';
    emit('confirm', {
        address: currentAddress.value,
        lat,
        lng,
    });
}

onMounted(() => {
    void nextTick(initializeMap);
});

onBeforeUnmount(() => {
    reverseGeocodeRequest?.abort();
    mapInstance?.remove();
    mapInstance = null;
});
</script>

<template>
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="location-map-title"
    >
        <div
            class="relative flex h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
        >
            <div
                class="flex items-center justify-between border-b border-slate-100 px-6 py-4"
            >
                <div>
                    <h3
                        id="location-map-title"
                        class="text-base font-bold text-slate-900"
                    >
                        Chọn vị trí trên bản đồ
                    </h3>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Kéo bản đồ để ghim đúng vị trí. Viền xanh là khu vực
                        phục vụ.
                    </p>
                </div>
                <button
                    type="button"
                    aria-label="Đóng bản đồ"
                    class="rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                    @click="close"
                >
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <div class="relative flex-1 bg-slate-100">
                <div
                    ref="mapContainer"
                    class="absolute inset-0 h-full w-full"
                />
                <div
                    v-if="mapLoadError"
                    role="alert"
                    class="absolute inset-0 z-20 flex items-center justify-center bg-slate-100 px-6 text-center text-sm font-medium text-red-600"
                >
                    {{ mapLoadError }}
                </div>

                <div
                    class="pointer-events-none absolute top-1/2 left-1/2 z-10 flex -translate-x-1/2 -translate-y-[calc(100%-8px)] flex-col items-center"
                >
                    <div
                        class="mb-1.5 rounded bg-slate-900 px-2 py-0.5 text-[11px] font-bold text-white shadow"
                    >
                        {{
                            isResolvingAddress
                                ? 'Đang xác định địa chỉ...'
                                : 'Điểm tại đây'
                        }}
                    </div>
                    <svg
                        class="h-10 w-10 text-red-600 drop-shadow-lg"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <div
                        class="mt-0.5 h-2 w-2 animate-ping rounded-full bg-red-600/30"
                    />
                </div>
            </div>

            <div class="space-y-4 border-t border-slate-100 bg-white p-6">
                <div class="flex items-start gap-2.5">
                    <span class="mt-0.5 text-base text-green-600">📍</span>
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-[11px] font-bold tracking-wider text-slate-400 uppercase"
                        >
                            Địa chỉ tại ghim
                        </p>
                        <p
                            class="mt-0.5 line-clamp-2 text-sm leading-relaxed font-semibold"
                            :class="
                                isResolvingAddress
                                    ? 'text-slate-400'
                                    : 'text-slate-800'
                            "
                        >
                            {{ currentAddress }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="selectionError"
                    role="alert"
                    aria-live="assertive"
                    class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-sm text-amber-800"
                >
                    <svg
                        class="mt-0.5 h-5 w-5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v2m0 4h.01M10.29 3.86l-8.82 15.28A1 1 0 002.34 21h19.32a1 1 0 00.87-1.5L13.71 3.86a1 1 0 00-1.74 0z"
                        />
                    </svg>
                    <span>{{ selectionError }}</span>
                </div>

                <div class="flex gap-3">
                    <button
                        type="button"
                        class="h-11 flex-1 rounded-xl bg-slate-100 text-sm font-semibold text-slate-700 transition hover:bg-slate-200"
                        @click="close"
                    >
                        Hủy
                    </button>
                    <button
                        type="button"
                        :disabled="isResolvingAddress || !!mapLoadError"
                        class="h-11 flex-1 rounded-xl bg-green-600 text-sm font-semibold text-white shadow transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="confirmSelection"
                    >
                        Xác nhận địa điểm
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
