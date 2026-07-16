<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { operatorApi } from '@/api/operator.api';

interface Booking {
    id: string;
    tracking_code: string;
    status: 'pending_payment' | 'confirmed' | 'cancelled' | 'completed';
    passenger_name: string;
    passenger_phone: string;
    trip: {
        tracking_code: string;
        depart_at: string;
        route: { origin_city: string; dest_city: string };
    };
    seat_codes: string[];
    pickup_stop: { stop_name: string };
    dropoff_stop: { stop_name: string };
    total_amount: number;
    created_at: string;
}

const bookings = ref<Booking[]>([]);
const loading = ref(true);
const error = ref('');
const search = ref('');
const statusTab = ref<
    'all' | 'pending_payment' | 'confirmed' | 'cancelled' | 'completed'
>('all');
const page = ref(1);
const totalPages = ref(1);
const totalCount = ref(0);

const detailModal = ref(false);
const detailItem = ref<Booking | null>(null);

const statusConfig: Record<string, { label: string; cls: string }> = {
    pending_payment: {
        label: 'Chờ thanh toán',
        cls: 'bg-yellow-50 text-yellow-700',
    },
    confirmed: { label: 'Đã xác nhận', cls: 'bg-green-50 text-green-700' },
    cancelled: { label: 'Đã huỷ', cls: 'bg-red-50 text-red-700' },
    completed: { label: 'Hoàn thành', cls: 'bg-gray-100 text-gray-700' },
};

const tabs = [
    { v: 'all', l: 'Tất cả' },
    { v: 'confirmed', l: 'Đã xác nhận' },
    { v: 'pending_payment', l: 'Chờ TT' },
    { v: 'completed', l: 'Hoàn thành' },
    { v: 'cancelled', l: 'Đã huỷ' },
];

async function fetchBookings() {
    loading.value = true;
    error.value = '';
    const params: Record<string, unknown> = { page: page.value };
    if (search.value.trim()) params.search = search.value.trim();
    if (statusTab.value !== 'all') params.status = statusTab.value;
    const { data, meta, error: err } = await operatorApi.getBookings(params);
    loading.value = false;
    if (err) {
        error.value = err;
        return;
    }
    bookings.value = data ?? [];
    totalPages.value = meta?.last_page ?? 1;
    totalCount.value = meta?.total ?? bookings.value.length;
}

function onFilter() {
    page.value = 1;
    fetchBookings();
}

function fmtDate(d: string) {
    return new Date(d).toLocaleDateString('vi-VN');
}

