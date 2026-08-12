<script setup lang="ts">
import {
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    CircleAlert,
    History,
    LoaderCircle,
    RefreshCw,
    WalletCards,
} from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import { operatorApi } from '@/api/operator.api';

type Period = 'today' | 'week' | 'month' | 'custom';

interface SummaryData {
    period: string;
    from: string;
    to: string;
    total_trips: number;
    total_bookings: number;
    total_passengers?: number;
    gross_revenue: number;
    commission: number;
    commission_rate: number;
    net_revenue: number;
    avg_occupancy: number;
}

interface DailyRow {
    date: string;
    total_bookings: number;
    revenue: number;
}

interface TransactionRow {
    id: string;
    date: string;
    route: string;
    driver: string;
    passengers: number;
    seat_count: number;
    gross_revenue: number;
    commission: number;
    net_revenue: number;
}

interface PayoutRow {
    id: string;
    amount: number;
    status: string;
    requested_at: string;
    processed_at: string | null;
}

interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const presets: ReadonlyArray<{ key: Period; label: string }> = [
    { key: 'today', label: 'Hôm nay' },
    { key: 'week', label: 'Tuần này' },
    { key: 'month', label: 'Tháng này' },
    { key: 'custom', label: 'Tùy chọn' },
];

const period = ref<Period>('week');
const customFrom = ref('');
const customTo = ref('');
const isLoading = ref(true);
const errorMsg = ref('');
const summary = ref<SummaryData | null>(null);
const previousSummary = ref<SummaryData | null>(null);
const dailyData = ref<DailyRow[]>([]);
const previousDailyData = ref<DailyRow[]>([]);
const transactions = ref<TransactionRow[]>([]);
const pagination = ref<PaginationMeta>({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
});
const payout = ref<{
    available: number;
    total_net: number;
    requested: number;
    history: PayoutRow[];
}>({ available: 0, total_net: 0, requested: 0, history: [] });
const payoutLoading = ref(false);
const payoutMsg = ref('');
const payoutHistoryOpen = ref(false);
let requestSequence = 0;

const payoutStatusLabel: Record<string, { label: string; cls: string }> = {
    pending: { label: 'Chờ duyệt', cls: 'bg-amber-50 text-amber-700' },
    approved: { label: 'Đã duyệt', cls: 'bg-blue-50 text-blue-700' },
    paid: { label: 'Đã chi', cls: 'bg-emerald-50 text-emerald-700' },
    rejected: { label: 'Từ chối', cls: 'bg-red-50 text-red-600' },
};

const formatCurrency = (value: number) =>
    `${new Intl.NumberFormat('vi-VN').format(value)}đ`;

const formatDate = (value: string) => {
    const [year, month, day] = value.split('-').map(Number);
    return new Intl.DateTimeFormat('vi-VN').format(
        new Date(year, month - 1, day),
    );
};

const formatChartDate = (value: string) => {
    const [year, month, day] = value.split('-').map(Number);
    const date = new Date(year, month - 1, day);
    return dailyData.value.length <= 7
        ? new Intl.DateTimeFormat('vi-VN', { weekday: 'short' }).format(date)
        : new Intl.DateTimeFormat('vi-VN', {
              day: '2-digit',
              month: '2-digit',
          }).format(date);
};

