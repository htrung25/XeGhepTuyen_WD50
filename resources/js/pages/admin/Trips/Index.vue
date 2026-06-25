<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { adminApi } from '@/api/admin.api';

interface Trip {
    id: string;
    tracking_code: string;
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
    depart_at: string;
    arrive_at: string;
    route: { origin_city: string; dest_city: string };
    vehicle: { plate_number: string; vehicle_type: string };
    driver: { full_name: string; phone: string } | null;
    operator: { company_name: string };
    passengers_count: number;
    available_seats: number;
    total_seats: number;
}

const router = useRouter();
const trips = ref<Trip[]>([]);
const loading = ref(true);
const error = ref('');
const search = ref('');
const statusTab = ref<
    'all' | 'scheduled' | 'in_progress' | 'completed' | 'cancelled'
>('all');
const dateFrom = ref('');
const dateTo = ref('');
const page = ref(1);
const totalPages = ref(1);
const totalCount = ref(0);

const cancelModal = ref(false);
const cancelTarget = ref<Trip | null>(null);
const cancelReason = ref('');
const cancelLoading = ref(false);

const statusConfig: Record<string, { label: string; cls: string }> = {
    scheduled: { label: 'Đã lên lịch', cls: 'bg-blue-50 text-blue-700' },
    in_progress: { label: 'Đang chạy', cls: 'bg-green-50 text-green-700' },
    completed: { label: 'Hoàn thành', cls: 'bg-gray-100 text-gray-700' },
    cancelled: { label: 'Đã huỷ', cls: 'bg-red-50 text-red-700' },
};

const tabs = [
    { v: 'all', l: 'Tất cả' },
    { v: 'scheduled', l: 'Đã lên lịch' },
    { v: 'in_progress', l: 'Đang chạy' },
    { v: 'completed', l: 'Hoàn thành' },
    { v: 'cancelled', l: 'Đã huỷ' },
];

async function fetchTrips() {
    loading.value = true;
    error.value = '';
    const params: Record<string, unknown> = { page: page.value };
    if (search.value.trim()) params.search = search.value.trim();
    if (statusTab.value !== 'all') params.status = statusTab.value;
    if (dateFrom.value) params.from_date = dateFrom.value;
    if (dateTo.value) params.to_date = dateTo.value;
    const { data, error: err } = await adminApi.getTrips(params);
    loading.value = false;
    if (err) {
        error.value = err;
        return;
    }
    trips.value = data.data ?? data;
    totalPages.value = data.meta?.last_page ?? 1;
    totalCount.value = data.meta?.total ?? trips.value.length;
}

function onFilter() {
    page.value = 1;
    fetchTrips();
}

// Detail modal states and interfaces
interface Stop {
    id: string;
    stop_name: string;
    address: string;
    stop_order: number;
    offset_minutes: number;
}

interface Booking {
    id: string;
    booking_code: string;
    booking_status: string;
    payment_status: string;
    payment_method: string;
    contact_name: string;
    contact_phone: string;
    passenger_count: number;
    final_amount: number;
    pickup_stop: string;
    dropoff_stop: string;
    created_at: string;
}

interface TripDetail {
    id: string;
    tracking_code: string;
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
    depart_at: string;
    arrive_at: string | null;
    price: number;
    note: string | null;
    cancel_reason: string | null;
    started_at: string | null;
    completed_at: string | null;
    cancelled_at: string | null;
    route: {
        id: string;
        name: string;
        origin_city: string;
        dest_city: string;
        stops?: Stop[];
    };
    vehicle: {
        plate_number: string;
        vehicle_type: string;
        seat_count: number;
        brand: string | null;
        model: string | null;
        color: string | null;
    };
    operator: {
        id: string;
        company_name: string;
    };
    driver: {
        id: string;
        full_name: string;
        phone: string;
        rating_avg: number | null;
        is_online: boolean;
    } | null;
    booking_count: number;
    passengers_count: number;
    total_seats: number;
    available_seats: number;
    revenue: number;
    created_at: string;
    bookings?: Booking[];
}

