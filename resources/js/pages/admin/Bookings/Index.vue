<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { watchDebounced } from '@vueuse/core';
import { adminApi } from '@/api/admin.api';

interface Booking {
    id: string;
    code: string;
    customer: string;
    phone: string;
    route: string;
    depart_at: string | null;
    passenger_count: number;
    amount: number;
    payment_status: 'unpaid' | 'paid' | 'refunded' | 'partial_refund';
    status:
        | 'pending'
        | 'confirmed'
        | 'checked_in'
        | 'completed'
        | 'cancelled'
        | 'no_show';
    created_at: string;
}

const bookings = ref<Booking[]>([]);
const loading = ref(true);
const error = ref('');
const search = ref('');
const statusTab = ref('all');
const dateFrom = ref('');
const dateTo = ref('');
const totalCount = ref(0);

const statusConfig: Record<string, { label: string; cls: string }> = {
    pending: { label: 'Chờ thanh toán', cls: 'bg-amber-50 text-amber-700' },
    confirmed: { label: 'Đã xác nhận', cls: 'bg-blue-50 text-blue-700' },
    checked_in: { label: 'Đã lên xe', cls: 'bg-indigo-50 text-indigo-700' },
    completed: { label: 'Hoàn thành', cls: 'bg-green-50 text-green-700' },
    cancelled: { label: 'Đã huỷ', cls: 'bg-red-50 text-red-700' },
    no_show: { label: 'Không lên xe', cls: 'bg-gray-100 text-gray-600' },
};

const paymentConfig: Record<string, { label: string; cls: string }> = {
    unpaid: { label: 'Chưa trả', cls: 'bg-gray-100 text-gray-600' },
    paid: { label: 'Đã trả', cls: 'bg-green-50 text-green-700' },
    refunded: { label: 'Đã hoàn', cls: 'bg-purple-50 text-purple-700' },
    partial_refund: {
        label: 'Hoàn 1 phần',
        cls: 'bg-purple-50 text-purple-700',
    },
};

const tabs = [
    { v: 'all', l: 'Tất cả' },
    { v: 'pending', l: 'Chờ thanh toán' },
    { v: 'confirmed', l: 'Đã xác nhận' },
    { v: 'checked_in', l: 'Đã lên xe' },
    { v: 'completed', l: 'Hoàn thành' },
    { v: 'cancelled', l: 'Đã huỷ' },
];

async function fetchBookings() {
    loading.value = true;
    error.value = '';
    const params: Record<string, unknown> = { per_page: 100 };
    if (search.value.trim()) params.search = search.value.trim();
    if (statusTab.value !== 'all') params.status = statusTab.value;
    if (dateFrom.value) params.from_date = dateFrom.value;
    if (dateTo.value) params.to_date = dateTo.value;

    const { data, error: err } = await adminApi.getBookings(params);
    loading.value = false;
    if (err) {
        error.value = err;
        return;
    }
    bookings.value = data ?? [];
    totalCount.value = bookings.value.length;
}

function onFilter() {
    fetchBookings();
}

function fmtCurrency(v: number) {
    return new Intl.NumberFormat('vi-VN').format(v) + 'đ';
}

// Bộ lọc tự động: ô tìm kiếm debounce 350ms (tab & ngày đã tự lọc khi đổi).
watchDebounced(search, onFilter, { debounce: 350 });

onMounted(fetchBookings);
</script>

<template>
    <div class="p-6">
        <!-- Header -->
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Quản lý đặt vé</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    {{ totalCount }} vé trong danh sách
                </p>
            </div>
        </div>

        <!-- Status tabs -->
        <div class="mb-4 flex flex-wrap gap-2">
            <button
                v-for="t in tabs"
                :key="t.v"
                @click="
                    statusTab = t.v;
                    onFilter();
                "
                :class="[
                    'rounded-lg px-3.5 py-1.5 text-sm font-medium transition-colors',
                    statusTab === t.v
                        ? 'bg-red-600 text-white'
                        : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50',
                ]"
            >
                {{ t.l }}
            </button>
        </div>

        <!-- Search + date range -->
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <div class="relative min-w-[240px] flex-1">
                <svg
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
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
                <input
                    v-model="search"
                    type="text"
                    placeholder="Tìm mã vé, tên khách, SĐT..."
                    class="w-full rounded-lg border border-gray-200 py-2 pr-3 pl-9 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                />
            </div>
            <input
                v-model="dateFrom"
                type="date"
                @change="onFilter"
                class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
            />
            <span class="text-sm text-gray-400">–</span>
            <input
                v-model="dateTo"
                type="date"
                @change="onFilter"
                class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
            />
        </div>

        <!-- Error -->
        <div
            v-if="error"
            class="mb-4 flex items-center justify-between rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
        >
            {{ error }}
            <button class="underline" @click="fetchBookings">Thử lại</button>
        </div>

        <!-- Table -->
        <div
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
        >
            <div v-if="loading" class="space-y-2 p-4">
                <div
                    v-for="i in 6"
                    :key="i"
                    class="h-12 animate-pulse rounded bg-gray-100"
                />
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Mã vé
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Khách hàng
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Tuyến
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Giờ đi
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Khách
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase"
                            >
                                Số tiền
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Thanh toán
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Trạng thái
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Ngày đặt
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="bookings.length === 0">
                            <td
                                colspan="9"
                                class="px-4 py-12 text-center text-sm text-gray-400"
                            >
                                Không có vé nào phù hợp
                            </td>
                        </tr>
                        <tr
                            v-for="b in bookings"
                            :key="b.id"
                            class="transition-colors hover:bg-gray-50"
                        >
                            <td
                                class="px-4 py-3 font-mono text-xs font-medium text-gray-900"
                            >
                                {{ b.code }}
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-gray-800">{{ b.customer }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ b.phone }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                {{ b.route }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                {{ b.depart_at ?? '—' }}
                            </td>
                            <td
                                class="px-4 py-3 text-center font-medium text-gray-800"
                            >
                                {{ b.passenger_count }}
                            </td>
                            <td
                                class="px-4 py-3 text-right font-medium text-gray-900"
                            >
                                {{ fmtCurrency(b.amount) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                        paymentConfig[b.payment_status]?.cls ??
                                            'bg-gray-100 text-gray-600',
                                    ]"
                                >
                                    {{
                                        paymentConfig[b.payment_status]
                                            ?.label ?? b.payment_status
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                        statusConfig[b.status]?.cls ??
                                            'bg-gray-100 text-gray-600',
                                    ]"
                                >
                                    {{
                                        statusConfig[b.status]?.label ??
                                        b.status
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ b.created_at }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
