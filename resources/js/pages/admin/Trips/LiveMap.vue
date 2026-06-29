<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { adminApi } from '@/api/admin.api';
import MapboxMap from '@/components/MapboxMap.vue';
import type {MapMarker} from '@/components/MapboxMap.vue';

interface LiveTrip {
    id: string;
    trip_code: string;
    route_name: string;
    driver_name: string;
    driver_rating: number;
    vehicle_type: string;
    vehicle_plate: string;
    departure_time: string;
    passenger_count: number;
    total_seats: number;
    status: string;
    current_speed: number;
    eta_minutes: number;
    lat: number;
    lng: number;
    is_delayed: boolean;
}

const trips = ref<LiveTrip[]>([]);
const isLoading = ref(true);
const errorMsg = ref('');
const activeFilter = ref<'all' | 'hn_hp' | 'hp_hn' | 'delayed'>('all');
const selectedTrip = ref<LiveTrip | null>(null);
let refreshTimer: ReturnType<typeof setInterval> | null = null;

const filters = [
    { key: 'all', label: 'Tất cả' },
    { key: 'hn_hp', label: 'HN → HP' },
    { key: 'hp_hn', label: 'HP → HN' },
    { key: 'delayed', label: 'Chậm trễ' },
];

const statusMap: Record<string, { label: string; class: string }> = {
    scheduled: { label: 'Đã lên lịch', class: 'bg-slate-100 text-slate-600' },
    boarding: { label: 'Đang đón', class: 'bg-blue-100 text-blue-700' },
    in_progress: { label: 'Đang chạy', class: 'bg-green-100 text-green-700' },
    delayed: { label: 'Chậm', class: 'bg-red-100 text-red-700' },
    completed: { label: 'Hoàn thành', class: 'bg-gray-100 text-gray-500' },
};

const filteredTrips = computed(() => {
    switch (activeFilter.value) {
        case 'hn_hp':
            return trips.value.filter(
                (t) =>
                    t.route_name.toLowerCase().includes('hà nội') ||
                    t.route_name.startsWith('HN'),
            );
        case 'hp_hn':
            return trips.value.filter(
                (t) =>
                    t.route_name.toLowerCase().includes('hải phòng') ||
                    t.route_name.startsWith('HP'),
            );
        case 'delayed':
            return trips.value.filter((t) => t.is_delayed);
        default:
            return trips.value;
    }
});

const delayedCount = computed(
    () => trips.value.filter((t) => t.is_delayed).length,
);

// Marker bản đồ Mapbox: chỉ xe có GPS (lat/lng ≠ 0), màu theo chọn/chậm
const mapMarkers = computed<MapMarker[]>(() =>
    filteredTrips.value
        .filter((t) => t.lat !== 0 || t.lng !== 0)
        .map((t) => ({
            id: t.id,
            lat: t.lat,
            lng: t.lng,
            color:
                selectedTrip.value?.id === t.id
                    ? '#dc2626'
                    : t.is_delayed
                      ? '#ef4444'
                      : '#16a34a',
            label: `${t.vehicle_plate} · ${t.trip_code}`,
        })),
);

function onMapSelect(id: string) {
    selectedTrip.value = trips.value.find((t) => t.id === id) ?? null;
}

async function loadLiveTrips() {
    const { data, error } = await adminApi.getLiveTrips();
    if (error) {
        if (isLoading.value) {
            errorMsg.value = error;
            isLoading.value = false;
        }
        return;
    }
    trips.value = (data as LiveTrip[]) ?? [];
    isLoading.value = false;
}

// Chạy thủ công command xử lý chuyến quá giờ (không cần chờ scheduler 10')
const resolving = ref(false);
const resolveMsg = ref('');
async function runAutoResolve() {
    resolving.value = true;
    resolveMsg.value = '';
    const { error } = await adminApi.runAutoResolveTrips();
    resolving.value = false;
    if (error) {
        resolveMsg.value = typeof error === 'string' ? error : 'Có lỗi xảy ra';
        return;
    }
    resolveMsg.value = 'Đã chạy xử lý chuyến quá giờ';
    await loadLiveTrips();
    setTimeout(() => (resolveMsg.value = ''), 4000);
}

