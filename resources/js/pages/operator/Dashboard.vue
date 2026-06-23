<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { operatorApi } from '@/api/operator.api';

interface KpiData {
    gross_revenue: number;
    net_revenue: number;
    commission: number;
    total_trips: number;
    total_bookings: number;
    avg_occupancy: number;
}

interface TripRow {
    id: string;
    depart_at: string;
    route: { origin_city: string; dest_city: string };
    driver: { full_name: string } | null;
    vehicle: { plate: string } | null;
    booking_count: number;
    total_seats: number;
    status: string;
}

interface OnboardingFleet {
    declared_fleet: Record<string, number>;
    declared_summary: string;
    declared_total: number;
    actual_count: number;
    remaining: number;
    fleet_labels: Record<string, string>;
}

const isLoading = ref(true);
const errorMsg = ref('');
const kpi = ref<KpiData | null>(null);
const trips = ref<TripRow[]>([]);
const chartData = ref<{ date: string; revenue: number }[]>([]);
const onboarding = ref<OnboardingFleet | null>(null);

const loadOnboarding = async () => {
    const { data } = await operatorApi.getOnboardingFleet();
    onboarding.value = (data as OnboardingFleet) ?? null;
};

const statusConfig: Record<string, { label: string; class: string }> = {
    scheduled: { label: 'Đã lên lịch', class: 'bg-slate-100 text-slate-600' },
    boarding: { label: 'Đang đón khách', class: 'bg-blue-100 text-blue-700' },
    in_progress: { label: 'Đang chạy', class: 'bg-green-100 text-green-700' },
    completed: { label: 'Hoàn thành', class: 'bg-slate-800 text-white' },
    cancelled: { label: 'Đã hủy', class: 'bg-red-100 text-red-700' },
};

const fmt = (n: number) => new Intl.NumberFormat('vi-VN').format(n) + 'đ';

const load = async () => {
    isLoading.value = true;
    errorMsg.value = '';

    const [summaryRes, tripsRes] = await Promise.all([
        operatorApi.getRevenueSummary({ period: 'today' }),
        operatorApi.getTrips({ date: new Date().toISOString().slice(0, 10) }),
    ]);

    if (summaryRes.error || tripsRes.error) {
        errorMsg.value = 'Không thể tải dữ liệu. Vui lòng thử lại.';
        isLoading.value = false;
        return;
    }

    kpi.value = summaryRes.data;
    trips.value = tripsRes.data ?? [];
    isLoading.value = false;
};

// Compute bar chart heights (relative to max)
const maxRevenue = ref(1);
const barPercent = (v: number) => Math.round((v / maxRevenue.value) * 100);

onMounted(() => {
    load();
    loadOnboarding();
});
</script>

