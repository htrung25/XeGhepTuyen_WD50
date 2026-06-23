<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { customerApi } from '@/api/customer.api';

const route = useRoute();
const bookingId = route.params.id as string;
const tracking = ref<any>(null);
const isLoading = ref(true);
const errorMsg = ref('');
const driverLat = ref<number | null>(null);
const driverLng = ref<number | null>(null);
const etaMinutes = ref<number | null>(null);
const lastUpdate = ref<string | null>(null);
const mapRef = ref<HTMLElement | null>(null);
let map: any = null;
let driverMarker: any = null;
let echoChannel: any = null;

const stops = [
    { label: 'Mỹ Đình', status: 'done', time: '06:05' },
    { label: 'Cầu Giấy', status: 'current', time: '~06:15', you: true },
    { label: 'Gia Lâm', status: 'upcoming', time: '~06:35' },
    {
        label: 'Trung tâm HP',
        status: 'upcoming',
        time: '~08:00',
        destination: true,
    },
];

function initMap(lat: number, lng: number) {
    if (!(window as any).google || !mapRef.value) return;
    const google = (window as any).google;
    map = new google.maps.Map(mapRef.value, {
        center: { lat, lng },
        zoom: 12,
        styles: [{ featureType: 'poi', stylers: [{ visibility: 'off' }] }],
    });
    driverMarker = new google.maps.Marker({
        position: { lat, lng },
        map,
        title: 'Xe đang chạy',
        icon: {
            url:
                'data:image/svg+xml,' +
                encodeURIComponent(
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#2563EB" width="32" height="32"><circle cx="12" cy="12" r="10" fill="#2563EB"/><text x="12" y="16" text-anchor="middle" fill="white" font-size="12">🚐</text></svg>',
                ),
            scaledSize: new google.maps.Size(40, 40),
        },
    });
}

function updateMapMarker(lat: number, lng: number) {
    if (!map || !driverMarker) {
        initMap(lat, lng);
        return;
    }
    const pos = { lat, lng };
    driverMarker.setPosition(pos);
    map.panTo(pos);
}

function setupWebSocket(tripId: string) {
    if (!(window as any).Echo) return;
    echoChannel = (window as any).Echo.channel(`trips.${tripId}`).listen(
        '.driver.location.updated',
        (e: any) => {
            driverLat.value = e.lat;
            driverLng.value = e.lng;
            etaMinutes.value = e.eta_minutes;
            lastUpdate.value = new Date().toLocaleTimeString('vi-VN', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
            });
            updateMapMarker(e.lat, e.lng);
        },
    );
}

function copyPhone(phone: string) {
    navigator.clipboard?.writeText(phone);
}

function copyShareLink() {
    navigator.clipboard?.writeText(window.location.href);
}

onMounted(async () => {
    if (!bookingId) return;
    const { data, error } = await customerApi.trackBooking(bookingId);
    isLoading.value = false;
    if (error) {
        errorMsg.value = 'Không thể tải thông tin theo dõi.';
        return;
    }
    tracking.value = data;

    if (data?.driver_lat && data?.driver_lng) {
        driverLat.value = data.driver_lat;
        driverLng.value = data.driver_lng;
        etaMinutes.value = data.eta_minutes;
        lastUpdate.value = new Date().toLocaleTimeString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        });
        initMap(data.driver_lat, data.driver_lng);
    }

    if (data?.trip_id) setupWebSocket(data.trip_id);
});

onUnmounted(() => {
    if (echoChannel && tracking.value?.trip_id)
        (window as any).Echo?.leave(`trips.${tracking.value.trip_id}`);
});
</script>

