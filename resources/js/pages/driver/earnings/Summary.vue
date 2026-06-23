<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { driverApi } from '@/api/driver.api';
import { useDriverAuthStore } from '@/stores/driver.auth.store';

const auth = useDriverAuthStore();

type Period = 'today' | 'week' | 'month';

const period = ref<Period>('week');
const earnings = ref<any>(null);
const transactions = ref<any[]>([]);
const isLoading = ref(true);
const errorMsg = ref('');
const txPage = ref(1);

const periods: { key: Period; label: string }[] = [
    { key: 'today', label: 'Hôm nay' },
    { key: 'week', label: 'Tuần này' },
    { key: 'month', label: 'Tháng này' },
];

function fmt(v: number) {
    return new Intl.NumberFormat('vi-VN').format(v) + 'đ';
}
function fmtDate(iso: string) {
    return new Date(iso).toLocaleDateString('vi-VN', {
        day: 'numeric',
        month: 'numeric',
    });
}

const maxBar = computed(() =>
    Math.max(...(earnings.value?.daily_amounts ?? [0]), 1),
);

const dayLabels = computed(() => {
    const days = [];
    for (let i = 6; i >= 0; i--) {
        const d = new Date();
        d.setDate(d.getDate() - i);
        days.push(d.toLocaleDateString('vi-VN', { weekday: 'short' }));
    }
    return days;
});

async function load() {
    isLoading.value = true;
    errorMsg.value = '';
    const [earnRes, txRes] = await Promise.all([
        driverApi.getEarnings({ period: period.value }),
        driverApi.getTransactions({ page: txPage.value }),
    ]);
    isLoading.value = false;
    if (earnRes.error) {
        errorMsg.value = earnRes.error as string;
        return;
    }
    earnings.value = earnRes.data;
    transactions.value = (txRes.data as any)?.data ?? txRes.data ?? [];
}

watch(period, () => {
    txPage.value = 1;
    load();
});
onMounted(load);
</script>

