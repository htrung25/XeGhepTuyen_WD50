<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { driverApi } from '@/api/driver.api';
import { useDriverStore } from '@/stores/driver.store';

const route = useRoute();
const store = useDriverStore();
const tripId = route.params.id as string;

const trip = ref<any>(null);
const passengers = ref<any[]>([]);
const isLoading = ref(true);
const errorMsg = ref('');
const showIncident = ref(false);
const incidentNote = ref('');
const gpsActive = ref(false);
const gpsLastUpdate = ref<string | null>(null);
const currentPos = ref({ lat: 21.0285, lng: 105.8542 }); // default Hanoi

let locationInterval: ReturnType<typeof setInterval> | null = null;
let watchId: number | null = null;
let mapInstance: any = null;
let driverMarker: any = null;
let mapRef: HTMLElement | null = null;

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

function initMap() {
    if (!(window as any).google || !mapRef) return;
    const google = (window as any).google;
    mapInstance = new google.maps.Map(mapRef, {
        center: currentPos.value,
        zoom: 13,
        mapTypeControl: false,
        fullscreenControl: false,
        streetViewControl: false,
        styles: [{ featureType: 'poi', stylers: [{ visibility: 'off' }] }],
    });
    driverMarker = new google.maps.Marker({
        position: currentPos.value,
        map: mapInstance,
        title: 'Vị trí của bạn',
        icon: {
            url:
                'data:image/svg+xml,' +
                encodeURIComponent(
                    '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36">' +
                        '<circle cx="18" cy="18" r="16" fill="#16A34A" stroke="white" stroke-width="3"/>' +
                        '<text x="18" y="23" text-anchor="middle" fill="white" font-size="14">🚐</text>' +
                        '</svg>',
                ),
            scaledSize: new google.maps.Size(36, 36),
            anchor: new google.maps.Point(18, 18),
        },
    });
}

function updateMapPosition(lat: number, lng: number) {
    currentPos.value = { lat, lng };
    if (mapInstance && driverMarker) {
        driverMarker.setPosition({ lat, lng });
        mapInstance.panTo({ lat, lng });
    }
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
    if (!navigator.geolocation) return;
    gpsActive.value = true;
    watchId = navigator.geolocation.watchPosition(
        (pos) => {
            updateMapPosition(pos.coords.latitude, pos.coords.longitude);
        },
        () => {
            gpsActive.value = false;
        },
        { enableHighAccuracy: true, maximumAge: 5000, timeout: 10000 },
    );
    locationInterval = setInterval(async () => {
        await sendLocation(currentPos.value.lat, currentPos.value.lng);
    }, 15000);
    // Send immediately on start
    sendLocation(currentPos.value.lat, currentPos.value.lng);
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
}

onMounted(async () => {
    isLoading.value = true;
    await loadData();
    isLoading.value = false;
    // Init map after DOM ready
    setTimeout(() => {
        mapRef = document.getElementById('nav-map');
        initMap();
    }, 100);
    startTracking();
});

onUnmounted(() => {
    if (locationInterval) clearInterval(locationInterval);
    if (watchId !== null) navigator.geolocation.clearWatch(watchId);
});
</script>

<template>
    <div class="mx-auto max-w-[1400px] p-6">
        <!-- Header -->
        <div class="mb-5 flex items-center justify-between">
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
        <div v-if="isLoading" class="grid grid-cols-[1fr_380px] gap-5">
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
        <div v-else class="grid grid-cols-[1fr_380px] items-start gap-5">
            <!-- ─── LEFT: Map ─────────────────────────────────────── -->
            <div class="space-y-3">
                <!-- Map container -->
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <div id="nav-map" class="h-[560px] w-full bg-slate-100">
                        <!-- Fallback when Google Maps not available -->
                        <div
                            class="flex h-full w-full flex-col items-center justify-center text-gray-400"
                        >
                            <div class="mb-4 animate-bounce text-6xl">🚐</div>
                            <p class="mb-1 font-semibold text-gray-600">
                                Đang điều hướng GPS
                            </p>
                            <p class="font-mono text-sm text-green-600">
                                {{ currentPos.lat.toFixed(5) }},
                                {{ currentPos.lng.toFixed(5) }}
                            </p>
                            <p class="mt-2 text-xs text-gray-400">
                                Cập nhật mỗi 15 giây
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Map action buttons -->
                <div class="flex gap-3">
                    <button
                        @click="openGoogleMaps"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white py-3 text-sm font-semibold text-gray-700 shadow-sm transition-colors hover:border-green-400 hover:text-green-700"
                    >
                        🗺️ Mở Google Maps
                    </button>
                    <button
                        @click="showIncident = true"
                        class="flex items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-semibold text-red-600 transition-colors hover:bg-red-100"
                    >
                        🚨 Báo sự cố
                    </button>
                </div>
            </div>

            <!-- ─── RIGHT: Navigation panel ──────────────────────── -->
            <div class="sticky top-6 space-y-4 self-start">
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

                    <!-- ETA (mock) -->
                    <div
                        class="mb-4 flex items-center gap-4 rounded-xl bg-green-50 p-3"
                    >
                        <div>
                            <p class="text-2xl font-black text-green-700">
                                ~{{ Math.floor(Math.random() * 8 + 3) }} phút
                            </p>
                            <p class="text-xs text-green-600">
                                {{ (Math.random() * 4 + 0.5).toFixed(1) }} km
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
                        :to="`/driver/checkin/${tripId}`"
                        class="block w-full rounded-xl bg-green-600 py-3.5 text-center font-bold text-white transition-colors hover:bg-green-700"
                    >
                        ✅ Đã đến — Quét QR
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

        <!-- ─── Incident modal ─────────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="showIncident"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                @click.self="showIncident = false"
            >
                <div
                    class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl"
                >
                    <h3 class="mb-1 text-lg font-bold text-gray-900">
                        Báo sự cố
                    </h3>
                    <p class="mb-4 text-sm text-gray-500">
                        Mô tả sự cố đang xảy ra
                    </p>
                    <textarea
                        v-model="incidentNote"
                        rows="3"
                        placeholder="Ví dụ: Xe bị hỏng, tai nạn, kẹt xe..."
                        class="mb-4 w-full resize-none rounded-xl border border-gray-300 px-4 py-3 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                    />
                    <div class="flex gap-3">
                        <button
                            @click="showIncident = false"
                            class="flex-1 rounded-xl border border-gray-200 py-3 font-medium text-gray-600 transition-colors hover:bg-gray-50"
                        >
                            Hủy
                        </button>
                        <button
                            @click="showIncident = false"
                            class="flex-1 rounded-xl bg-red-600 py-3 font-bold text-white transition-colors hover:bg-red-700"
                        >
                            Gửi báo cáo
                        </button>
                    </div>
                    <a
                        href="tel:18009999"
                        class="mt-3 block text-center text-sm font-medium text-red-600 hover:underline"
                    >
                        Gọi ngay hotline hỗ trợ: 1800-9999
                    </a>
                </div>
            </div>
        </Teleport>
    </div>
</template>