function fmtDateTime(d: string) {
    return new Date(d).toLocaleString('vi-VN', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

function fmtMoney(n: number) {
    return n.toLocaleString('vi-VN') + ' đ';
}

onMounted(fetchBookings);
</script>

<template>
    <div class="mx-auto max-w-7xl p-6">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Quản lý đặt chỗ</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    {{ totalCount }} đặt chỗ
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="mb-5 space-y-3 rounded-xl border border-gray-200 bg-white p-4"
        >
            <!-- Status tabs -->
            <div class="flex w-fit flex-wrap gap-1 rounded-lg bg-gray-100 p-1">
                <button
                    v-for="t in tabs"
                    :key="t.v"
                    @click="
                        statusTab = t.v as typeof statusTab.value;
                        onFilter();
                    "
                    :class="[
                        'rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                        statusTab === t.v
                            ? 'bg-white text-gray-900 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700',
                    ]"
                >
                    {{ t.l }}
                </button>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative max-w-sm flex-1">
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
                        placeholder="Tìm mã đặt, tên, SĐT khách..."
                        class="w-full rounded-lg border border-gray-200 py-2 pr-4 pl-9 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                        @keyup.enter="onFilter"
                    />
                </div>
                <button
                    @click="onFilter"
                    class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-amber-600"
                >
                    Tìm kiếm
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div
            v-if="loading"
            class="rounded-xl border border-gray-200 bg-white p-16 text-center"
        >
            <div
                class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-2 border-amber-500 border-t-transparent"
            />
            <p class="text-sm text-gray-500">Đang tải...</p>
        </div>

        <div
            v-else-if="error"
            class="rounded-xl border border-gray-200 bg-white p-12 text-center"
        >
            <p class="mb-4 text-sm text-red-500">{{ error }}</p>
            <button
                @click="fetchBookings"
                class="rounded-lg bg-amber-500 px-4 py-2 text-sm text-white"
            >
                Thử lại
            </button>
        </div>

        <!-- Table -->
        <div
            v-else
            class="overflow-hidden rounded-xl border border-gray-200 bg-white"
        >
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Mã đặt
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Khách hàng
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Chuyến đi
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Ghế / Điểm
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase"
                            >
                                Tổng tiền
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
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Chi tiết
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="bookings.length === 0">
                            <td
                                colspan="8"
                                class="px-4 py-12 text-center text-sm text-gray-400"
                            >
                                Không có đặt chỗ nào
                            </td>
                        </tr>
                        <tr
                            v-for="b in bookings"
                            :key="b.id"
                            class="transition-colors hover:bg-gray-50"
                        >
                            <!-- Code -->
                            <td class="px-4 py-3">
                                <span
                                    class="font-mono text-xs font-semibold text-gray-700"
                                    >{{ b.tracking_code }}</span
                                >
                            </td>
                            <!-- Passenger -->
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">
                                    {{ b.passenger_name }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ b.passenger_phone }}
                                </p>
                            </td>
                            <!-- Trip -->
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">
                                    {{ b.trip?.route?.origin_city }} →
                                    {{ b.trip?.route?.dest_city }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{
                                        b.trip?.depart_at
                                            ? fmtDateTime(b.trip.depart_at)
                                            : '—'
                                    }}
                                </p>
                            </td>
                            <!-- Seats / Stops -->
                            <td class="px-4 py-3">
                                <p class="text-gray-700">
                                    <span
                                        class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs"
                                    >
                                        {{ b.seat_codes?.join(', ') }}
                                    </span>
                                </p>
                                <p class="mt-0.5 text-xs text-gray-400">
                                    {{ b.pickup_stop?.stop_name }} →
                                    {{ b.dropoff_stop?.stop_name }}
                                </p>
                            </td>
                            <!-- Amount -->
                            <td
                                class="px-4 py-3 text-right font-semibold text-gray-900"
                            >
                                {{ fmtMoney(b.total_amount) }}
                            </td>
                            <!-- Status -->
                            <td class="px-4 py-3 text-center">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
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
                            <!-- Date -->
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ fmtDate(b.created_at) }}
                            </td>
                            <!-- Detail -->
                            <td class="px-4 py-3 text-center">
                                <button
                                    @click="
                                        detailItem = b;
                                        detailModal = true;
                                    "
                                    class="rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100"
                                >
                                    Xem
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="totalPages > 1"
                class="flex items-center justify-between border-t border-gray-200 px-4 py-3"
            >
                <p class="text-xs text-gray-500">
                    Trang {{ page }} / {{ totalPages }}
                </p>
                <div class="flex gap-2">
                    <button
                        :disabled="page <= 1"
                        @click="
                            page--;
                            fetchBookings();
                        "
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-40"
                    >
                        ← Trước
                    </button>
                    <button
                        :disabled="page >= totalPages"
                        @click="
                            page++;
                            fetchBookings();
                        "
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-40"
                    >
                        Sau →
                    </button>
                </div>
            </div>
        </div>

        <!-- Detail modal -->
        <Teleport to="body">
            <div
                v-if="detailModal && detailItem"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div
                    class="absolute inset-0 bg-black/40"
                    @click="detailModal = false"
                />
                <div
                    class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl"
                >
                    <div class="mb-5 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">
                            Chi tiết đặt chỗ
                        </h3>
                        <button
                            @click="detailModal = false"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Mã đặt</span>
                            <span class="font-mono font-semibold">{{
                                detailItem.tracking_code
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Khách hàng</span>
                            <span class="font-medium"
                                >{{ detailItem.passenger_name }} —
                                {{ detailItem.passenger_phone }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tuyến</span>
                            <span
                                >{{ detailItem.trip?.route?.origin_city }} →
                                {{ detailItem.trip?.route?.dest_city }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Giờ khởi hành</span>
                            <span>{{
                                detailItem.trip?.depart_at
                                    ? fmtDateTime(detailItem.trip.depart_at)
                                    : '—'
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Ghế</span>
                            <span class="font-mono">{{
                                detailItem.seat_codes?.join(', ')
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Điểm đón</span>
                            <span>{{ detailItem.pickup_stop?.stop_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Điểm trả</span>
                            <span>{{
                                detailItem.dropoff_stop?.stop_name
                            }}</span>
                        </div>
                        <div class="mt-3 flex justify-between border-t pt-3">
                            <span class="text-gray-500">Tổng tiền</span>
                            <span class="text-lg font-bold text-amber-600">{{
                                fmtMoney(detailItem.total_amount)
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Trạng thái</span>
                            <span
                                :class="[
                                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                    statusConfig[detailItem.status]?.cls ??
                                        'bg-gray-100 text-gray-600',
                                ]"
                            >
                                {{ statusConfig[detailItem.status]?.label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