<template>
    <div class="mx-auto max-w-5xl p-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-xl font-bold text-gray-900">Thu nhập</h1>
            <p class="mt-0.5 text-sm text-gray-500">
                {{ auth.user?.full_name }} · Tài xế
            </p>
        </div>

        <!-- Error -->
        <div
            v-if="errorMsg && !isLoading"
            class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-600"
        >
            {{ errorMsg }}
        </div>

        <!-- Tổng thu nhập tích lũy (chỉ xem — nhà xe quyết toán trực tiếp) -->
        <div
            class="mb-6 rounded-xl bg-gradient-to-r from-green-700 to-green-600 p-6 text-white shadow-lg"
        >
            <p class="mb-1 text-sm font-medium text-green-200">
                Tổng thu nhập tích lũy
            </p>
            <p
                v-if="isLoading"
                class="h-10 w-48 animate-pulse rounded-xl bg-white/20"
            />
            <p v-else class="text-4xl font-black">
                {{ fmt(earnings?.total_earned ?? 0) }}
            </p>
            <div
                class="mt-3 flex items-start gap-2 rounded-lg bg-white/10 px-3 py-2 text-xs text-green-100"
            >
                <svg
                    class="mt-0.5 h-4 w-4 shrink-0"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <span
                    >Doanh thu các chuyến bạn đã chạy. Nhà xe quyết toán và chi
                    trả trực tiếp cho tài xế.</span
                >
            </div>
        </div>

        <!-- Period tabs -->
        <div class="mb-5 flex gap-2">
            <button
                v-for="p in periods"
                :key="p.key"
                @click="period = p.key"
                :class="[
                    'rounded-xl px-5 py-2.5 text-sm font-semibold transition-colors',
                    period === p.key
                        ? 'bg-green-600 text-white shadow-sm'
                        : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50',
                ]"
            >
                {{ p.label }}
            </button>
        </div>

        <!-- Loading skeleton -->
        <div v-if="isLoading" class="space-y-4">
            <div class="grid grid-cols-3 gap-4">
                <div
                    v-for="i in 3"
                    :key="i"
                    class="h-20 animate-pulse rounded-xl bg-gray-200"
                />
            </div>
            <div class="h-40 animate-pulse rounded-xl bg-gray-200" />
        </div>

        <div v-else class="space-y-5">
            <!-- Stats cards -->
            <div class="grid grid-cols-3 gap-4">
                <div
                    v-for="stat in [
                        {
                            label: 'Số chuyến',
                            value: earnings?.trip_count ?? 0,
                            icon: '🚐',
                            color: 'text-green-600',
                        },
                        {
                            label: 'Hành khách',
                            value: earnings?.passenger_count ?? 0,
                            icon: '👥',
                            color: 'text-blue-600',
                        },
                        {
                            label: 'Tổng km',
                            value: (earnings?.total_km ?? 0) + ' km',
                            icon: '📍',
                            color: 'text-gray-700',
                            raw: true,
                        },
                    ]"
                    :key="stat.label"
                    class="rounded-xl border border-gray-200 bg-white p-4 text-center shadow-sm"
                >
                    <div class="mb-2 text-2xl">{{ stat.icon }}</div>
                    <p :class="['text-2xl font-black', stat.color]">
                        {{ stat.value }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ stat.label }}</p>
                </div>
            </div>

            <!-- Total earnings -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">
                        Thu nhập
                        {{
                            period === 'today'
                                ? 'hôm nay'
                                : period === 'week'
                                  ? 'tuần này'
                                  : 'tháng này'
                        }}
                    </h3>
                    <p class="text-2xl font-black text-green-600">
                        {{ fmt(earnings?.total ?? 0) }}
                    </p>
                </div>

                <!-- Bar chart -->
                <div v-if="earnings?.daily_amounts?.length">
                    <div class="mb-2 flex h-24 items-end gap-1.5">
                        <div
                            v-for="(val, i) in earnings.daily_amounts"
                            :key="i"
                            class="flex-1 rounded-t-lg transition-all"
                            :class="
                                i === new Date().getDay()
                                    ? 'bg-green-500'
                                    : 'bg-green-200'
                            "
                            :style="{
                                height:
                                    Math.max(
                                        (val / maxBar) * 100,
                                        val > 0 ? 8 : 4,
                                    ) + '%',
                            }"
                            :title="fmt(val)"
                        />
                    </div>
                    <div class="flex">
                        <span
                            v-for="(label, i) in dayLabels"
                            :key="i"
                            class="flex-1 text-center text-xs text-gray-400"
                            >{{ label }}</span
                        >
                    </div>
                </div>
                <div
                    v-else
                    class="flex h-24 items-center justify-center rounded-xl bg-gray-50 text-sm text-gray-400"
                >
                    Chưa có dữ liệu biểu đồ
                </div>
            </div>

            <!-- Transaction list -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <div
                    class="flex items-center justify-between border-b border-gray-100 px-5 py-3"
                >
                    <h3 class="font-semibold text-gray-900">
                        Lịch sử thu nhập
                    </h3>
                    <span class="text-sm text-gray-400"
                        >{{ transactions.length }} giao dịch</span
                    >
                </div>

                <div
                    v-if="transactions.length === 0"
                    class="p-8 text-center text-gray-400"
                >
                    <p class="mb-2 text-2xl">📋</p>
                    <p>Chưa có giao dịch nào trong kỳ này</p>
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div
                        v-for="tx in transactions"
                        :key="tx.id"
                        class="flex items-center gap-4 px-5 py-4 transition-colors hover:bg-gray-50"
                    >
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-100"
                        >
                            <span class="text-lg">🚐</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-gray-900">
                                {{ tx.route ?? 'Chuyến đi' }}
                            </p>
                            <div
                                class="mt-0.5 flex items-center gap-2 text-sm text-gray-500"
                            >
                                <span>{{
                                    fmtDate(tx.date ?? tx.created_at)
                                }}</span>
                                <span>·</span>
                                <span>{{ tx.passenger_count ?? 0 }} khách</span>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="font-black text-green-600">
                                +{{ fmt(tx.amount ?? 0) }}
                            </p>
                            <span
                                :class="[
                                    'text-xs font-medium',
                                    tx.status === 'paid'
                                        ? 'text-green-500'
                                        : 'text-amber-500',
                                ]"
                            >
                                {{
                                    tx.status === 'paid'
                                        ? 'Đã nhận'
                                        : 'Đang xử lý'
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="transactions.length >= 10"
                    class="flex justify-center gap-2 border-t border-gray-100 px-5 py-4"
                >
                    <button
                        @click="
                            txPage--;
                            load();
                        "
                        :disabled="txPage <= 1"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-sm transition-colors hover:bg-gray-50 disabled:opacity-40"
                    >
                        ‹ Trước
                    </button>
                    <span class="px-4 py-2 text-sm text-gray-600"
                        >Trang {{ txPage }}</span
                    >
                    <button
                        @click="
                            txPage++;
                            load();
                        "
                        :disabled="transactions.length < 10"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-sm transition-colors hover:bg-gray-50 disabled:opacity-40"
                    >
                        Tiếp ›
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