const toIsoDate = (date: Date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const addDays = (date: Date, amount: number) => {
    const next = new Date(date);
    next.setDate(next.getDate() + amount);
    return next;
};

const parseIsoDate = (value: string) => {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
};

const currentRange = computed(() => {
    if (period.value === 'custom') {
        return customFrom.value && customTo.value
            ? { from: customFrom.value, to: customTo.value }
            : null;
    }

    const now = new Date();
    if (period.value === 'today') {
        return { from: toIsoDate(now), to: toIsoDate(now) };
    }
    if (period.value === 'week') {
        const mondayOffset = (now.getDay() + 6) % 7;
        const monday = addDays(now, -mondayOffset);
        return { from: toIsoDate(monday), to: toIsoDate(addDays(monday, 6)) };
    }

    return {
        from: toIsoDate(new Date(now.getFullYear(), now.getMonth(), 1)),
        to: toIsoDate(new Date(now.getFullYear(), now.getMonth() + 1, 0)),
    };
});

const previousRange = computed(() => {
    if (!currentRange.value) return null;
    const from = parseIsoDate(currentRange.value.from);
    const to = parseIsoDate(currentRange.value.to);
    const days = Math.round((to.getTime() - from.getTime()) / 86_400_000) + 1;
    const previousTo = addDays(from, -1);
    return {
        from: toIsoDate(addDays(previousTo, -(days - 1))),
        to: toIsoDate(previousTo),
    };
});

const currentParams = computed<Record<string, unknown>>(() => {
    const params: Record<string, unknown> = { period: period.value };
    if (period.value === 'custom' && currentRange.value) {
        params.from_date = currentRange.value.from;
        params.to_date = currentRange.value.to;
    }
    return params;
});

const comparisonLabel = computed(() =>
    period.value === 'today'
        ? 'so với hôm qua'
        : period.value === 'month'
          ? 'so với tháng trước'
          : 'so với kỳ trước',
);

const percentChange = (current: number, previous?: number) => {
    if (!previous) return null;
    return Math.round(((current - previous) / previous) * 100);
};

const trend = (current: number, previous?: number) => {
    const value = percentChange(current, previous);
    if (value === null) return { value: '—', positive: true };
    return {
        value: `${value >= 0 ? '+' : ''}${value}%`,
        positive: value >= 0,
    };
};

const passengers = computed(
    () => summary.value?.total_passengers ?? summary.value?.total_bookings ?? 0,
);

const chartMax = computed(() =>
    Math.max(
        1,
        ...dailyData.value.map((row) => row.revenue),
        ...previousDailyData.value.map((row) => row.revenue),
    ),
);

const chartLength = computed(() =>
    Math.max(dailyData.value.length, previousDailyData.value.length),
);

const chartPoints = (rows: DailyRow[]) => {
    if (!rows.length) return '';
    return rows
        .map((row, index) => {
            const x =
                chartLength.value <= 1
                    ? 500
                    : (index / (chartLength.value - 1)) * 1000;
            const y = 220 - (row.revenue / chartMax.value) * 170;
            return `${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(' ');
};

const areaPath = computed(() => {
    const points = chartPoints(dailyData.value);
    if (!points) return '';
    const pairs = points.split(' ');
    const firstX = pairs[0].split(',')[0];
    const lastX = pairs[pairs.length - 1].split(',')[0];
    return `M ${points.replaceAll(' ', ' L ')} L ${lastX},220 L ${firstX},220 Z`;
});

const chartLabels = computed(() => {
    const length = dailyData.value.length;
    if (!length) return [];
    const step = length > 10 ? Math.ceil(length / 7) : 1;
    return dailyData.value
        .map((row, index) => ({
            text: formatChartDate(row.date),
            left: length <= 1 ? 50 : (index / (length - 1)) * 100,
            index,
        }))
        .filter(({ index }) => index % step === 0 || index === length - 1);
});

const pageTotals = computed(() =>
    transactions.value.reduce(
        (total, row) => ({
            passengers: total.passengers + row.passengers,
            gross: total.gross + row.gross_revenue,
            commission: total.commission + row.commission,
            net: total.net + row.net_revenue,
        }),
        { passengers: 0, gross: 0, commission: 0, net: 0 },
    ),
);

const load = async (page = 1) => {
    if (period.value === 'custom' && !currentRange.value) return;

    const sequence = ++requestSequence;
    isLoading.value = true;
    errorMsg.value = '';
    payoutMsg.value = '';

    const previousParams = previousRange.value
        ? {
              period: 'custom',
              from_date: previousRange.value.from,
              to_date: previousRange.value.to,
          }
        : null;

    const [
        summaryRes,
        dailyRes,
        transactionsRes,
        payoutRes,
        prevSummaryRes,
        prevDailyRes,
    ] = await Promise.all([
        operatorApi.getRevenueSummary(currentParams.value),
        operatorApi.getRevenueDaily(currentParams.value),
        operatorApi.getRevenueTransactions({
            ...currentParams.value,
            page,
            per_page: 10,
        }),
        operatorApi.getPayouts(),
        previousParams
            ? operatorApi.getRevenueSummary(previousParams)
            : Promise.resolve({ data: null, error: null }),
        previousParams
            ? operatorApi.getRevenueDaily(previousParams)
            : Promise.resolve({ data: [], error: null }),
    ]);

    if (sequence !== requestSequence) return;
    isLoading.value = false;

    const failed = [summaryRes, dailyRes, transactionsRes, payoutRes].find(
        (result) => result.error,
    );
    if (failed?.error || !summaryRes.data) {
        errorMsg.value = failed?.error ?? 'Không thể tải dữ liệu doanh thu';
        return;
    }

    summary.value = summaryRes.data as SummaryData;
    dailyData.value = (dailyRes.data ?? []) as DailyRow[];
    transactions.value = (transactionsRes.data ?? []) as TransactionRow[];
    if (transactionsRes.meta) {
        pagination.value = transactionsRes.meta as PaginationMeta;
    }
    if (payoutRes.data) payout.value = payoutRes.data as typeof payout.value;
    previousSummary.value = (prevSummaryRes.data as SummaryData | null) ?? null;
    previousDailyData.value = (prevDailyRes.data ?? []) as DailyRow[];
};

const applyCustomRange = () => {
    if (!currentRange.value || customFrom.value > customTo.value) return;
    load(1);
};

const requestPayout = async () => {
    if (payout.value.available <= 0 || payoutLoading.value) return;
    if (
        !window.confirm(
            `Yêu cầu quyết toán ${formatCurrency(payout.value.available)}?`,
        )
    ) {
        return;
    }

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
    if (data) payout.value = data as typeof payout.value;
};

watch(period, (value) => {
    if (value !== 'custom') load(1);
});

onMounted(() => load(1));
</script>

<template>
    <div class="min-h-full bg-[#f7f9fb] p-4 sm:p-6 lg:p-7">
        <section
            aria-label="Bộ lọc thời gian"
            class="mb-6 flex flex-col gap-4 rounded-2xl border border-[#ead4bd] bg-white p-3 shadow-sm lg:flex-row lg:items-center lg:justify-between"
        >
            <div class="grid grid-cols-2 rounded-xl bg-slate-100 p-1 sm:flex">
                <button
                    v-for="item in presets"
                    :key="item.key"
                    type="button"
                    class="min-h-11 rounded-lg px-4 text-sm font-medium transition sm:px-6"
                    :class="
                        period === item.key
                            ? 'bg-amber-500 text-white shadow-sm'
                            : 'text-slate-600 hover:bg-white hover:text-slate-900'
                    "
                    @click="period = item.key"
                >
                    {{ item.label }}
                </button>
            </div>

            <div
                v-if="period === 'custom'"
                class="flex flex-col gap-2 sm:flex-row sm:items-center"
            >
                <label class="relative">
                    <span class="sr-only">Từ ngày</span>
                    <input
                        v-model="customFrom"
                        type="date"
                        class="h-11 w-full rounded-xl border border-[#dfc2a5] bg-white px-3 pr-10 text-sm text-slate-800 transition outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-100 sm:w-44"
                    />
                    <CalendarDays
                        class="pointer-events-none absolute top-3 right-3 h-5 w-5 text-slate-500"
                    />
                </label>
                <span class="hidden text-sm text-slate-500 sm:inline">đến</span>
                <label class="relative">
                    <span class="sr-only">Đến ngày</span>
                    <input
                        v-model="customTo"
                        type="date"
                        class="h-11 w-full rounded-xl border border-[#dfc2a5] bg-white px-3 pr-10 text-sm text-slate-800 transition outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-100 sm:w-44"
                    />
                    <CalendarDays
                        class="pointer-events-none absolute top-3 right-3 h-5 w-5 text-slate-500"
                    />
                </label>
                <button
                    type="button"
                    :disabled="!currentRange || customFrom > customTo"
                    class="h-11 rounded-xl bg-amber-500 px-5 text-sm font-semibold text-white transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="applyCustomRange"
                >
                    Áp dụng
                </button>
            </div>

            <p v-else-if="currentRange" class="text-sm text-slate-500">
                {{ formatDate(currentRange.from) }}
                <span class="mx-2">đến</span>
                {{ formatDate(currentRange.to) }}
            </p>
        </section>

        <div v-if="isLoading" aria-label="Đang tải dữ liệu" class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="index in 4"
                    :key="index"
                    class="h-44 animate-pulse rounded-2xl border border-slate-200 bg-white"
                />
            </div>
            <div
                class="h-80 animate-pulse rounded-2xl border border-slate-200 bg-white"
            />
            <div
                class="h-96 animate-pulse rounded-2xl border border-slate-200 bg-white"
            />
        </div>

        <div
            v-else-if="errorMsg"
            role="alert"
            class="flex flex-col items-center justify-center rounded-2xl border border-red-200 bg-white px-6 py-16 text-center"
        >
            <CircleAlert class="h-10 w-10 text-red-500" />
            <h2 class="mt-4 text-lg font-semibold text-slate-900">
                Không thể tải báo cáo
            </h2>
            <p class="mt-1 text-sm text-slate-500">{{ errorMsg }}</p>
            <button
                type="button"
                class="mt-5 inline-flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-600"
                @click="load(pagination.current_page)"
            >
                <RefreshCw class="h-4 w-4" />
                Thử lại
            </button>
        </div>

        <template v-else-if="summary">
            <section
                aria-label="Tổng quan doanh thu"
                class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
            >
                <article
                    class="rounded-2xl border border-[#ead4bd] bg-white p-6 shadow-sm"
                >
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-medium text-[#39291e]">Tổng doanh thu</p>
                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="
                                trend(
                                    summary.gross_revenue,
                                    previousSummary?.gross_revenue,
                                ).positive
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-red-100 text-red-600'
                            "
                        >
                            {{
                                trend(
                                    summary.gross_revenue,
                                    previousSummary?.gross_revenue,
                                ).value
                            }}
                        </span>
                    </div>
                    <p
                        class="mt-5 text-3xl font-bold tracking-tight text-[#9a5700]"
                    >
                        {{ formatCurrency(summary.gross_revenue) }}
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ comparisonLabel }}
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-[#ead4bd] bg-white p-6 shadow-sm"
                >
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-medium text-[#39291e]">Số chuyến</p>
                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="
                                trend(
                                    summary.total_trips,
                                    previousSummary?.total_trips,
                                ).positive
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-red-100 text-red-600'
                            "
                        >
                            {{
                                trend(
                                    summary.total_trips,
                                    previousSummary?.total_trips,
                                ).value
                            }}
                        </span>
                    </div>
                    <p
                        class="mt-5 text-3xl font-bold tracking-tight text-slate-950"
                    >
                        {{ summary.total_trips }}
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        Đã hoàn thành trong kỳ
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-[#ead4bd] bg-white p-6 shadow-sm"
                >
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-medium text-[#39291e]">Số hành khách</p>
                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="
                                trend(
                                    passengers,
                                    previousSummary?.total_passengers ??
                                        previousSummary?.total_bookings,
                                ).positive
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-red-100 text-red-600'
                            "
                        >
                            {{
                                trend(
                                    passengers,
                                    previousSummary?.total_passengers ??
                                        previousSummary?.total_bookings,
                                ).value
                            }}
                        </span>
                    </div>
                    <p
                        class="mt-5 text-3xl font-bold tracking-tight text-slate-950"
                    >
                        {{ passengers }}
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        Lượt khách đã vận chuyển
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-[#ead4bd] bg-white p-6 shadow-sm"
                >
                    <div class="flex items-center justify-between gap-5">
                        <div>
                            <p class="font-medium text-[#39291e]">
                                Tỷ lệ lấp đầy
                            </p>
                            <p
                                class="mt-5 text-3xl font-bold tracking-tight text-slate-950"
                            >
                                {{ summary.avg_occupancy }}%
                            </p>
                            <p class="mt-2 text-sm text-slate-500">
                                Trung bình/chuyến
                            </p>
                        </div>
                        <div
                            class="grid h-20 w-20 shrink-0 place-items-center rounded-full"
                            :style="{
                                background: `conic-gradient(#f59e0b ${Math.min(100, summary.avg_occupancy)}%, #fde9b4 0)`,
                            }"
                        >
                            <div
                                class="grid h-14 w-14 place-items-center rounded-full bg-white text-sm font-semibold text-[#9a5700]"
                            >
                                {{ summary.avg_occupancy }}%
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section
                class="mt-6 rounded-2xl border border-[#ead4bd] bg-white p-5 shadow-sm sm:p-7"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <h2 class="text-xl font-bold text-slate-950">
                        Doanh thu theo ngày
                    </h2>
                    <div class="flex items-center gap-5 text-sm text-slate-600">
                        <span class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-amber-500" />
                            Kỳ này
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-[#cbd8f3]" />
                            Kỳ trước
                        </span>
                    </div>
                </div>

                <div
                    v-if="dailyData.length === 0"
                    class="grid h-64 place-items-center text-sm text-slate-400"
                >
                    Chưa có dữ liệu doanh thu trong khoảng thời gian này
                </div>
                <div v-else class="mt-6 overflow-x-auto pb-2">
                    <div class="min-w-[680px]">
                        <svg
                            viewBox="0 0 1000 240"
                            role="img"
                            aria-label="Biểu đồ doanh thu theo ngày"
                            class="h-64 w-full overflow-visible"
                            preserveAspectRatio="none"
                        >
                            <defs>
                                <linearGradient
                                    id="revenueArea"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="0"
                                        stop-color="#f59e0b"
                                        stop-opacity="0.18"
                                    />
                                    <stop
                                        offset="1"
                                        stop-color="#f59e0b"
                                        stop-opacity="0.01"
                                    />
                                </linearGradient>
                            </defs>
                            <line
                                v-for="y in [50, 105, 160, 220]"
                                :key="y"
                                x1="0"
                                x2="1000"
                                :y1="y"
                                :y2="y"
                                stroke="#dbe4ee"
                                stroke-dasharray="5 5"
                                vector-effect="non-scaling-stroke"
                            />
                            <path
                                v-if="areaPath"
                                :d="areaPath"
                                fill="url(#revenueArea)"
                            />
                            <polyline
                                v-if="previousDailyData.length"
                                :points="chartPoints(previousDailyData)"
                                fill="none"
                                stroke="#cbd8f3"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                vector-effect="non-scaling-stroke"
                            />
                            <polyline
                                :points="chartPoints(dailyData)"
                                fill="none"
                                stroke="#f59e0b"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                vector-effect="non-scaling-stroke"
                            />
                            <circle
                                v-for="(row, index) in dailyData"
                                :key="row.date"
                                :cx="
                                    chartLength <= 1
                                        ? 500
                                        : (index / (chartLength - 1)) * 1000
                                "
                                :cy="220 - (row.revenue / chartMax) * 170"
                                r="5"
                                fill="#f59e0b"
                                stroke="white"
                                stroke-width="3"
                                vector-effect="non-scaling-stroke"
                            >
                                <title>
                                    {{ formatDate(row.date) }}:
                                    {{ formatCurrency(row.revenue) }}
                                </title>
                            </circle>
                        </svg>
                        <div class="relative h-6 text-xs text-[#5f4a3c]">
                            <span
                                v-for="label in chartLabels"
                                :key="`${label.index}-${label.text}`"
                                class="absolute -translate-x-1/2 whitespace-nowrap"
                                :style="{ left: `${label.left}%` }"
                            >
                                {{ label.text }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="mt-6 overflow-hidden rounded-2xl border border-[#ead4bd] bg-white shadow-sm"
            >
                <div
                    class="flex items-center justify-between border-b border-[#ead4bd] px-5 py-5 sm:px-7"
                >
                    <div>
                        <h2 class="text-xl font-bold text-slate-950">
                            Chi tiết giao dịch
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ pagination.total }} chuyến có doanh thu trong kỳ
                        </p>
                    </div>
                </div>

                <div
                    v-if="transactions.length === 0"
                    class="py-16 text-center text-sm text-slate-400"
                >
                    Chưa có giao dịch hoàn thành trong khoảng thời gian này
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[940px] text-sm">
                        <thead class="bg-[#f4f6f8] text-[#483426]">
                            <tr>
                                <th class="px-6 py-4 text-left font-semibold">
                                    Ngày
                                </th>
                                <th class="px-6 py-4 text-left font-semibold">
                                    Tuyến
                                </th>
                                <th class="px-6 py-4 text-left font-semibold">
                                    Tài xế
                                </th>
                                <th class="px-6 py-4 text-center font-semibold">
                                    Số khách
                                </th>
                                <th class="px-6 py-4 text-right font-semibold">
                                    Doanh thu
                                </th>
                                <th class="px-6 py-4 text-right font-semibold">
                                    Hoa hồng ({{ summary.commission_rate }}%)
                                </th>
                                <th class="px-6 py-4 text-right font-semibold">
                                    Thực nhận
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#ead4bd]">
                            <tr
                                v-for="row in transactions"
                                :key="row.id"
                                class="transition hover:bg-amber-50/40"
                            >
                                <td
                                    class="px-6 py-5 whitespace-nowrap text-slate-700"
                                >
                                    {{ formatDate(row.date) }}
                                </td>
                                <td
                                    class="px-6 py-5 font-medium text-slate-800"
                                >
                                    {{ row.route }}
                                </td>
                                <td class="px-6 py-5 text-slate-700">
                                    {{ row.driver }}
                                </td>
                                <td
                                    class="px-6 py-5 text-center text-slate-700"
                                >
                                    {{ row.passengers }}/{{
                                        row.seat_count || '—'
                                    }}
                                </td>
                                <td
                                    class="px-6 py-5 text-right font-medium whitespace-nowrap text-slate-900"
                                >
                                    {{ formatCurrency(row.gross_revenue) }}
                                </td>
                                <td
                                    class="px-6 py-5 text-right font-medium whitespace-nowrap text-red-600"
                                >
                                    −{{ formatCurrency(row.commission) }}
                                </td>
                                <td
                                    class="px-6 py-5 text-right font-bold whitespace-nowrap text-slate-950"
                                >
                                    {{ formatCurrency(row.net_revenue) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot
                            class="border-t border-[#ead4bd] bg-[#f4f6f8] font-bold"
                        >
                            <tr>
                                <td
                                    colspan="3"
                                    class="px-6 py-4 text-slate-950"
                                >
                                    {{
                                        pagination.last_page > 1
                                            ? 'Tổng trang này'
                                            : 'Tổng cộng'
                                    }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    {{ pageTotals.passengers }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    {{ formatCurrency(pageTotals.gross) }}
                                </td>
                                <td class="px-6 py-4 text-right text-red-600">
                                    −{{ formatCurrency(pageTotals.commission) }}
                                </td>
                                <td class="px-6 py-4 text-right text-[#9a5700]">
                                    {{ formatCurrency(pageTotals.net) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div
                    v-if="pagination.last_page > 1"
                    class="flex items-center justify-between border-t border-[#ead4bd] px-5 py-4"
                >
                    <p class="text-sm text-slate-500">
                        Trang {{ pagination.current_page }}/{{
                            pagination.last_page
                        }}
                    </p>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            aria-label="Trang trước"
                            :disabled="pagination.current_page <= 1"
                            class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40"
                            @click="load(pagination.current_page - 1)"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            aria-label="Trang sau"
                            :disabled="
                                pagination.current_page >= pagination.last_page
                            "
                            class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40"
                            @click="load(pagination.current_page + 1)"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </section>

            <section
                class="mt-6 overflow-hidden rounded-2xl border border-amber-300 bg-[#fffaf0] shadow-sm"
            >
                <div
                    class="flex flex-col gap-5 p-5 lg:flex-row lg:items-center lg:justify-between lg:p-7"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-amber-100 text-[#9a5700]"
                        >
                            <WalletCards class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-sm text-[#594435]">
                                Số dư chưa quyết toán
                            </p>
                            <p class="mt-1 text-2xl font-bold text-slate-950">
                                {{ formatCurrency(payout.available) }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Đã/đang yêu cầu:
                                {{ formatCurrency(payout.requested) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button
                            type="button"
                            :disabled="payout.available <= 0 || payoutLoading"
                            class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-amber-500 px-8 text-sm font-bold text-white transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="requestPayout"
                        >
                            <LoaderCircle
                                v-if="payoutLoading"
                                class="h-4 w-4 animate-spin"
                            />
                            {{
                                payoutLoading
                                    ? 'Đang gửi...'
                                    : 'Yêu cầu quyết toán'
                            }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-[#8b735f] bg-white px-8 text-sm font-bold text-slate-900 transition hover:bg-amber-50"
                            @click="payoutHistoryOpen = !payoutHistoryOpen"
                        >
                            <History class="h-4 w-4" />
                            Lịch sử quyết toán
                        </button>
                    </div>
                </div>

                <p
                    v-if="payoutMsg"
                    role="status"
                    class="border-t border-amber-200 bg-white/70 px-7 py-3 text-sm text-emerald-700"
                >
                    {{ payoutMsg }}
                </p>

                <div
                    v-if="payoutHistoryOpen"
                    class="border-t border-amber-200 bg-white"
                >
                    <div v-if="payout.history.length" class="overflow-x-auto">
                        <table class="w-full min-w-[620px] text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-6 py-3 text-left font-medium">
                                        Ngày yêu cầu
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right font-medium"
                                    >
                                        Số tiền
                                    </th>
                                    <th
                                        class="px-6 py-3 text-center font-medium"
                                    >
                                        Trạng thái
                                    </th>
                                    <th class="px-6 py-3 text-left font-medium">
                                        Ngày xử lý
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="item in payout.history"
                                    :key="item.id"
                                >
                                    <td class="px-6 py-4 text-slate-700">
                                        {{ item.requested_at }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-right font-semibold"
                                    >
                                        {{ formatCurrency(item.amount) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="
                                                payoutStatusLabel[item.status]
                                                    ?.cls ??
                                                'bg-slate-100 text-slate-600'
                                            "
                                        >
                                            {{
                                                payoutStatusLabel[item.status]
                                                    ?.label ?? item.status
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500">
                                        {{ item.processed_at ?? '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p
                        v-else
                        class="px-6 py-10 text-center text-sm text-slate-400"
                    >
                        Chưa có yêu cầu quyết toán nào
                    </p>
                </div>
            </section>
        </template>
    </div>
</template>
