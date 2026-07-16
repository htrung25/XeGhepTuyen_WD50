<script setup lang="ts">
import mapboxgl from 'mapbox-gl';
import { ref, watch, onUnmounted, nextTick } from 'vue';
import 'mapbox-gl/dist/mapbox-gl.css';

const props = defineProps<{
    label: string;
    placeholder?: string;
    modelValue: string;
    lat: number | null;
    lng: number | null;
    error?: string;
    cityBias?: string; // 'Hà Nội' hoặc 'Hải Phòng'
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'update:lat', value: number | null): void;
    (e: 'update:lng', value: number | null): void;
}>();

const token = import.meta.env.VITE_MAPBOX_TOKEN as string | undefined;

// Autocomplete State
const searchInput = ref(props.modelValue);
const suggestions = ref<any[]>([]);
const showSuggestions = ref(false);
const isSearching = ref(false);
let debounceTimeout: ReturnType<typeof setTimeout> | null = null;

// Modal Map State
const isMapModalOpen = ref(false);
const mapContainer = ref<HTMLElement | null>(null);
const currentMapAddress = ref('');
const currentMapCoords = ref<[number, number] | null>(null); // [lng, lat]
const isResolvingAddress = ref(false);
const mapError = ref('');
let mapInstance: mapboxgl.Map | null = null;

// Coordinate biases
const CITY_COORDS: Record<string, [number, number]> = {
    'Hà Nội': [105.8544, 21.0285],
    'Hải Phòng': [106.6881, 20.8449],
};

watch(
    () => props.modelValue,
    (newVal) => {
        searchInput.value = newVal;
    },
);

// Clean up debounce
onUnmounted(() => {
    if (debounceTimeout) clearTimeout(debounceTimeout);
});