const detailModal = ref(false);
const detailTrip = ref<TripDetail | null>(null);
const detailLoading = ref(false);
const detailError = ref('');

const bookingStatusConfig: Record<string, { label: string; cls: string }> = {
    pending: { label: 'Chờ thanh toán', cls: 'bg-amber-50 text-amber-700 border-amber-200' },
    confirmed: { label: 'Đã xác nhận', cls: 'bg-blue-50 text-blue-700 border-blue-200' },
    checked_in: { label: 'Đã lên xe', cls: 'bg-indigo-50 text-indigo-700 border-indigo-200' },
    completed: { label: 'Hoàn thành', cls: 'bg-green-50 text-green-700 border-green-200' },
    cancelled: { label: 'Đã huỷ', cls: 'bg-red-50 text-red-700 border-red-200' },
    no_show: { label: 'Không lên xe', cls: 'bg-gray-100 text-gray-600 border-gray-200' },
};

const paymentStatusConfig: Record<string, { label: string; cls: string }> = {
    unpaid: { label: 'Chưa trả', cls: 'bg-gray-100 text-gray-600 border-gray-200' },
    paid: { label: 'Đã trả', cls: 'bg-green-50 text-green-700 border-green-200' },
    refunded: { label: 'Đã hoàn', cls: 'bg-purple-50 text-purple-700 border-purple-200' },
    partial_refund: { label: 'Hoàn 1 phần', cls: 'bg-purple-50 text-purple-700 border-purple-200' },
};

const vehicleTypeLabels: Record<string, string> = {
    sedan_4: 'Sedan 4 chỗ',
    mpv_7: 'SUV/MPV 7 chỗ',
    van_9: 'Limousine 9 chỗ',
    minibus_16: 'Xe khách 16 chỗ',
};

async function openDetailModal(id: string) {
    detailModal.value = true;
    detailLoading.value = true;
    detailError.value = '';
    detailTrip.value = null;
    const { data, error: err } = await adminApi.getTrip(id);
    detailLoading.value = false;
    if (err) {
        detailError.value = err;
        return;
    }
    detailTrip.value = data.data ?? data;
}

function openCancelModal(trip: any) {
    cancelTarget.value = trip;
    cancelReason.value = '';
    cancelModal.value = true;
}

async function confirmCancel() {
    if (!cancelTarget.value || !cancelReason.value.trim()) return;
    cancelLoading.value = true;
    const { error: err } = await adminApi.cancelTrip(cancelTarget.value.id, {
        reason: cancelReason.value,
    });
    cancelLoading.value = false;
    if (err) {
        alert(err);
        return;
    }
    cancelModal.value = false;
    fetchTrips();
    if (detailModal.value && detailTrip.value?.id === cancelTarget.value.id) {
        openDetailModal(detailTrip.value.id);
    }
}

function fmtCurrency(v: number) {
    return new Intl.NumberFormat('vi-VN').format(v) + 'đ';
}