onMounted(() => {
    loadLiveTrips();
    refreshTimer = setInterval(loadLiveTrips, 15_000);
});
onUnmounted(() => {
    if (refreshTimer) clearInterval(refreshTimer);
});
</script>

<template>
    <div class="flex h-full flex-col">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">
                    Theo dõi chuyến đi trực tiếp
                </h1>
                <p class="mt-0.5 text-sm text-gray-500">Cập nhật mỗi 15 giây</p>
            </div>
            <div class="flex items-center gap-3">
                <span
                    v-if="resolveMsg"
                    class="text-xs font-medium text-green-600"
                    >{{ resolveMsg }}</span
                >
                <button
                    @click="runAutoResolve"
                    :disabled="resolving"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-red-700 disabled:opacity-60"
                    title="Hủy/hoàn tất các chuyến đã quá giờ + tất toán vé mồ côi"
                >
                    <svg
                        class="h-3.5 w-3.5"
                        :class="resolving ? 'animate-spin' : ''"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                    {{ resolving ? 'Đang chạy...' : 'Xử lý chuyến quá giờ' }}
                </button>
                <span
                    v-if="delayedCount > 0"
                    class="inline-flex items-center gap-1 rounded-full bg-red-100 px-3 py-1.5 text-xs font-medium text-red-700"
                >
                    <span
                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-red-500"
                    />
                    {{ delayedCount }} chuyến bị chậm
                </span>
                <span class="flex items-center gap-1 text-xs text-gray-500">
                    <span
                        class="h-2 w-2 animate-pulse rounded-full bg-green-500"
                    />
                    Live
                </span>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="mb-4 flex w-fit gap-1 rounded-xl bg-gray-100 p-1">
            <button
                v-for="f in filters"
                :key="f.key"
                @click="activeFilter = f.key as typeof activeFilter.value"
                :class="[
                    'rounded-lg px-4 py-1.5 text-sm font-medium transition-colors',
                    activeFilter === f.key
                        ? 'bg-white text-gray-900 shadow-sm'
                        : 'text-gray-500 hover:text-gray-700',
                ]"
            >
                {{ f.label }}
                <span
                    v-if="f.key === 'delayed' && delayedCount > 0"
                    class="ml-1 rounded-full bg-red-500 px-1.5 text-xs text-white"
                    >{{ delayedCount }}</span
                >
            </button>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="flex h-64 items-center justify-center">
            <div
                class="h-8 w-8 animate-spin rounded-full border-b-2 border-red-600"
            />
        </div>

        <!-- Error -->
        <div
            v-else-if="errorMsg"
            class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-5 text-red-700"
        >
            {{ errorMsg }}
            <button @click="loadLiveTrips" class="ml-auto text-sm underline">
                Thử lại
            </button>
        </div>

        <template v-else>
            <div class="flex min-h-0 flex-1 gap-5">
                <!-- Left: Map placeholder (60%) -->
                <div
                    class="flex w-3/5 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-4 py-3"
                    >
                        <h3 class="text-sm font-semibold text-gray-900">
                            Bản đồ thời gian thực
                        </h3>
                        <span class="text-xs text-gray-400">Mapbox</span>
                    </div>
                    <!-- Map area -->
                    <div class="relative flex-1 bg-slate-100">
                        <!-- Bản đồ Mapbox thời gian thực -->
                        <MapboxMap
                            :markers="mapMarkers"
                            class="absolute inset-0"
                            @select="onMapSelect"
                        />
                        <div
                            v-if="mapMarkers.length === 0"
                            class="pointer-events-none absolute inset-x-0 top-3 flex justify-center"
                        >
                            <span
                                class="rounded-full bg-white/90 px-3 py-1 text-xs text-gray-500 shadow"
                            >
                                Chưa có xe gửi vị trí GPS
                            </span>
                        </div>

                        <!-- Selected trip popup -->
                        <div
                            v-if="selectedTrip"
                            class="absolute right-4 bottom-4 left-4 rounded-xl border border-slate-200 bg-white p-4 shadow-lg"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <p
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        {{ selectedTrip.trip_code }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-gray-500">
                                        {{ selectedTrip.route_name }}
                                    </p>
                                </div>
                                <button
                                    @click="selectedTrip = null"
                                    class="text-gray-400 hover:text-gray-600"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-3 text-xs">
                                <div>
                                    <p class="text-gray-400">Tài xế</p>
                                    <p class="font-medium text-gray-800">
                                        {{ selectedTrip.driver_name }}
                                    </p>
                                    <p class="text-gray-400">
                                        ⭐ {{ selectedTrip.driver_rating }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-400">Hành khách</p>
                                    <p class="font-medium text-gray-800">
                                        {{ selectedTrip.passenger_count }}/{{
                                            selectedTrip.total_seats
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-400">ETA</p>
                                    <p
                                        :class="[
                                            'font-medium',
                                            selectedTrip.is_delayed
                                                ? 'text-red-600'
                                                : 'text-gray-800',
                                        ]"
                                    >
                                        {{ selectedTrip.eta_minutes }} phút
                                        <span
                                            v-if="selectedTrip.is_delayed"
                                            class="text-red-500"
                                            >(Chậm)</span
                                        >
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Trip list (40%) -->
                <div
                    class="flex w-2/5 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="border-b border-slate-100 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Chuyến đang hoạt động
                            <span class="ml-1 text-xs font-normal text-gray-400"
                                >({{ filteredTrips.length }})</span
                            >
                        </h3>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <div
                            v-if="filteredTrips.length === 0"
                            class="py-12 text-center text-gray-400"
                        >
                            <svg
                                class="mx-auto mb-2 h-10 w-10"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7"
                                />
                            </svg>
                            <p class="text-sm">Không có chuyến nào</p>
                        </div>
                        <div
                            v-for="t in filteredTrips"
                            :key="t.id"
                            @click="selectedTrip = t"
                            :class="[
                                'cursor-pointer border-b border-slate-50 px-4 py-3 transition-colors hover:bg-slate-50',
                                selectedTrip?.id === t.id
                                    ? 'border-l-2 border-l-red-500 bg-red-50'
                                    : '',
                                t.is_delayed
                                    ? 'border-l-2 border-l-red-400'
                                    : '',
                            ]"
                        >
                            <div class="flex items-start justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p
                                            class="font-mono text-xs font-semibold text-gray-900"
                                        >
                                            {{ t.trip_code }}
                                        </p>
                                        <span
                                            v-if="t.is_delayed"
                                            class="inline-flex items-center rounded bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-600"
                                        >
                                            Chậm
                                        </span>
                                    </div>
                                    <p
                                        class="mt-0.5 truncate text-xs text-gray-500"
                                    >
                                        {{ t.route_name }}
                                    </p>
                                    <div
                                        class="mt-1.5 flex items-center gap-3 text-xs text-gray-600"
                                    >
                                        <span>👤 {{ t.driver_name }}</span>
                                        <span>🚌 {{ t.vehicle_type }}</span>
                                    </div>
                                    <div
                                        class="mt-1 flex items-center gap-3 text-xs text-gray-500"
                                    >
                                        <span>🕐 {{ t.departure_time }}</span>
                                        <span
                                            >👥 {{ t.passenger_count }}/{{
                                                t.total_seats
                                            }}</span
                                        >
                                        <span v-if="t.current_speed > 0"
                                            >⚡ {{ t.current_speed }} km/h</span
                                        >
                                    </div>
                                </div>
                                <span
                                    :class="[
                                        'ml-2 inline-flex shrink-0 rounded-full px-2 py-0.5 text-xs font-medium',
                                        statusMap[t.status]?.class ??
                                            'bg-gray-100 text-gray-600',
                                    ]"
                                >
                                    {{ statusMap[t.status]?.label ?? t.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