// Mapbox Autocomplete Geocoding
function handleInput() {
    if (debounceTimeout) clearTimeout(debounceTimeout);

    if (!searchInput.value.trim()) {
        suggestions.value = [];
        showSuggestions.value = false;
        emit('update:modelValue', '');
        emit('update:lat', null);
        emit('update:lng', null);
        return;
    }

    debounceTimeout = setTimeout(async () => {
        if (!token) return;
        isSearching.value = true;

        let url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(searchInput.value)}.json?access_token=${token}&country=vn&limit=5&language=vi`;

        // Proximity bias based on route city
        if (props.cityBias && CITY_COORDS[props.cityBias]) {
            const [lng, lat] = CITY_COORDS[props.cityBias];
            url += `&proximity=${lng},${lat}`;
        }

        try {
            const res = await fetch(url);
            const data = await res.json();
            suggestions.value = data.features || [];
            showSuggestions.value = suggestions.value.length > 0;
        } catch (e) {
            console.error('Geocoding error', e);
        } finally {
            isSearching.value = false;
        }
    }, 400);
}

// Select a suggestion
function selectSuggestion(item: any) {
    const address = item.place_name || item.text;
    const [lng, lat] = item.center;

    searchInput.value = address;
    suggestions.value = [];
    showSuggestions.value = false;

    emit('update:modelValue', address);
    emit('update:lat', lat);
    emit('update:lng', lng);
}

// Map Reverse Geocoding
async function reverseGeocode(lng: number, lat: number) {
    if (!token) return;
    isResolvingAddress.value = true;
    const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json?access_token=${token}&country=vn&limit=1&language=vi`;

    try {
        const res = await fetch(url);
        const data = await res.json();
        if (data.features && data.features.length > 0) {
            currentMapAddress.value = data.features[0].place_name;
        } else {
            currentMapAddress.value = `Tọa độ: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        }
    } catch {
        currentMapAddress.value = `Tọa độ: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
    } finally {
        isResolvingAddress.value = false;
    }
}

// Initialize Map
function openMapModal() {
    isMapModalOpen.value = true;
    mapError.value = token
        ? ''
        : 'Chưa cấu hình VITE_MAPBOX_TOKEN cho frontend.';
    currentMapAddress.value =
        props.modelValue || 'Đang lấy vị trí tâm bản đồ...';

    let initialCoords: [number, number] = [105.8544, 21.0285]; // default Hanoi

    if (props.lng && props.lat) {
        initialCoords = [props.lng, props.lat];
    } else if (props.cityBias && CITY_COORDS[props.cityBias]) {
        initialCoords = CITY_COORDS[props.cityBias];
    }

    currentMapCoords.value = initialCoords;

    nextTick(() => {
        if (!token || !mapContainer.value) return;
        try {
            mapboxgl.accessToken = token.trim();
            mapInstance = new mapboxgl.Map({
                container: mapContainer.value,
                style: 'mapbox://styles/mapbox/streets-v12',
                center: initialCoords,
                zoom: 15,
            });
        } catch (error) {
            console.error('Mapbox initialization error:', error);
            mapError.value = 'Không thể khởi tạo bản đồ trên thiết bị này.';
            return;
        }

        mapInstance.addControl(new mapboxgl.NavigationControl(), 'top-right');

        mapInstance.on('load', () => {
            mapError.value = '';
            if (props.lng && props.lat) {
                reverseGeocode(props.lng, props.lat);
            } else {
                const center = mapInstance!.getCenter();
                reverseGeocode(center.lng, center.lat);
            }
        });

        mapInstance.on('error', (event) => {
            console.error('Mapbox error:', event.error);
            if (!mapInstance?.loaded()) {
                mapError.value =
                    'Không thể tải bản đồ. Vui lòng kiểm tra Mapbox token và giới hạn URL.';
            }
        });

        // Listen to moveend to update target coordinates when map is panned/dragged
        mapInstance.on('moveend', () => {
            if (!mapInstance) return;
            const center = mapInstance.getCenter();
            currentMapCoords.value = [center.lng, center.lat];
            reverseGeocode(center.lng, center.lat);
        });
    });
}

function closeMapModal() {
    if (mapInstance) {
        mapInstance.remove();
        mapInstance = null;
    }
    isMapModalOpen.value = false;
}

function confirmMapSelection() {
    if (currentMapCoords.value && currentMapAddress.value) {
        const [lng, lat] = currentMapCoords.value;
        searchInput.value = currentMapAddress.value;
        emit('update:modelValue', currentMapAddress.value);
        emit('update:lat', lat);
        emit('update:lng', lng);
    }
    closeMapModal();
}

function handleClickOutside() {
    // delay to allow option click to trigger
    setTimeout(() => {
        showSuggestions.value = false;
    }, 200);
}
</script>

<template>
    <div class="relative w-full space-y-1.5">
        <label class="block text-sm font-semibold text-slate-700">
            {{ label }} <span class="text-red-500">*</span>
        </label>

        <div class="flex gap-2">
            <!-- Text input address search -->
            <div class="relative flex-1">
                <div
                    class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-slate-400"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                </div>

                <input
                    v-model="searchInput"
                    type="text"
                    :placeholder="placeholder || 'Nhập tìm kiếm địa chỉ...'"
                    class="h-12 w-full rounded-xl border border-slate-200 bg-white pr-4 pl-11 text-base text-slate-900 placeholder-slate-400 transition-all duration-150 focus:border-green-500 focus:ring-2 focus:ring-green-500/20 focus:outline-none"
                    :class="
                        error
                            ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20'
                            : ''
                    "
                    @input="handleInput"
                    @blur="handleClickOutside"
                />

                <!-- Autocomplete suggestions dropdown -->
                <div
                    v-if="showSuggestions && suggestions.length"
                    class="absolute z-30 mt-1.5 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl"
                >
                    <button
                        v-for="item in suggestions"
                        :key="item.id"
                        type="button"
                        class="flex w-full items-start gap-2.5 border-b border-slate-100 px-4 py-3 text-left text-sm transition last:border-0 hover:bg-slate-50"
                        @mousedown="selectSuggestion(item)"
                    >
                        <span class="mt-0.5 shrink-0 text-slate-400">📍</span>
                        <div>
                            <p class="text-[13.5px] font-medium text-slate-800">
                                {{ item.text }}
                            </p>
                            <p
                                class="mt-0.5 text-xs leading-relaxed text-slate-500"
                            >
                                {{ item.place_name }}
                            </p>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Map button -->
            <button
                type="button"
                class="flex h-12 shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                @click="openMapModal"
            >
                <svg
                    class="h-5 w-5 text-green-600"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L16 4m0 13V4m0 0L9 7"
                    />
                </svg>
                Bản đồ
            </button>
        </div>

        <p v-if="error" class="text-xs text-red-600">{{ error }}</p>

        <!-- MAP PICKER MODAL -->
        <div
            v-if="isMapModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
        >
            <div
                class="relative flex h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
            >
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-6 py-4"
                >
                    <div>
                        <h3 class="text-base font-bold text-slate-900">
                            Chọn vị trí trên bản đồ
                        </h3>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Kéo thả bản đồ để ghim đúng vị trí đón/trả
                        </p>
                    </div>
                    <button
                        type="button"
                        class="p-1 text-slate-400 transition hover:text-slate-600"
                        @click="closeMapModal"
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

                <!-- Map wrapper -->
                <div class="relative flex-1 bg-slate-100">
                    <!-- Mapbox container -->
                    <div
                        ref="mapContainer"
                        class="absolute inset-0 h-full w-full"
                    />
                    <div
                        v-if="mapError"
                        role="alert"
                        class="absolute inset-0 z-20 flex items-center justify-center bg-slate-100 px-6 text-center text-sm font-medium text-red-600"
                    >
                        {{ mapError }}
                    </div>

                    <!-- Fixed Target Pin in center (Grab-style) -->
                    <div
                        class="pointer-events-none absolute top-1/2 left-1/2 z-10 flex -translate-x-1/2 -translate-y-[calc(100%-8px)] flex-col items-center"
                    >
                        <div
                            class="mb-1.5 rounded bg-slate-900 px-2 py-0.5 text-[11px] font-bold text-white shadow transition-opacity"
                            :class="
                                isResolvingAddress
                                    ? 'opacity-50'
                                    : 'opacity-100'
                            "
                        >
                            {{
                                isResolvingAddress
                                    ? 'Đang xác vị trí...'
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
                        <!-- Pin pulse shadow -->
                        <div
                            class="mt-0.5 h-2 w-2 animate-ping rounded-full bg-red-600/30"
                        />
                    </div>
                </div>

                <!-- Bottom Panel (Address show & Action) -->
                <div class="space-y-4 border-t border-slate-100 bg-white p-6">
                    <div class="flex items-start gap-2.5">
                        <span class="mt-0.5 text-base text-green-600">📍</span>
                        <div class="min-w-0 flex-1">
                            <p
                                class="text-[11px] font-bold tracking-wider text-slate-400 uppercase"
                            >
                                Địa chỉ hiện tại
                            </p>
                            <p
                                class="mt-0.5 truncate text-sm leading-relaxed font-semibold text-slate-800"
                                :class="
                                    isResolvingAddress ? 'text-slate-400' : ''
                                "
                            >
                                {{ currentMapAddress }}
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            class="h-11 flex-1 rounded-xl bg-slate-100 text-sm font-semibold text-slate-700 transition hover:bg-slate-200"
                            @click="closeMapModal"
                        >
                            Hủy
                        </button>
                        <button
                            type="button"
                            :disabled="isResolvingAddress"
                            class="h-11 flex-1 rounded-xl bg-green-600 text-sm font-semibold text-white shadow transition hover:bg-green-700 disabled:opacity-50"
                            @click="confirmMapSelection"
                        >
                            Xác nhận địa điểm
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
