<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { operatorApi } from '@/api/operator.api';

interface SummaryData {
    period: string;
    from: string;
    to: string;
    total_trips: number;
    total_bookings: number;
    gross_revenue: number;
    commission: number;
    commission_rate: number;
    net_revenue: number;
    avg_occupancy: number;
}

interface BreakdownRow {
    name: string;
    total_bookings: number;
    revenue: number;
}
interface PayoutRow {
    id: string;
    amount: number;
    status: string;
    requested_at: string;
    processed_at: string | null;
}

interface DailyRow {
    date: string;
    total_bookings: number;
    revenue: number;
}

const isLoading = ref(true);
const errorMsg = ref('');
const summary = ref<SummaryData | null>(null);
const dailyData = ref<DailyRow[]>([]);
const byRoute = ref<BreakdownRow[]>([]);
const byDriver = ref<BreakdownRow[]>([]);
const payout = ref<{
    available: number;
    total_net: number;
    requested: number;
    history: PayoutRow[];
}>({ available: 0, total_net: 0, requested: 0, history: [] });
const payoutLoading = ref(false);
const payoutMsg = ref('');

const payoutStatusLabel: Record<string, { label: string; cls: string }> = {
    pending: { label: 'Chờ duyệt', cls: 'bg-amber-50 text-amber-700' },
    approved: { label: 'Đã duyệt', cls: 'bg-blue-50 text-blue-700' },
    paid: { label: 'Đã chi', cls: 'bg-green-50 text-green-700' },
    rejected: { label: 'Từ chối', cls: 'bg-red-50 text-red-600' },
};
// % chiều rộng thanh cho breakdown (theo doanh thu lớn nhất)
const pct = (v: number, rows: BreakdownRow[]) => {
    const max = Math.max(1, ...rows.map((r) => r.revenue));
    return Math.max(2, Math.round((v / max) * 100));
};

const period = ref<'today' | 'week' | 'month' | 'custom'>('week');
const customFrom = ref('');
const customTo = ref('');

const presets = [
    { key: 'today', label: 'Hôm nay' },
    { key: 'week', label: 'Tuần này' },
    { key: 'month', label: 'Tháng này' },
    { key: 'custom', label: 'Tùy chọn' },
] as const;

const fmt = (n: number) => new Intl.NumberFormat('vi-VN').format(n) + 'đ';
const fmtK = (n: number) =>
    n >= 1_000_000
        ? (n / 1_000_000).toFixed(1) + 'M'
        : new Intl.NumberFormat('vi-VN').format(n);

// Chart: max revenue for scaling
const maxRev = ref(1);
const barH = (v: number) => Math.max(4, Math.round((v / maxRev.value) * 120));

const load = async () => {
    isLoading.value = true;
    errorMsg.value = '';

    const params: any = { period: period.value };
    if (period.value === 'custom') {
        params.from_date = customFrom.value;
        params.to_date = customTo.value;
    }

    const [summaryRes, dailyRes, routeRes, driverRes, payoutRes] =
        await Promise.all([
            operatorApi.getRevenueSummary(params),
            operatorApi.getRevenueDaily(params),
            operatorApi.getRevenueByRoute(params),
            operatorApi.getRevenueByDriver(params),
            operatorApi.getPayouts(),
        ]);

    isLoading.value = false;

    if (summaryRes.error) {
        errorMsg.value = 'Không thể tải dữ liệu doanh thu';
        return;
    }

    summary.value = summaryRes.data;
    dailyData.value = dailyRes.data ?? [];
    byRoute.value = routeRes.data ?? [];
    byDriver.value = driverRes.data ?? [];
    if (payoutRes.data) payout.value = payoutRes.data;
    maxRev.value = Math.max(
        1,
        ...dailyData.value.map((d: DailyRow) => d.revenue),
    );
};