<template>
    <div class="mx-auto max-w-5xl px-6 py-8">
        <!-- Title row -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">
                    Theo dõi chuyến đi
                </h1>
                <p
                    v-if="tracking?.booking_code"
                    class="mt-0.5 font-mono text-sm text-gray-500"
                >
                    {{ tracking.booking_code }}
                </p>
            </div>
            <router-link
                to="/bookings"
                class="flex items-center gap-1 text-sm font-medium text-gray-600 transition-colors hover:text-blue-600"
            >
                ← Vé của tôi
            </router-link>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="flex justify-center py-20">
            <div
                class="h-8 w-8 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"
            />
        </div>

        <!-- Error -->
        <div
            v-else-if="errorMsg"
            class="rounded-xl border border-red-200 bg-red-50 p-6 text-center text-red-700"
        >
            <p class="mb-3 font-medium">{{ errorMsg }}</p>
            <router-link
                to="/bookings"
                class="rounded-lg border border-red-300 px-5 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50"
            >
                ← Quay lại
            </router-link>
        </div>

        <div v-else class="grid grid-cols-[1fr_340px] gap-6">
            <!-- ─── LEFT: Map ──────────────────────────────── -->
            <div class="space-y-4">
                <!-- Map container -->
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <div ref="mapRef" class="h-[500px] w-full bg-slate-100">
                        <!-- Fallback while map loads -->
                        <div
                            v-if="!driverLat"
                            class="flex h-full w-full flex-col items-center justify-center text-gray-400"
                        >
                            <div class="mb-3 text-5xl">🗺️</div>
                            <p class="text-sm font-medium">
                                {{
                                    tracking?.trip_status === 'in_progress'
                                        ? 'Đang tải bản đồ...'
                                        : 'Chuyến chưa bắt đầu'
                                }}
                            </p>
                            <p
                                v-if="tracking?.trip_status !== 'in_progress'"
                                class="mt-1 text-xs"
                            >
                                Bản đồ sẽ hiển thị khi xe bắt đầu chạy
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Share section -->
                <div
                    class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <div>
                        <p class="text-sm font-medium text-gray-700">
                            Chia sẻ hành trình cho người thân
                        </p>
                        <p class="mt-0.5 text-xs text-gray-500">
                            Họ có thể theo dõi xe của bạn theo thời gian thực
                        </p>
                    </div>
                    <button
                        @click="copyShareLink()"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors hover:bg-gray-50"
                    >
                        🔗 Sao chép link
                    </button>
                </div>
            </div>

            <!-- ─── RIGHT: Status panel ────────────────────── -->
            <div class="space-y-4">
                <!-- ETA card -->
                <div
                    class="rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 p-5 text-white"
                >
                    <p class="mb-1 text-sm text-blue-100">
                        Tài xế đến điểm đón của bạn
                    </p>
                    <p class="mb-1 text-4xl font-bold tabular-nums">
                        {{ etaMinutes !== null ? `~${etaMinutes} phút` : '—' }}
                    </p>
                    <p class="text-xs text-blue-200">
                        {{
                            lastUpdate
                                ? `Cập nhật lúc ${lastUpdate}`
                                : 'Chưa có cập nhật'
                        }}
                    </p>
                </div>

                <!-- Driver info -->
                <div
                    v-if="tracking?.driver"
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <div class="mb-3 flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-lg font-bold text-blue-700"
                        >
                            {{ tracking.driver.full_name?.charAt(0) ?? 'T' }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">
                                {{ tracking.driver.full_name }}
                            </p>
                            <div class="flex items-center gap-1">
                                <svg
                                    class="h-3.5 w-3.5 fill-yellow-400 text-yellow-400"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                    />
                                </svg>
                                <span class="text-xs text-gray-600">{{
                                    tracking.driver.rating_avg?.toFixed(1) ??
                                    '4.8'
                                }}</span>
                            </div>
                        </div>
                    </div>
                    <p class="mb-3 text-xs text-gray-500">
                        {{ tracking.vehicle?.plate_number ?? '—' }} ·
                        {{ tracking.vehicle?.brand ?? '' }}
                        {{ tracking.vehicle?.model ?? '' }}
                    </p>
                    <div class="flex gap-2">
                        <a
                            :href="`tel:${tracking.driver.phone}`"
                            class="flex-1 rounded-lg bg-blue-600 py-2 text-center text-xs font-semibold text-white transition-colors hover:bg-blue-700"
                        >
                            📞 Gọi tài xế
                        </a>
                        <button
                            @click="copyPhone(tracking.driver.phone)"
                            class="flex-1 rounded-lg border border-gray-300 py-2 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Sao chép SĐT
                        </button>
                    </div>
                </div>

                <!-- Stop timeline -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <h3 class="mb-4 text-sm font-semibold text-gray-900">
                        Lộ trình
                    </h3>
                    <div class="space-y-3">
                        <div
                            v-for="(stop, i) in stops"
                            :key="i"
                            class="flex items-start gap-3"
                        >
                            <!-- Timeline dot -->
                            <div class="mt-0.5 flex flex-col items-center">
                                <div
                                    :class="[
                                        'flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full border-2',
                                        stop.status === 'done'
                                            ? 'border-green-500 bg-green-500'
                                            : stop.status === 'current'
                                              ? 'border-blue-600 bg-blue-600 ring-2 ring-blue-200'
                                              : 'border-gray-300 bg-white',
                                    ]"
                                >
                                    <svg
                                        v-if="stop.status === 'done'"
                                        class="h-2.5 w-2.5 text-white"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="3"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                </div>
                                <div
                                    v-if="i < stops.length - 1"
                                    :class="[
                                        'mt-0.5 h-6 w-0.5',
                                        stop.status === 'done'
                                            ? 'bg-green-300'
                                            : 'bg-gray-200',
                                    ]"
                                />
                            </div>
                            <!-- Stop info -->
                            <div class="min-w-0 flex-1 pb-1">
                                <div class="flex items-center justify-between">
                                    <p
                                        :class="[
                                            'text-sm font-medium',
                                            stop.status === 'current'
                                                ? 'text-blue-700'
                                                : stop.status === 'done'
                                                  ? 'text-gray-500 line-through'
                                                  : 'text-gray-800',
                                        ]"
                                    >
                                        {{ stop.label }}
                                        <span
                                            v-if="stop.you"
                                            class="ml-1 rounded bg-blue-100 px-1.5 py-0.5 text-xs font-semibold text-blue-600 not-italic no-underline"
                                            >Bạn</span
                                        >
                                        <span
                                            v-if="stop.destination"
                                            class="ml-1 rounded bg-green-100 px-1.5 py-0.5 text-xs font-semibold text-green-600"
                                            >Điểm trả</span
                                        >
                                    </p>
                                    <span
                                        :class="[
                                            'text-xs tabular-nums',
                                            stop.status === 'done'
                                                ? 'font-medium text-green-600'
                                                : stop.status === 'current'
                                                  ? 'font-medium text-blue-600'
                                                  : 'text-gray-400',
                                        ]"
                                    >
                                        {{ stop.time }}
                                    </span>
                                </div>
                                <p
                                    v-if="stop.status === 'done'"
                                    class="text-xs text-green-600"
                                >
                                    Đã đón
                                </p>
                                <p
                                    v-else-if="stop.status === 'current'"
                                    class="text-xs font-medium text-blue-600"
                                >
                                    Đang đến · ETA {{ stop.time }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
