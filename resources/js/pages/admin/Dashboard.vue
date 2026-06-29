<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { adminApi } from '@/api/admin.api';
import MapboxMap from '@/components/MapboxMap.vue';
import type { MapMarker } from '@/components/MapboxMap.vue';

interface DashboardStats {
    bookings_today: number;
    revenue_today: number;
    active_trips: number;
    new_users_today: number;
    pending_operators: number;
    pending_drivers: number;
}

interface RecentBooking {
    code: string;
    customer: string;
    route: string;
    amount: number;
    status: string;
    created_at: string;
}

const stats = ref<DashboardStats | null>(null);
const recentBookings = ref<RecentBooking[]>([]);
const isLoading = ref(true);
const errorMsg = ref('');
let refreshTimer: ReturnType<typeof setInterval> | null = null;

// Bản đồ xe đang chạy (mini-map)
interface MapTrip {
    id: string;
    lat: number;
    lng: number;
    vehicle_plate: string;
    trip_code: string;
}
const mapTrips = ref<MapTrip[]>([]);
const mapMarkers = computed<MapMarker[]>(() =>
    mapTrips.value
        .filter((t) => t.lat !== 0 || t.lng !== 0)
        .map((t) => ({
            id: t.id,
            lat: t.lat,
            lng: t.lng,
            color: '#16a34a',
            label: `${t.vehicle_plate} · ${t.trip_code}`,
        })),
);

const statusMap: Record<string, { label: string; class: string }> = {
    pending: { label: 'Chờ xử lý', class: 'bg-yellow-100 text-yellow-700' },
    confirmed: { label: 'Đã xác nhận', class: 'bg-blue-100 text-blue-700' },
    in_progress: { label: 'Đang đi', class: 'bg-green-100 text-green-700' },
    completed: { label: 'Hoàn thành', class: 'bg-green-50 text-green-700' },
    cancelled: { label: 'Đã hủy', class: 'bg-red-100 text-red-700' },
};

function formatCurrency(val: number) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(val);
}

async function loadDashboard() {
    const { data, error } = await adminApi.getDashboard();
    if (error) {
        errorMsg.value = error;
        isLoading.value = false;
        return;
    }
    stats.value = data.stats;
    recentBookings.value = data.recent_bookings ?? [];
    isLoading.value = false;

    const mapRes = await adminApi.getDashboardMap();
    if (!mapRes.error) mapTrips.value = (mapRes.data as MapTrip[]) ?? [];
}

