<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { driverApi } from '@/api/driver.api';
import MapboxMap from '@/components/MapboxMap.vue';
import type { MapMarker } from '@/components/MapboxMap.vue';
import {
    buildNavigationWaypoints,
    fetchNavigationRoute,
} from '@/services/navigation-route.service';
import type {
    NavigationRouteResult,
    NavigationWaypoint,
} from '@/services/navigation-route.service';
import { useDriverStore } from '@/stores/driver.store';

const route = useRoute();
const store = useDriverStore();
const tripId = route.params.id as string;

const trip = ref<any>(null);
const passengers = ref<any[]>([]);
const isLoading = ref(true);
const errorMsg = ref('');
const gpsActive = ref(false);
const gpsLastUpdate = ref<string | null>(null);
const currentPos = ref<{ lat: number; lng: number } | null>(null);
const navigationWaypoints = ref<NavigationWaypoint[]>([]);
const navigationRoute = ref<NavigationRouteResult | null>(null);
const routeLoading = ref(false);
const routeError = ref('');
let routedFromActualGps = false;

let locationInterval: ReturnType<typeof setInterval> | null = null;
let watchId: number | null = null;
const mapMarkers = computed<MapMarker[]>(() =>
    navigationWaypoints.value.map((waypoint, index) => ({
        id: `${tripId}-${index}`,
        lat: waypoint.lat,
        lng: waypoint.lng,
        color:
            index === 0
                ? '#16a34a'
                : index === navigationWaypoints.value.length - 1
                  ? '#dc2626'
                  : '#2563eb',
        label: waypoint.label,
    })),
);

const nextPassenger = computed(() => {
    return (
        passengers.value.find(
            (p) => !p.checked_in && p.booking_status !== 'no_show',
        ) ?? null
    );
});

const nextStop = computed(() => nextPassenger.value?.pickup_stop ?? null);

const checkedCount = computed(
    () => passengers.value.filter((p) => p.checked_in).length,
);
const pendingStops = computed(() =>
    passengers.value.filter(
        (p) => !p.checked_in && p.booking_status !== 'no_show',
    ),
);

function openGoogleMaps() {
    const dest = nextStop.value;
    if (!dest?.lat || !dest?.lng) {
        const query = encodeURIComponent(
            (dest?.address ?? nextStop.value?.stop_name) || 'Hải Phòng',
        );
        window.open(`https://maps.google.com/?q=${query}`, '_blank');
        return;
    }
    window.open(
        `https://www.google.com/maps/dir/?api=1&destination=${dest.lat},${dest.lng}&travelmode=driving`,
        '_blank',
    );
}

function updateMapPosition(lat: number, lng: number) {
    currentPos.value = { lat, lng };
    if (!routedFromActualGps && passengers.value.length > 0) {
        routedFromActualGps = true;
        void loadNavigationRoute();
    }
}

async function loadNavigationRoute() {
    if (!currentPos.value) {
        navigationWaypoints.value = [];
        navigationRoute.value = null;
        routeError.value =
            'Vui lòng cho phép truy cập GPS để sinh tuyến đón khách.';
        return;
    }

    routeLoading.value = true;
    routeError.value = '';

    try {
        navigationWaypoints.value = buildNavigationWaypoints(
            currentPos.value,
            passengers.value,
        );
        navigationRoute.value =
            navigationWaypoints.value.length >= 2
                ? await fetchNavigationRoute(navigationWaypoints.value)
                : null;
    } catch (error) {
        navigationRoute.value = null;
        routeError.value =
            error instanceof Error
                ? error.message
                : 'Không thể sinh tuyến đón khách';
    } finally {
        routeLoading.value = false;
    }
}

function formatDistance(meters: number) {
    return meters >= 1000
        ? `${(meters / 1000).toFixed(1)} km`
        : `${Math.round(meters)} m`;
}

function formatDuration(seconds: number) {
    const minutes = Math.max(1, Math.round(seconds / 60));
    return minutes >= 60
        ? `${Math.floor(minutes / 60)} giờ ${minutes % 60} phút`
        : `${minutes} phút`;
}