<template>
    <div class="space-y-6 p-6">
        <!-- Page title -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-800">Tổng quan</h1>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{
                        new Date().toLocaleDateString('vi-VN', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                        })
                    }}
                </p>
            </div>
            <button
                class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm text-slate-600 transition-colors hover:bg-slate-50"
                @click="load"
            >
                <svg
                    class="h-4 w-4"
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
                Làm mới
            </button>
        </div>

        <!-- Onboarding: nhắc thêm xe so với cơ cấu đã khai lúc đăng ký -->
        <div
            v-if="onboarding && onboarding.remaining > 0"
            class="rounded-xl border border-amber-200 bg-amber-50 p-5"
        >
            <div class="flex items-start gap-4">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100"
                >
                    <svg
                        class="h-5 w-5 text-amber-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-amber-900">
                        Hoàn tất khai báo đội xe
                    </p>
                    <p class="mt-0.5 text-sm text-amber-800">
                        Hồ sơ đăng ký của bạn:
                        <strong>{{ onboarding.declared_total }} xe</strong> ({{
                            onboarding.declared_summary
                        }}). Bạn đã thêm
                        <strong
                            >{{ onboarding.actual_count }}/{{
                                onboarding.declared_total
                            }}</strong
                        >
                        xe — hãy khai đầy đủ biển số &amp; giấy tờ để bắt đầu
                        nhận chuyến.
                    </p>
                    <div class="mt-3 flex items-center gap-3">
                        <div
                            class="h-1.5 max-w-xs flex-1 overflow-hidden rounded-full bg-amber-100"
                        >
                            <div
                                class="h-full rounded-full bg-amber-500 transition-all duration-500"
                                :style="{
                                    width:
                                        Math.min(
                                            100,
                                            Math.round(
                                                (onboarding.actual_count /
                                                    (onboarding.declared_total ||
                                                        1)) *
                                                    100,
                                            ),
                                        ) + '%',
                                }"
                            />
                        </div>
                        <span class="text-xs font-medium text-amber-700"
                            >Còn {{ onboarding.remaining }} xe</span
                        >
                    </div>
                </div>
                <router-link
                    to="/operator/vehicles"
                    class="shrink-0 rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-amber-600"
                >
                    Thêm xe
                </router-link>
            </div>
        </div>

        <!-- Loading skeleton -->
        <div v-if="isLoading" class="space-y-4">
            <div class="grid grid-cols-4 gap-4">
                <div
                    v-for="i in 4"
                    :key="i"
                    class="h-28 animate-pulse rounded-xl border border-slate-200 bg-white"
                />
            </div>
            <div
                class="h-56 animate-pulse rounded-xl border border-slate-200 bg-white"
            />
            <div
                class="h-64 animate-pulse rounded-xl border border-slate-200 bg-white"
            />
        </div>

        <!-- Error state -->
        <div
            v-else-if="errorMsg"
            class="flex items-center gap-4 rounded-xl border border-red-200 bg-red-50 p-5"
        >
            <svg
                class="h-8 w-8 flex-shrink-0 text-red-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </svg>
            <div>
                <p class="font-medium text-red-700">Lỗi tải dữ liệu</p>
                <p class="mt-0.5 text-sm text-red-600">{{ errorMsg }}</p>
            </div>
            <button
                class="ml-auto rounded-lg bg-red-600 px-4 py-2 text-sm text-white transition-colors hover:bg-red-700"
                @click="load"
            >
                Thử lại
            </button>
        </div>

        <template v-else>
            <!-- KPI Cards -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <!-- Doanh thu hôm nay -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p
                                class="text-xs font-medium tracking-wider text-slate-500 uppercase"
                            >
                                Doanh thu hôm nay
                            </p>
                            <p class="mt-2 text-2xl font-bold text-slate-800">
                                {{ kpi ? fmt(kpi.gross_revenue ?? 0) : '—' }}
                            </p>
                        </div>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100"
                        >
                            <svg
                                class="h-5 w-5 text-amber-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <p
                        class="mt-3 flex items-center gap-1 text-xs font-medium text-green-600"
                    >
                        <svg
                            class="h-3.5 w-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 10l7-7m0 0l7 7m-7-7v18"
                            />
                        </svg>
                        +12% so với hôm qua
                    </p>
                </div>

                <!-- Chuyến hôm nay -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p
                                class="text-xs font-medium tracking-wider text-slate-500 uppercase"
                            >
                                Chuyến hôm nay
                            </p>
                            <p class="mt-2 text-2xl font-bold text-slate-800">
                                {{ kpi?.total_trips ?? trips.length }}
                            </p>
                        </div>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100"
                        >
                            <svg
                                class="h-5 w-5 text-blue-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">
                        {{
                            trips.filter((t) => t.status === 'in_progress')
                                .length
                        }}
                        chuyến đang chạy
                    </p>
                </div>

                <!-- Số khách -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p
                                class="text-xs font-medium tracking-wider text-slate-500 uppercase"
                            >
                                Số khách hôm nay
                            </p>
                            <p class="mt-2 text-2xl font-bold text-slate-800">
                                {{ kpi?.total_bookings ?? '—' }}
                            </p>
                        </div>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-100"
                        >
                            <svg
                                class="h-5 w-5 text-green-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">
                        Hành khách đã xác nhận
                    </p>
                </div>

                <!-- Tỷ lệ lấp đầy -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p
                                class="text-xs font-medium tracking-wider text-slate-500 uppercase"
                            >
                                Tỷ lệ lấp đầy
                            </p>
                            <p class="mt-2 text-2xl font-bold text-slate-800">
                                {{ kpi?.avg_occupancy ?? '—' }}%
                            </p>
                        </div>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100"
                        >
                            <svg
                                class="h-5 w-5 text-amber-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div
                            class="h-1.5 overflow-hidden rounded-full bg-slate-100"
                        >
                            <div
                                class="h-full rounded-full bg-amber-500 transition-all duration-500"
                                :style="{
                                    width: (kpi?.avg_occupancy ?? 0) + '%',
                                }"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trips Table -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-6 py-4"
                >
                    <h2 class="font-semibold text-slate-800">
                        Chuyến đi hôm nay
                    </h2>
                    <router-link
                        to="/operator/trips"
                        class="text-sm font-medium text-amber-600 hover:text-amber-700"
                    >
                        Xem tất cả →
                    </router-link>
                </div>

                <!-- Empty state -->
                <div
                    v-if="trips.length === 0"
                    class="flex flex-col items-center py-16 text-slate-400"
                >
                    <svg
                        class="mb-3 h-12 w-12 text-slate-300"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                    <p class="font-medium">Chưa có chuyến nào hôm nay</p>
                    <p class="mt-1 text-sm">Tạo lịch chạy để bắt đầu</p>
                    <router-link
                        to="/operator/trips"
                        class="mt-3 rounded-lg bg-amber-500 px-4 py-2 text-sm text-white transition-colors hover:bg-amber-600"
                    >
                        Tạo chuyến mới
                    </router-link>
                </div>

                <!-- Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Giờ xuất phát
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Tuyến
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Tài xế
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Xe
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Khách / Chỗ
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Trạng thái
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="trip in trips"
                                :key="trip.id"
                                class="transition-colors hover:bg-slate-50"
                            >
                                <td
                                    class="px-6 py-4 text-sm font-medium text-slate-800"
                                >
                                    {{
                                        new Date(
                                            trip.depart_at,
                                        ).toLocaleTimeString('vi-VN', {
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        })
                                    }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700">
                                    {{ trip.route?.origin_city }} →
                                    {{ trip.route?.dest_city }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700">
                                    {{ trip.driver?.full_name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ trip.vehicle?.plate ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-1.5 w-20 overflow-hidden rounded-full bg-slate-100"
                                        >
                                            <div
                                                class="h-full rounded-full bg-amber-500"
                                                :style="{
                                                    width:
                                                        ((trip.booking_count ??
                                                            0) /
                                                            (trip.total_seats ||
                                                                1)) *
                                                            100 +
                                                        '%',
                                                }"
                                            />
                                        </div>
                                        <span class="text-sm text-slate-600"
                                            >{{ trip.booking_count ?? 0 }}/{{
                                                trip.total_seats
                                            }}</span
                                        >
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="
                                            statusConfig[trip.status]?.class ??
                                            'bg-slate-100 text-slate-600'
                                        "
                                    >
                                        {{
                                            statusConfig[trip.status]?.label ??
                                            trip.status
                                        }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</template>