onMounted(() => {
    loadDashboard();
    refreshTimer = setInterval(loadDashboard, 30_000);
});
onUnmounted(() => {
    if (refreshTimer) clearInterval(refreshTimer);
});
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">
                    Tổng quan hệ thống
                </h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    Dữ liệu thời gian thực — tự động cập nhật mỗi 30s
                </p>
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-500">
                <span
                    class="inline-block h-2 w-2 animate-pulse rounded-full bg-green-500"
                />
                Đang theo dõi trực tiếp
            </div>
        </div>

        <!-- Loading -->
        <div v-if="isLoading">
            <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div
                    v-for="i in 4"
                    :key="i"
                    class="h-28 animate-pulse rounded-xl border border-slate-200 bg-white p-5"
                />
            </div>
            <div class="mb-6 grid grid-cols-2 gap-4">
                <div
                    v-for="i in 2"
                    :key="i"
                    class="h-24 animate-pulse rounded-xl border border-slate-200 bg-white p-5"
                />
            </div>
            <div
                class="h-64 animate-pulse rounded-xl border border-slate-200 bg-white p-5"
            />
        </div>

        <!-- Error -->
        <div
            v-else-if="errorMsg"
            class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-5 text-red-700"
        >
            <svg
                class="h-5 w-5 shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </svg>
            <span>{{ errorMsg }}</span>
            <button @click="loadDashboard" class="ml-auto text-sm underline">
                Thử lại
            </button>
        </div>

        <template v-else-if="stats">
            <!-- KPI Row 1 -->
            <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs font-medium tracking-wide text-gray-500 uppercase"
                    >
                        Booking hôm nay
                    </p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">
                        {{ stats.bookings_today }}
                    </p>
                    <p class="mt-1 text-xs text-green-600">
                        Tổng đơn đặt vé trong ngày
                    </p>
                </div>
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs font-medium tracking-wide text-gray-500 uppercase"
                    >
                        Doanh thu hôm nay
                    </p>
                    <p class="mt-1 text-2xl font-bold text-red-600">
                        {{ formatCurrency(stats.revenue_today) }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">Trước phí hoa hồng</p>
                </div>
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs font-medium tracking-wide text-gray-500 uppercase"
                    >
                        Xe đang chạy
                    </p>
                    <p class="mt-1 text-3xl font-bold text-blue-600">
                        {{ stats.active_trips }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">
                        Chuyến đang trong lộ trình
                    </p>
                </div>
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs font-medium tracking-wide text-gray-500 uppercase"
                    >
                        User mới hôm nay
                    </p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">
                        {{ stats.new_users_today }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">
                        Tài khoản đăng ký mới
                    </p>
                </div>
            </div>

            <!-- KPI Row 2 — Pending approvals -->
            <div class="mb-6 grid grid-cols-2 gap-4">
                <router-link
                    to="/admin/operators"
                    class="group flex items-center gap-4 rounded-xl border border-orange-200 bg-white p-5 shadow-sm transition-colors hover:border-orange-300"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-100"
                    >
                        <svg
                            class="h-6 w-6 text-orange-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"
                            />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">
                            Nhà xe chờ duyệt
                        </p>
                        <div class="mt-0.5 flex items-center gap-2">
                            <p class="text-2xl font-bold text-gray-900">
                                {{ stats.pending_operators }}
                            </p>
                            <span
                                class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700"
                            >
                                Cần xử lý
                            </span>
                        </div>
                    </div>
                    <svg
                        class="ml-auto h-4 w-4 text-gray-400 transition-colors group-hover:text-orange-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </router-link>

                <router-link
                    to="/admin/drivers"
                    class="group flex items-center gap-4 rounded-xl border border-orange-200 bg-white p-5 shadow-sm transition-colors hover:border-orange-300"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-100"
                    >
                        <svg
                            class="h-6 w-6 text-orange-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">
                            Tài xế chờ duyệt
                        </p>
                        <div class="mt-0.5 flex items-center gap-2">
                            <p class="text-2xl font-bold text-gray-900">
                                {{ stats.pending_drivers }}
                            </p>
                            <span
                                class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700"
                            >
                                Cần xử lý
                            </span>
                        </div>
                    </div>
                    <svg
                        class="ml-auto h-4 w-4 text-gray-400 transition-colors group-hover:text-orange-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </router-link>
            </div>

            <!-- Map placeholder + Recent bookings -->
            <div class="grid grid-cols-5 gap-6">
                <!-- Theo dõi xe trực tiếp (mini-map) -->
                <div
                    class="col-span-2 flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
                    >
                        <h3 class="text-sm font-semibold text-gray-900">
                            Theo dõi xe trực tiếp
                        </h3>
                        <router-link
                            to="/admin/trips/live"
                            class="text-xs font-medium text-red-600 hover:text-red-700"
                            >Xem bản đồ đầy đủ →</router-link
                        >
                    </div>
                    <div class="relative min-h-[16rem] flex-1">
                        <MapboxMap :markers="mapMarkers" />
                        <div
                            v-if="mapMarkers.length === 0"
                            class="pointer-events-none absolute inset-x-0 top-3 flex justify-center"
                        >
                            <span
                                class="rounded-full bg-white/90 px-3 py-1 text-xs text-gray-500 shadow"
                            >
                                {{ stats.active_trips }} xe đang chạy — chưa có
                                vị trí GPS
                            </span>
                        </div>
                        <router-link
                            to="/admin/trips/live"
                            class="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-lg bg-red-600 px-3 py-1.5 text-xs text-white shadow hover:bg-red-700"
                        >
                            Mở bản đồ đầy đủ
                        </router-link>
                    </div>
                </div>

                <!-- Recent bookings -->
                <div
                    class="col-span-3 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Booking gần đây
                        </h3>
                    </div>
                    <div
                        v-if="recentBookings.length === 0"
                        class="py-12 text-center text-sm text-gray-400"
                    >
                        Chưa có booking nào hôm nay
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Mã vé
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Khách hàng
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Tuyến
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-right text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Giá vé
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-center text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Trạng thái
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="b in recentBookings"
                                    :key="b.code"
                                    class="transition-colors hover:bg-slate-50"
                                >
                                    <td
                                        class="px-4 py-3 font-mono text-xs text-gray-900"
                                    >
                                        {{ b.code }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ b.customer }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        {{ b.route }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-medium text-gray-900"
                                    >
                                        {{ formatCurrency(b.amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            :class="[
                                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                                statusMap[b.status]?.class ??
                                                    'bg-gray-100 text-gray-600',
                                            ]"
                                        >
                                            {{
                                                statusMap[b.status]?.label ??
                                                b.status
                                            }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