async function sendLocation(lat: number, lng: number) {
    await driverApi.updateLocation({ trip_id: tripId, lat, lng });
    gpsLastUpdate.value = new Date().toLocaleTimeString('vi-VN', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function startTracking() {
    if (!navigator.geolocation) {
        routeError.value = 'Thiết bị không hỗ trợ định vị GPS.';
        return;
    }
    watchId = navigator.geolocation.watchPosition(
        (pos) => {
            updateMapPosition(pos.coords.latitude, pos.coords.longitude);
            gpsActive.value = true;
            void sendLocation(pos.coords.latitude, pos.coords.longitude);
        },
        () => {
            gpsActive.value = false;
            routeError.value =
                'Không thể lấy vị trí. Hãy bật GPS và cấp quyền định vị cho trình duyệt.';
        },
        { enableHighAccuracy: true, maximumAge: 5000, timeout: 10000 },
    );
    locationInterval = setInterval(async () => {
        if (currentPos.value) {
            await sendLocation(currentPos.value.lat, currentPos.value.lng);
        }
    }, 15000);
}

async function loadData() {
    const [tripRes, passRes] = await Promise.all([
        driverApi.getTrip(tripId),
        driverApi.getPassengers(tripId),
    ]);
    if (tripRes.error || passRes.error) {
        errorMsg.value = 'Không thể tải dữ liệu chuyến';
        return;
    }
    trip.value = tripRes.data;
    passengers.value = passRes.data ?? [];
    store.passengers = passengers.value;
    await loadNavigationRoute();
}

onMounted(async () => {
    isLoading.value = true;
    await loadData();
    isLoading.value = false;
    startTracking();
});

onUnmounted(() => {
    if (locationInterval) clearInterval(locationInterval);
    if (watchId !== null) navigator.geolocation.clearWatch(watchId);
});
</script>

<template>
    <div class="mx-auto max-w-[1400px] p-4 sm:p-6">
        <!-- Header -->
        <div
            class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div class="mb-1 flex items-center gap-2 text-sm text-gray-500">
                    <router-link
                        :to="`/driver/trips/${tripId}`"
                        class="transition-colors hover:text-green-600"
                        >← Chi tiết chuyến</router-link
                    >
                    <span>/</span>
                    <span class="font-medium text-gray-700">Điều hướng</span>
                </div>
                <h1
                    class="flex items-center gap-2 text-xl font-bold text-gray-900"
                >
                    Điều hướng chuyến đi
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-sm font-normal text-green-700"
                    >
                        <span
                            class="h-2 w-2 animate-pulse rounded-full bg-green-500"
                        />
                        Đang chạy
                    </span>
                </h1>
            </div>

            <!-- GPS status -->
            <div
                :class="[
                    'flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium',
                    gpsActive
                        ? 'bg-green-50 text-green-700'
                        : 'bg-gray-100 text-gray-500',
                ]"
            >
                <span
                    :class="[
                        'h-2.5 w-2.5 rounded-full',
                        gpsActive
                            ? 'animate-pulse bg-green-500'
                            : 'bg-gray-400',
                    ]"
                />
                <span>{{ gpsActive ? '🟢 GPS đang gửi' : '⚪ GPS tắt' }}</span>
                <span v-if="gpsLastUpdate" class="text-xs opacity-70"
                    >· {{ gpsLastUpdate }}</span
                >
            </div>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="grid gap-5 lg:grid-cols-[1fr_380px]">
            <div class="h-[600px] animate-pulse rounded-xl bg-gray-200" />
            <div class="space-y-4">
                <div
                    v-for="i in 3"
                    :key="i"
                    class="h-40 animate-pulse rounded-xl bg-gray-200"
                />
            </div>
        </div>

        <!-- Error -->
        <div
            v-else-if="errorMsg"
            class="rounded-xl border border-red-200 bg-red-50 p-6 text-center text-red-700"
        >
            <p class="mb-3 font-medium">{{ errorMsg }}</p>
            <button @click="loadData" class="text-sm font-semibold underline">
                Thử lại
            </button>
        </div>

        <!-- Content -->
        <div v-else class="grid items-start gap-5 lg:grid-cols-[1fr_380px]">
            <!-- ─── LEFT: Map ─────────────────────────────────────── -->
            <div class="space-y-3">
                <!-- Map container -->
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <div
                        class="relative h-[420px] w-full bg-slate-100 sm:h-[560px]"
                    >
                        <MapboxMap
                            :markers="mapMarkers"
                            :route-coordinates="
                                navigationRoute?.coordinates ?? []
                            "
                            :center="
                                currentPos
                                    ? [currentPos.lng, currentPos.lat]
                                    : undefined
                            "
                            :zoom="currentPos ? 13 : 8.5"
                        />
                        <div
                            v-if="currentPos"
                            class="pointer-events-none absolute bottom-4 left-4 rounded-lg bg-white/90 px-3 py-2 shadow-sm"
                        >
                            <p
                                class="font-mono text-xs font-medium text-green-700"
                            >
                                {{ currentPos.lat.toFixed(5) }},
                                {{ currentPos.lng.toFixed(5) }}
                            </p>
                        </div>
                        <div
                            v-if="routeLoading"
                            class="absolute top-4 left-4 rounded-lg bg-white/95 px-3 py-2 text-xs font-semibold text-green-700 shadow-sm"
                        >
                            Đang sinh tuyến đón khách…
                        </div>
                    </div>
                </div>

                <div
                    v-if="routeError"
                    class="flex items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700"
                >
                    <span>{{ routeError }}</span>
                    <button
                        class="font-semibold underline"
                        @click="loadNavigationRoute"
                    >
                        Thử lại
                    </button>
                </div>

                <div
                    v-else-if="navigationRoute"
                    class="grid grid-cols-2 gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-sm"
                >
                    <div>
                        <p class="text-xs text-green-600">Tổng quãng đường</p>
                        <p class="font-bold text-green-800">
                            {{ formatDistance(navigationRoute.distanceMeters) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-green-600">Thời gian dự kiến</p>
                        <p class="font-bold text-green-800">
                            {{
                                formatDuration(navigationRoute.durationSeconds)
                            }}
                        </p>
                    </div>
                </div>

                <!-- Map action buttons -->
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button
                        @click="openGoogleMaps"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white py-3 text-sm font-semibold text-gray-700 shadow-sm transition-colors hover:border-green-400 hover:text-green-700"
                    >
                        🗺️ Mở Google Maps
                    </button>
                    <a
                        v-if="trip?.operator?.phone"
                        :href="`tel:${trip.operator.phone}`"
                        class="flex items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-semibold text-red-600 transition-colors hover:bg-red-100"
                    >
                        🚨 Gọi nhà xe khi có sự cố
                    </a>
                </div>
            </div>

            <!-- ─── RIGHT: Navigation panel ──────────────────────── -->
            <div class="space-y-4 self-start lg:sticky lg:top-6">
                <!-- Stop progress -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-500"
                            >Tiến độ đón khách</span
                        >
                        <span class="font-bold text-green-600"
                            >{{ checkedCount }}/{{ passengers.length }}</span
                        >
                    </div>
                    <div class="h-2 w-full rounded-full bg-gray-100">
                        <div
                            class="h-2 rounded-full bg-green-500 transition-all duration-500"
                            :style="{
                                width:
                                    passengers.length > 0
                                        ? (checkedCount / passengers.length) *
                                              100 +
                                          '%'
                                        : '0%',
                            }"
                        />
                    </div>
                </div>

                <!-- Current stop card -->
                <div
                    v-if="nextStop"
                    class="rounded-xl border-2 border-green-500 bg-white p-5 shadow-sm"
                >
                    <div class="mb-3 flex items-center gap-2">
                        <div
                            class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-green-600 text-xs font-black text-white"
                        >
                            {{ checkedCount + 1 }}
                        </div>
                        <span
                            class="text-xs font-semibold tracking-wider text-green-600 uppercase"
                            >Điểm đón tiếp theo</span
                        >
                    </div>

                    <h2 class="mb-1 text-xl font-black text-gray-900">
                        {{ nextStop.stop_name }}
                    </h2>
                    <p class="mb-4 text-sm text-gray-500">
                        {{ nextStop.address }}
                    </p>

                    <!-- ETA thật từ chặng đầu của Mapbox Directions -->
                    <div
                        class="mb-4 flex items-center gap-4 rounded-xl bg-green-50 p-3"
                    >
                        <div>
                            <p class="text-2xl font-black text-green-700">
                                {{
                                    navigationRoute
                                        ? formatDuration(
                                              navigationRoute.nextStopDurationSeconds,
                                          )
                                        : 'Đang tính…'
                                }}
                            </p>
                            <p class="text-xs text-green-600">
                                {{
                                    navigationRoute
                                        ? formatDistance(
                                              navigationRoute.nextStopDistanceMeters,
                                          )
                                        : '—'
                                }}
                            </p>
                        </div>
                        <div class="h-10 w-px bg-green-200" />
                        <p class="flex-1 text-xs text-green-600">
                            Dựa trên vị trí GPS hiện tại
                        </p>
                    </div>

                    <!-- Passenger waiting -->
                    <div
                        v-if="nextPassenger"
                        class="mb-4 flex items-center gap-3"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 font-bold text-green-700"
                        >
                            {{ nextPassenger.passenger_name?.[0] ?? 'K' }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-gray-900">
                                {{ nextPassenger.passenger_name }}
                            </p>
                            <p class="font-mono text-xs text-gray-400">
                                Ghế {{ nextPassenger.seat_codes?.join(', ') }}
                            </p>
                        </div>
                        <a
                            :href="`tel:${nextPassenger.passenger_phone}`"
                            class="flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-green-700"
                        >
                            📞 Gọi ngay
                        </a>
                    </div>

                    <!-- Arrived button -->
                    <router-link
                        :to="`/driver/trips/${tripId}`"
                        class="block w-full rounded-xl bg-green-600 py-3.5 text-center font-bold text-white transition-colors hover:bg-green-700"
                    >
                        ✅ Đã đến — Check-in khách
                    </router-link>
                </div>

                <!-- All checked in -->
                <div
                    v-else
                    class="rounded-xl border border-green-200 bg-green-50 p-5 text-center"
                >
                    <div class="mb-2 text-3xl">🎉</div>
                    <p class="font-bold text-green-700">
                        Đã đón hết hành khách!
                    </p>
                    <p class="mt-1 text-sm text-green-600">
                        Tiếp tục đến điểm trả hàng
                    </p>
                </div>

                <!-- Upcoming stops list -->
                <div
                    v-if="pendingStops.length > 1"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <div class="border-b border-gray-100 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-700">
                            Điểm đón còn lại
                        </h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="(p, i) in pendingStops.slice(1, 4)"
                            :key="p.id"
                            class="flex items-center gap-3 px-4 py-3"
                        >
                            <div
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600"
                            >
                                {{ checkedCount + i + 2 }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-medium text-gray-800"
                                >
                                    {{ p.pickup_stop?.stop_name }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ p.passenger_name }}
                                </p>
                            </div>
                            <span class="text-xs text-gray-400"
                                >Ghế {{ p.seat_codes?.[0] }}</span
                            >
                        </div>
                        <div
                            v-if="pendingStops.length > 4"
                            class="px-4 py-2 text-center text-xs text-gray-400"
                        >
                            +{{ pendingStops.length - 4 }} điểm đón nữa
                        </div>
                    </div>
                </div>

                <!-- Destination -->
                <div
                    class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600"
                    >
                        🏁
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-400">
                            Điểm đến cuối
                        </p>
                        <p class="font-semibold text-gray-900">
                            {{ trip?.route?.dest_city ?? 'Hải Phòng' }}
                        </p>
                    </div>
                    <div class="ml-auto text-right">
                        <p class="text-xs text-gray-400">ETA</p>
                        <p class="font-bold text-gray-700">
                            ~{{ trip?.route?.est_duration_min ?? 120 }} phút
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