function fmtDateTime(d: string | null | undefined) {
    if (!d) return '—';
    return new Date(d).toLocaleString('vi-VN', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

function getInitials(name?: string) {
    if (!name) return 'TX';
    return name
        .split(' ')
        .map((n) => n[0])
        .slice(-2)
        .join('')
        .toUpperCase();
}

onMounted(fetchTrips);
</script>

<template>
    <div class="mx-auto max-w-7xl p-6">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">
                    Quản lý chuyến đi
                </h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    {{ totalCount }} chuyến trong hệ thống
                </p>
            </div>
            <button
                @click="$router.push('/admin/trips/live')"
                class="flex items-center gap-2 rounded-xl bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700"
            >
                <span class="h-2 w-2 animate-pulse rounded-full bg-green-200" />
                Xem trực tiếp
            </button>
        </div>

        <!-- Filters -->
        <div
            class="mb-5 space-y-3 rounded-xl border border-gray-200 bg-white p-4"
        >
            <!-- Status tabs -->
            <div class="flex w-fit gap-1 rounded-lg bg-gray-100 p-1">
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

            <div class="flex flex-wrap items-center gap-3">
                <!-- Search -->
                <div class="relative min-w-[220px] flex-1">
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
                        placeholder="Tìm mã chuyến, tuyến đường..."
                        class="w-full rounded-lg border border-gray-200 py-2 pr-4 pl-9 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                        @keyup.enter="onFilter"
                    />
                </div>

                <!-- Date range -->
                <div class="flex items-center gap-2">
                    <input
                        v-model="dateFrom"
                        type="date"
                        class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                    />
                    <span class="text-sm text-gray-400">–</span>
                    <input
                        v-model="dateTo"
                        type="date"
                        class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                    />
                </div>

                <button
                    @click="onFilter"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700"
                >
                    Lọc
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div
            v-if="loading"
            class="rounded-xl border border-gray-200 bg-white p-16 text-center"
        >
            <div
                class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-2 border-red-600 border-t-transparent"
            />
            <p class="text-sm text-gray-500">Đang tải...</p>
        </div>

        <div
            v-else-if="error"
            class="rounded-xl border border-gray-200 bg-white p-12 text-center"
        >
            <p class="mb-4 text-sm text-red-500">{{ error }}</p>
            <button
                @click="fetchTrips"
                class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white"
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
                                Mã / Tuyến
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Giờ chạy
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Nhà xe
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Tài xế / Xe
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Khách
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Trạng thái
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Thao tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="trips.length === 0">
                            <td
                                colspan="7"
                                class="px-4 py-12 text-center text-sm text-gray-400"
                            >
                                Không có chuyến đi nào
                            </td>
                        </tr>
                        <tr
                            v-for="trip in trips"
                            :key="trip.id"
                            class="transition-colors hover:bg-gray-50"
                        >
                            <!-- Code / Route -->
                            <td class="px-4 py-3">
                                <p class="font-mono text-xs text-gray-500">
                                    {{ trip.tracking_code }}
                                </p>
                                <p class="mt-0.5 font-medium text-gray-900">
                                    {{ trip.route.origin_city }} →
                                    {{ trip.route.dest_city }}
                                </p>
                            </td>
                            <!-- Time -->
                            <td class="px-4 py-3 text-gray-700">
                                <p>{{ fmtDateTime(trip.depart_at) }}</p>
                                <p class="text-xs text-gray-400">
                                    → {{ fmtDateTime(trip.arrive_at) }}
                                </p>
                            </td>
                            <!-- Operator -->
                            <td class="px-4 py-3 text-gray-700">
                                {{ trip.operator?.company_name ?? '—' }}
                            </td>
                            <!-- Driver / Vehicle -->
                            <td class="px-4 py-3">
                                <p class="text-gray-800">
                                    {{ trip.driver?.full_name ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ trip.vehicle?.plate_number }}
                                </p>
                            </td>
                            <!-- Seats -->
                            <td class="px-4 py-3 text-center">
                                <span class="font-medium text-gray-800">{{
                                    trip.passengers_count ?? 0
                                }}</span>
                                <span class="text-xs text-gray-400">
                                    / {{ trip.total_seats ?? '?' }}</span
                                >
                            </td>
                            <!-- Status -->
                            <td class="px-4 py-3 text-center">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                        statusConfig[trip.status]?.cls ??
                                            'bg-gray-100 text-gray-600',
                                    ]"
                                >
                                    {{
                                        statusConfig[trip.status]?.label ??
                                        trip.status
                                    }}
                                </span>
                            </td>
                            <!-- Actions -->
                            <td class="px-4 py-3 text-center">
                                <div
                                    class="flex items-center justify-center gap-1.5"
                                >
                                    <button
                                        @click="
                                            openDetailModal(trip.id)
                                        "
                                        class="rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100"
                                    >
                                        Chi tiết
                                    </button>
                                    <button
                                        v-if="trip.status === 'in_progress'"
                                        @click="
                                            router.push('/admin/trips/live')
                                        "
                                        class="rounded-lg bg-green-50 px-2.5 py-1.5 text-xs font-medium text-green-700 transition-colors hover:bg-green-100"
                                    >
                                        Live
                                    </button>
                                    <button
                                        v-if="
                                            [
                                                'scheduled',
                                                'in_progress',
                                            ].includes(trip.status)
                                        "
                                        @click="openCancelModal(trip)"
                                        class="rounded-lg bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-100"
                                    >
                                        Huỷ
                                    </button>
                                </div>
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
                            fetchTrips();
                        "
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-40"
                    >
                        ← Trước
                    </button>
                    <button
                        :disabled="page >= totalPages"
                        @click="
                            page++;
                            fetchTrips();
                        "
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-40"
                    >
                        Sau →
                    </button>
                </div>
            </div>
        </div>

        <!-- Cancel Modal -->
        <Teleport to="body">
            <div
                v-if="cancelModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div
                    class="absolute inset-0 bg-black/40"
                    @click="cancelModal = false"
                />
                <div
                    class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
                >
                    <h3 class="mb-1 text-lg font-bold text-gray-900">
                        Huỷ chuyến đi
                    </h3>
                    <p class="mb-4 text-sm text-gray-500">
                        Huỷ chuyến
                        <strong>{{ cancelTarget?.tracking_code }}</strong> ({{
                            cancelTarget?.route?.origin_city
                        }}
                        → {{ cancelTarget?.route?.dest_city }})?
                    </p>
                    <div class="mb-4">
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Lý do huỷ <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="cancelReason"
                            rows="3"
                            placeholder="Nhập lý do huỷ chuyến..."
                            class="w-full resize-none rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                        />
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            @click="cancelModal = false"
                            class="rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                        >
                            Huỷ
                        </button>
                        <button
                            @click="confirmCancel"
                            :disabled="!cancelReason.trim() || cancelLoading"
                            class="flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700 disabled:opacity-50"
                        >
                            <svg
                                v-if="cancelLoading"
                                class="h-4 w-4 animate-spin"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                />
                            </svg>
                            Xác nhận huỷ
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Trip Detail Modal -->
        <Teleport to="body">
            <div
                v-if="detailModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity animate-fade-in"
                    @click="detailModal = false"
                />

                <!-- Modal Container -->
                <div
                    class="relative flex h-[90vh] w-full max-w-6xl flex-col rounded-2xl bg-[#F7F9FB] shadow-2xl overflow-hidden transition-all"
                >
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-xs font-semibold text-gray-500">
                                {{ detailTrip?.tracking_code || 'Trip Details' }}
                            </span>
                            <span
                                v-if="detailTrip"
                                :class="[
                                    'inline-flex rounded-full border px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider',
                                    statusConfig[detailTrip.status]?.cls || 'bg-gray-100 text-gray-700'
                                ]"
                            >
                                {{ statusConfig[detailTrip.status]?.label || detailTrip.status }}
                            </span>
                        </div>
                        <button
                            @click="detailModal = false"
                            class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="flex-1 overflow-y-auto p-6">
                        <!-- Loading State -->
                        <div v-if="detailLoading" class="flex min-h-[400px] items-center justify-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="h-10 w-10 animate-spin rounded-full border-4 border-amber-500 border-t-transparent" />
                                <p class="text-sm font-medium text-gray-500 font-sans">Đang tải chi tiết chuyến đi...</p>
                            </div>
                        </div>

                        <!-- Error State -->
                        <div v-else-if="detailError" class="rounded-xl border border-red-200 bg-red-50 p-6 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mx-auto h-12 w-12 text-red-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 7.5h.008v.008H12v-.008Z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900 font-sans">Không thể tải thông tin</h3>
                            <p class="mt-2 text-sm text-red-700 font-sans">{{ detailError }}</p>
                            <button
                                @click="detailModal = false"
                                class="mt-4 rounded-xl bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300"
                            >
                                Đóng
                            </button>
                        </div>

                        <!-- Data Loaded -->
                        <div v-else-if="detailTrip" class="space-y-6">
                            <!-- Title Banner -->
                            <div class="flex flex-col justify-between gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900 font-sans">
                                        {{ detailTrip.route.origin_city }} ↔ {{ detailTrip.route.dest_city }}
                                    </h2>
                                    <p class="text-xs text-gray-400 font-sans">
                                        Khởi hành: {{ fmtDateTime(detailTrip.depart_at) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="detailTrip.status === 'in_progress'"
                                        @click="detailModal = false; router.push('/admin/trips/live')"
                                        class="flex items-center gap-2 rounded-xl bg-green-600 px-3.5 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-green-700"
                                    >
                                        Theo dõi GPS
                                    </button>
                                    <button
                                        v-if="['scheduled', 'in_progress'].includes(detailTrip.status)"
                                        @click="openCancelModal(detailTrip)"
                                        class="rounded-xl border border-red-200 bg-red-50 px-3.5 py-1.5 text-xs font-semibold text-red-600 transition-colors hover:bg-red-100"
                                    >
                                        Huỷ chuyến đi
                                    </button>
                                </div>
                            </div>

                            <!-- Content Grid -->
                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                                <!-- Left Section (2/3) -->
                                <div class="space-y-6 lg:col-span-2">
                                    <!-- Info Card -->
                                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                        <h3 class="mb-3 text-sm font-bold text-gray-900 font-sans">Thông tin hành trình</h3>
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <p class="text-[10px] font-bold text-gray-400 uppercase font-sans">Tuyến đường</p>
                                                <p class="mt-0.5 text-sm font-semibold text-gray-800 font-sans">
                                                    {{ detailTrip.route.name || `${detailTrip.route.origin_city} → ${detailTrip.route.dest_city}` }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-bold text-gray-400 uppercase font-sans">Giá vé</p>
                                                <p class="mt-0.5 text-sm font-semibold text-amber-600 font-sans">
                                                    {{ fmtCurrency(detailTrip.price) }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-bold text-gray-400 uppercase font-sans">Giờ xuất phát</p>
                                                <p class="mt-0.5 text-sm font-semibold text-gray-800 font-sans">
                                                    {{ fmtDateTime(detailTrip.depart_at) }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-bold text-gray-400 uppercase font-sans">Giờ đến dự kiến</p>
                                                <p class="mt-0.5 text-sm font-semibold text-gray-800 font-sans">
                                                    {{ fmtDateTime(detailTrip.arrive_at) }}
                                                </p>
                                            </div>
                                            
                                            <!-- Actual times -->
                                            <div v-if="detailTrip.started_at">
                                                <p class="text-[10px] font-bold text-gray-400 uppercase font-sans">Bắt đầu thực tế</p>
                                                <p class="mt-0.5 text-sm font-semibold text-green-700 font-sans">
                                                    {{ fmtDateTime(detailTrip.started_at) }}
                                                </p>
                                            </div>
                                            <div v-if="detailTrip.completed_at">
                                                <p class="text-[10px] font-bold text-gray-400 uppercase font-sans">Hoàn thành thực tế</p>
                                                <p class="mt-0.5 text-sm font-semibold text-blue-700 font-sans">
                                                    {{ fmtDateTime(detailTrip.completed_at) }}
                                                </p>
                                            </div>
                                            <div v-if="detailTrip.cancelled_at">
                                                <p class="text-[10px] font-bold text-gray-400 uppercase font-sans">Hủy chuyến lúc</p>
                                                <p class="mt-0.5 text-sm font-semibold text-red-700 font-sans">
                                                    {{ fmtDateTime(detailTrip.cancelled_at) }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Cancel Reason -->
                                        <div v-if="detailTrip.status === 'cancelled' && detailTrip.cancel_reason" class="mt-4 rounded-lg bg-red-50 p-4 border border-red-100">
                                            <h4 class="text-[10px] font-bold uppercase text-red-800 font-sans">Lý do hủy</h4>
                                            <p class="mt-0.5 text-xs text-red-700 font-medium font-sans">
                                                {{ detailTrip.cancel_reason }}
                                            </p>
                                        </div>

                                        <!-- Notes -->
                                        <div v-if="detailTrip.note" class="mt-4 border-t border-gray-100 pt-3">
                                            <p class="text-[10px] font-bold text-gray-400 uppercase font-sans">Ghi chú chuyến đi</p>
                                            <p class="mt-0.5 text-xs text-gray-700 italic font-sans">
                                                "{{ detailTrip.note }}"
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Stops Timeline -->
                                    <div v-if="detailTrip.route.stops && detailTrip.route.stops.length > 0" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                        <h3 class="mb-4 text-sm font-bold text-gray-900 font-sans">Lộ trình chi tiết</h3>
                                        <div class="relative pl-6">
                                            <div class="absolute bottom-1.5 left-2.5 top-1.5 w-0.5 bg-slate-200" />
                                            <div
                                                v-for="(stop, sIdx) in detailTrip.route.stops"
                                                :key="stop.id"
                                                class="relative mb-5 last:mb-0"
                                            >
                                                <div
                                                    :class="[
                                                        'absolute -left-[21px] top-1.5 h-3.5 w-3.5 rounded-full border-2 bg-white',
                                                        sIdx === 0
                                                            ? 'border-green-500 ring-4 ring-green-50'
                                                            : sIdx === detailTrip.route.stops.length - 1
                                                            ? 'border-red-500 ring-4 ring-red-50'
                                                            : 'border-slate-400'
                                                    ]"
                                                />
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <h4 class="text-xs font-semibold text-gray-800 font-sans">
                                                            {{ stop.stop_name }}
                                                        </h4>
                                                        <span
                                                            v-if="stop.offset_minutes > 0"
                                                            class="rounded bg-slate-100 px-1 py-0.2 text-[9px] font-medium text-gray-500 font-sans"
                                                        >
                                                            +{{ stop.offset_minutes }}m
                                                        </span>
                                                    </div>
                                                    <p class="mt-0.5 text-[10px] text-gray-500 font-sans">
                                                        {{ stop.address }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Passengers List Table -->
                                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                                        <div class="border-b border-gray-100 p-5">
                                            <h3 class="text-sm font-bold text-gray-900 font-sans">Danh sách hành khách đặt vé</h3>
                                        </div>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-left text-xs border-collapse">
                                                <thead>
                                                    <tr class="border-b border-gray-200 bg-slate-50 text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                                                        <th class="px-5 py-3">Mã vé</th>
                                                        <th class="px-5 py-3">Hành khách</th>
                                                        <th class="px-5 py-3 text-center">Số ghế</th>
                                                        <th class="px-5 py-3">Điểm đón / trả</th>
                                                        <th class="px-5 py-3 text-right">Tổng tiền</th>
                                                        <th class="px-5 py-3 text-center">Trạng thái</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 bg-white">
                                                    <tr v-if="!detailTrip.bookings || detailTrip.bookings.length === 0">
                                                        <td colspan="6" class="px-5 py-8 text-center text-gray-400 font-sans">
                                                            Chưa có đặt vé nào cho chuyến đi này.
                                                        </td>
                                                    </tr>
                                                    <tr
                                                        v-for="b in detailTrip.bookings"
                                                        :key="b.id"
                                                        class="hover:bg-slate-50 transition-colors"
                                                    >
                                                        <td class="px-5 py-3.5">
                                                            <p class="font-mono font-semibold text-gray-700">
                                                                {{ b.booking_code }}
                                                            </p>
                                                            <p class="text-[9px] text-gray-400">
                                                                {{ fmtDateTime(b.created_at) }}
                                                            </p>
                                                        </td>
                                                        <td class="px-5 py-3.5 text-gray-900">
                                                            <p class="font-semibold font-sans">
                                                                {{ b.contact_name }}
                                                            </p>
                                                            <p class="text-[10px] text-gray-500 font-mono">
                                                                {{ b.contact_phone }}
                                                            </p>
                                                        </td>
                                                        <td class="px-5 py-3.5 text-center font-semibold text-gray-800">
                                                            {{ b.passenger_count }}
                                                        </td>
                                                        <td class="px-5 py-3.5">
                                                            <div class="flex flex-col gap-0.5 max-w-[180px]">
                                                                <p class="truncate text-[10px] text-gray-700 font-sans">
                                                                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-green-500 mr-1" />
                                                                    Đón: {{ b.pickup_stop || 'N/A' }}
                                                                </p>
                                                                <p class="truncate text-[10px] text-gray-700 font-sans">
                                                                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-red-500 mr-1" />
                                                                    Trả: {{ b.dropoff_stop || 'N/A' }}
                                                                </p>
                                                            </div>
                                                        </td>
                                                        <td class="px-5 py-3.5 text-right font-semibold text-gray-900 font-mono">
                                                            {{ fmtCurrency(b.final_amount) }}
                                                        </td>
                                                        <td class="px-5 py-3.5">
                                                            <div class="flex flex-col items-center gap-1">
                                                                <span
                                                                    :class="[
                                                                        'inline-flex rounded-full px-2 py-0.5 text-[9px] font-semibold border',
                                                                        bookingStatusConfig[b.booking_status]?.cls || 'bg-gray-100 text-gray-700'
                                                                    ]"
                                                                >
                                                                    {{ bookingStatusConfig[b.booking_status]?.label || b.booking_status }}
                                                                </span>
                                                                <span
                                                                    :class="[
                                                                        'inline-flex rounded-full px-2 py-0.5 text-[9px] font-semibold border',
                                                                        paymentStatusConfig[b.payment_status]?.cls || 'bg-gray-100 text-gray-700 border-gray-200'
                                                                    ]"
                                                                >
                                                                    {{ paymentStatusConfig[b.payment_status]?.label || b.payment_status }}
                                                                </span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Section (1/3) -->
                                <div class="space-y-6">
                                    <!-- Stats Card -->
                                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                        <h3 class="mb-3 text-sm font-bold text-gray-900 font-sans">Chỉ số chuyến đi</h3>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="rounded-lg bg-slate-50 p-2.5 border border-slate-100">
                                                <p class="text-[9px] font-bold text-gray-400 uppercase font-sans">Lượt đặt vé</p>
                                                <p class="mt-0.5 text-base font-bold text-gray-800 font-sans">
                                                    {{ detailTrip.booking_count }}
                                                </p>
                                            </div>
                                            <div class="rounded-lg bg-slate-50 p-2.5 border border-slate-100">
                                                <p class="text-[9px] font-bold text-gray-400 uppercase font-sans">Hành khách</p>
                                                <p class="mt-0.5 text-base font-bold text-gray-800 font-sans">
                                                    {{ detailTrip.passengers_count }}
                                                </p>
                                            </div>
                                            <div class="rounded-lg bg-slate-50 p-2.5 border border-slate-100 col-span-2">
                                                <p class="text-[9px] font-bold text-gray-400 uppercase font-sans">Ghế trống</p>
                                                <p class="mt-0.5 text-sm font-bold text-gray-800 font-sans">
                                                    {{ detailTrip.available_seats }} / {{ detailTrip.total_seats }} ghế
                                                </p>
                                            </div>
                                            <div class="rounded-lg bg-slate-50 p-2.5 border border-slate-100 col-span-2">
                                                <p class="text-[9px] font-bold text-gray-400 uppercase font-sans">Doanh thu</p>
                                                <p class="mt-0.5 text-sm font-bold text-emerald-600 truncate font-mono">
                                                    {{ fmtCurrency(detailTrip.revenue) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Driver Card -->
                                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                        <h3 class="mb-3 text-sm font-bold text-gray-900 font-sans">Tài xế</h3>
                                        <div v-if="detailTrip.driver" class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-500 font-bold text-white shadow-sm ring-4 ring-amber-50 text-sm">
                                                {{ getInitials(detailTrip.driver.full_name) }}
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-xs text-gray-900 font-sans">{{ detailTrip.driver.full_name }}</h4>
                                                <p class="text-[10px] text-gray-500 font-semibold font-mono">{{ detailTrip.driver.phone }}</p>
                                                <div class="mt-0.5 flex items-center gap-0.5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3 text-amber-500">
                                                        <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.6 3.102-1.196 4.622c-.21.811.679 1.458 1.374 1.002L10 15.247l4.182 2.793c.695.456 1.585-.19 1.374-1.002l-1.196-4.622 3.6-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.83-4.401Z" clip-rule="evenodd" />
                                                    </svg>
                                                    <span class="text-[10px] font-semibold text-gray-600 font-sans">
                                                        {{ detailTrip.driver.rating_avg != null ? Number(detailTrip.driver.rating_avg).toFixed(1) : '—' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-center py-2 text-xs text-gray-400 italic font-sans">
                                            Chưa phân công tài xế
                                        </div>
                                    </div>

                                    <!-- Vehicle Card -->
                                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                        <h3 class="mb-3 text-sm font-bold text-gray-900 font-sans">Phương tiện</h3>
                                        <div class="space-y-2.5 text-xs">
                                            <div class="flex items-center justify-between border-b border-gray-50 pb-1.5">
                                                <span class="font-semibold text-gray-400 uppercase text-[9px] font-sans">Biển số</span>
                                                <span class="rounded bg-slate-100 px-2 py-0.2 font-mono font-bold text-gray-700 border border-slate-200">
                                                    {{ detailTrip.vehicle.plate_number }}
                                                </span>
                                            </div>
                                            <div class="flex items-center justify-between border-b border-gray-50 pb-1.5">
                                                <span class="font-semibold text-gray-400 uppercase text-[9px] font-sans">Loại xe</span>
                                                <span class="font-bold text-gray-800 font-sans">
                                                    {{ vehicleTypeLabels[detailTrip.vehicle.vehicle_type] || detailTrip.vehicle.vehicle_type }}
                                                </span>
                                            </div>
                                            <div class="flex items-center justify-between border-b border-gray-50 pb-1.5">
                                                <span class="font-semibold text-gray-400 uppercase text-[9px] font-sans">Số ghế</span>
                                                <span class="font-bold text-gray-800 font-sans">
                                                    {{ detailTrip.vehicle.seat_count }} ghế
                                                </span>
                                            </div>
                                            <div v-if="detailTrip.vehicle.brand" class="flex items-center justify-between">
                                                <span class="font-semibold text-gray-400 uppercase text-[9px] font-sans">Hãng & Model</span>
                                                <span class="font-bold text-gray-800 font-sans">
                                                    {{ detailTrip.vehicle.brand }} {{ detailTrip.vehicle.model }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Operator Card -->
                                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                        <h3 class="mb-3 text-sm font-bold text-gray-900 font-sans">Nhà xe</h3>
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 font-bold border border-blue-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3.75h.008v.008h-.008V3.75Zm0 2.25h.008v.008h-.008V6ZM18.75 8.25h.008v.008h-.008V8.25Zm-3 11.25h.008v.008h-.008v-.008Zm0-2.25h.008v.008h-.008V17.25Zm0-2.25h.008v.008h-.008v-.008ZM12.75 17.25h.008v.008h-.008V17.25Zm0-2.25h.008v.008h-.008v-.008ZM12.75 12h.008v.008h-.008V12Zm-9 5.25h.008v.008h-.008V17.25Zm0-2.25h.008v.008h-.008v-.008ZM3.75 12h.008v.008h-.008V12Zm0-2.25h.008v.008h-.008V9.75Zm3 7.25h.008v.008h-.008V17.25Zm0-2.25h.008v.008h-.008v-.008ZM6.75 12h.008v.008h-.008V12Zm0-2.25h.008v.008h-.008V9.75ZM6.75 7.5h.008v.008h-.008V7.5Zm0-2.25h.008v.008h-.008V5.25Zm3 12h.008v.008h-.008V17.25Zm0-2.25h.008v.008h-.008v-.008ZM9.75 12h.008v.008h-.008V12Zm0-2.25h.008v.008h-.008V9.75Zm0-2.25h.008v.008h-.008V7.5Zm0-2.25h.008v.008h-.008V5.25Z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-xs text-gray-900 font-sans">
                                                    {{ detailTrip.operator?.company_name || 'N/A' }}
                                                </h4>
                                                <p class="text-[9px] text-gray-400 font-semibold font-sans">Đối tác vận tải</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