const requestPayout = async () => {
    if (payout.value.available <= 0) return;
    if (!confirm(`Yêu cầu quyết toán ${fmt(payout.value.available)}?`)) return;
    payoutLoading.value = true;
    payoutMsg.value = '';
    const { error, message } = await operatorApi.requestPayout();
    payoutLoading.value = false;
    if (error) {
        payoutMsg.value = error;
        return;
    }
    payoutMsg.value = message ?? 'Đã gửi yêu cầu quyết toán';
    const { data } = await operatorApi.getPayouts();
    if (data) payout.value = data;
};

watch(period, () => {
    if (period.value !== 'custom') load();
});

onMounted(() => load());
</script>

<template>
    <div class="space-y-5 p-6">
        <!-- Header + period filter -->
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-800">
                    Báo cáo doanh thu
                </h1>
                <p class="mt-0.5 text-sm text-slate-500">
                    Theo dõi doanh thu và hoa hồng
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Preset tabs -->
                <div
                    class="flex gap-1 rounded-lg border border-slate-200 bg-white p-1"
                >
                    <button
                        v-for="p in presets"
                        :key="p.key"
                        :class="
                            period === p.key
                                ? 'bg-amber-500 text-white'
                                : 'text-slate-600 hover:bg-slate-50'
                        "
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                        @click="period = p.key"
                    >
                        {{ p.label }}
                    </button>
                </div>

                <!-- Custom date range -->
                <template v-if="period === 'custom'">
                    <input
                        v-model="customFrom"
                        type="date"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none"
                    />
                    <span class="text-slate-400">→</span>
                    <input
                        v-model="customTo"
                        type="date"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none"
                    />
                    <button
                        class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-amber-600"
                        @click="load"
                    >
                        Áp dụng
                    </button>
                </template>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="space-y-4">
            <div class="grid grid-cols-4 gap-4">
                <div
                    v-for="i in 4"
                    :key="i"
                    class="h-28 animate-pulse rounded-xl border border-slate-200 bg-white"
                />
            </div>
            <div
                class="h-48 animate-pulse rounded-xl border border-slate-200 bg-white"
            />
            <div
                class="h-64 animate-pulse rounded-xl border border-slate-200 bg-white"
            />
        </div>

        <!-- Error -->
        <div
            v-else-if="errorMsg"
            class="flex items-center gap-4 rounded-xl border border-red-200 bg-red-50 p-5 text-red-700"
        >
            <svg
                class="h-6 w-6 flex-shrink-0"
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
            {{ errorMsg }}
            <button class="ml-auto text-sm underline" @click="load">
                Thử lại
            </button>
        </div>

        <template v-else-if="summary">
            <!-- KPI Cards -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs font-medium tracking-wider text-slate-500 uppercase"
                    >
                        Tổng doanh thu
                    </p>
                    <p class="mt-2 text-2xl font-bold text-slate-800">
                        {{ fmtK(summary.gross_revenue) }}
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        {{ fmt(summary.gross_revenue) }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs font-medium tracking-wider text-slate-500 uppercase"
                    >
                        Số chuyến
                    </p>
                    <p class="mt-2 text-2xl font-bold text-slate-800">
                        {{ summary.total_trips }}
                    </p>
                    <p class="mt-1 text-xs text-green-600">chuyến hoàn thành</p>
                </div>
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs font-medium tracking-wider text-slate-500 uppercase"
                    >
                        Số hành khách
                    </p>
                    <p class="mt-2 text-2xl font-bold text-slate-800">
                        {{ summary.total_bookings }}
                    </p>
                    <p class="mt-1 text-xs text-slate-400">lượt đặt vé</p>
                </div>
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <p
                        class="text-xs font-medium tracking-wider text-slate-500 uppercase"
                    >
                        Tỷ lệ lấp đầy TB
                    </p>
                    <p class="mt-2 text-2xl font-bold text-slate-800">
                        {{ summary.avg_occupancy }}%
                    </p>
                    <div
                        class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100"
                    >
                        <div
                            class="h-full rounded-full bg-amber-500"
                            :style="{ width: summary.avg_occupancy + '%' }"
                        />
                    </div>
                </div>
            </div>

            <!-- Bar chart -->
            <div
                class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <h2 class="mb-4 font-semibold text-slate-800">
                    Doanh thu theo ngày
                </h2>

                <div
                    v-if="dailyData.length === 0"
                    class="py-8 text-center text-sm text-slate-400"
                >
                    Không có dữ liệu trong khoảng thời gian này
                </div>

                <div
                    v-else
                    class="flex h-36 items-end gap-1 overflow-x-auto pb-4"
                >
                    <div
                        v-for="day in dailyData"
                        :key="day.date"
                        class="flex flex-shrink-0 flex-col items-center gap-1"
                        style="min-width: 24px"
                    >
                        <div
                            class="w-5 cursor-pointer rounded-t bg-amber-400 transition-colors hover:bg-amber-500"
                            :style="{ height: barH(day.revenue) + 'px' }"
                            :title="`${day.date}: ${fmt(day.revenue)}`"
                        />
                        <span
                            class="origin-left rotate-45 text-xs text-slate-400"
                            style="
                                writing-mode: vertical-lr;
                                transform: rotate(180deg);
                            "
                        >
                            {{
                                new Date(day.date).toLocaleDateString('vi-VN', {
                                    day: '2-digit',
                                    month: '2-digit',
                                })
                            }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Detail table -->
            <div
                class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="font-semibold text-slate-800">
                        Chi tiết theo ngày
                    </h2>
                </div>

                <div
                    v-if="dailyData.length === 0"
                    class="py-12 text-center text-sm text-slate-400"
                >
                    Không có dữ liệu
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Ngày
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Số vé
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Doanh thu
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Hoa hồng ({{
                                        summary.commission_rate ?? 5
                                    }}%)
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Thực nhận
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="row in dailyData"
                                :key="row.date"
                                class="transition-colors hover:bg-slate-50"
                            >
                                <td class="px-6 py-4 text-slate-700">
                                    {{
                                        new Date(row.date).toLocaleDateString(
                                            'vi-VN',
                                            {
                                                weekday: 'short',
                                                day: '2-digit',
                                                month: '2-digit',
                                            },
                                        )
                                    }}
                                </td>
                                <td class="px-6 py-4 text-right text-slate-700">
                                    {{ row.total_bookings }}
                                </td>
                                <td
                                    class="px-6 py-4 text-right font-medium text-slate-800"
                                >
                                    {{ fmt(row.revenue) }}
                                </td>
                                <td
                                    class="px-6 py-4 text-right text-orange-600"
                                >
                                    {{
                                        fmt(
                                            Math.round(
                                                (row.revenue *
                                                    (summary.commission_rate ??
                                                        5)) /
                                                    100,
                                            ),
                                        )
                                    }}
                                </td>
                                <td
                                    class="px-6 py-4 text-right font-semibold text-green-700"
                                >
                                    {{
                                        fmt(
                                            Math.round(
                                                row.revenue *
                                                    (1 -
                                                        (summary.commission_rate ??
                                                            5) /
                                                            100),
                                            ),
                                        )
                                    }}
                                </td>
                            </tr>

                            <!-- Totals row -->
                            <tr
                                class="border-t-2 border-amber-200 bg-amber-50 font-semibold"
                            >
                                <td class="px-6 py-3 text-slate-800">
                                    Tổng cộng
                                </td>
                                <td class="px-6 py-3 text-right text-slate-800">
                                    {{ summary.total_bookings }}
                                </td>
                                <td class="px-6 py-3 text-right text-slate-800">
                                    {{ fmt(summary.gross_revenue) }}
                                </td>
                                <td
                                    class="px-6 py-3 text-right text-orange-700"
                                >
                                    {{ fmt(summary.commission) }}
                                </td>
                                <td class="px-6 py-3 text-right text-green-700">
                                    {{ fmt(summary.net_revenue) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Doanh thu theo tuyến + theo tài xế -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <h2 class="mb-4 font-semibold text-slate-800">
                        Doanh thu theo tuyến
                    </h2>
                    <p
                        v-if="byRoute.length === 0"
                        class="py-6 text-center text-sm text-slate-400"
                    >
                        Không có dữ liệu
                    </p>
                    <div v-else class="space-y-3">
                        <div v-for="r in byRoute" :key="r.name">
                            <div class="mb-1 flex justify-between text-sm">
                                <span class="text-slate-700">{{ r.name }}</span>
                                <span class="font-medium text-slate-800"
                                    >{{ fmt(r.revenue) }}
                                    <span class="text-xs text-slate-400"
                                        >· {{ r.total_bookings }} vé</span
                                    ></span
                                >
                            </div>
                            <div
                                class="h-2.5 overflow-hidden rounded-full bg-slate-100"
                            >
                                <div
                                    class="h-full rounded-full bg-amber-400 transition-all"
                                    :style="{
                                        width: pct(r.revenue, byRoute) + '%',
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <h2 class="mb-4 font-semibold text-slate-800">
                        Doanh thu theo tài xế
                    </h2>
                    <p
                        v-if="byDriver.length === 0"
                        class="py-6 text-center text-sm text-slate-400"
                    >
                        Không có dữ liệu
                    </p>
                    <div v-else class="space-y-3">
                        <div v-for="d in byDriver" :key="d.name">
                            <div class="mb-1 flex justify-between text-sm">
                                <span class="text-slate-700">{{ d.name }}</span>
                                <span class="font-medium text-slate-800"
                                    >{{ fmt(d.revenue) }}
                                    <span class="text-xs text-slate-400"
                                        >· {{ d.total_bookings }} vé</span
                                    ></span
                                >
                            </div>
                            <div
                                class="h-2.5 overflow-hidden rounded-full bg-slate-100"
                            >
                                <div
                                    class="h-full rounded-full bg-green-400 transition-all"
                                    :style="{
                                        width: pct(d.revenue, byDriver) + '%',
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quyết toán -->
            <div
                class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-amber-200 bg-amber-50 p-5"
                >
                    <div>
                        <p class="font-semibold text-amber-900">
                            Số dư khả dụng để quyết toán
                        </p>
                        <p class="mt-1 text-2xl font-bold text-amber-700">
                            {{ fmt(payout.available) }}
                        </p>
                        <p class="mt-0.5 text-xs text-amber-600">
                            Tổng thực nhận: {{ fmt(payout.total_net) }} ·
                            Đã/đang yêu cầu: {{ fmt(payout.requested) }} · Quyết
                            toán ngày 1 & 15 hàng tháng
                        </p>
                    </div>
                    <button
                        :disabled="payout.available <= 0 || payoutLoading"
                        @click="requestPayout"
                        class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            payoutLoading ? 'Đang gửi...' : 'Yêu cầu quyết toán'
                        }}
                    </button>
                </div>

                <p
                    v-if="payoutMsg"
                    class="border-b border-green-100 bg-green-50 px-5 py-2.5 text-sm text-green-700"
                >
                    {{ payoutMsg }}
                </p>

                <!-- Lịch sử quyết toán -->
                <div v-if="payout.history.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase"
                                >
                                    Ngày yêu cầu
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase"
                                >
                                    Số tiền
                                </th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase"
                                >
                                    Trạng thái
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase"
                                >
                                    Ngày xử lý
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="h in payout.history"
                                :key="h.id"
                                class="hover:bg-slate-50"
                            >
                                <td class="px-6 py-3 text-slate-700">
                                    {{ h.requested_at }}
                                </td>
                                <td
                                    class="px-6 py-3 text-right font-medium text-slate-800"
                                >
                                    {{ fmt(h.amount) }}
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            payoutStatusLabel[h.status]?.cls ??
                                                'bg-slate-100 text-slate-600',
                                        ]"
                                    >
                                        {{
                                            payoutStatusLabel[h.status]
                                                ?.label ?? h.status
                                        }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-slate-500">
                                    {{ h.processed_at ?? '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="px-6 py-8 text-center text-sm text-slate-400">
                    Chưa có yêu cầu quyết toán nào
                </p>
            </div>
        </template>
    </div>
</template>
