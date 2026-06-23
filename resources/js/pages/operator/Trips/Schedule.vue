<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { operatorApi } from '@/api/operator.api';

interface TripBlock {
    id: string;
    tracking_code?: string;
    depart_at: string;
    arrive_at?: string;
    status: string;
    base_price?: number;
    route?: { origin_city: string; dest_city: string };
    vehicle?: { plate: string; type?: string } | null;
    driver?: { full_name: string; phone?: string } | null;
    booking_count?: number;
    passengers_count?: number;
    total_seats?: number;
    notes?: string | null;
}

// Nhãn + màu trạng thái hành khách trong manifest
const paxStatusMap: Record<string, { label: string; class: string }> = {
    confirmed: { label: 'Chờ lên xe', class: 'bg-slate-100 text-slate-600' },
    checked_in: { label: 'Đã lên xe', class: 'bg-green-100 text-green-700' },
    completed: { label: 'Hoàn thành', class: 'bg-blue-100 text-blue-700' },
    no_show: { label: 'Vắng mặt', class: 'bg-red-100 text-red-700' },
};

interface RouteOption {
    id: string;
    label: string;
}
interface VehicleOption {
    id: string;
    label: string;
    driver_id?: string | null;
}
interface DriverOption {
    id: string;
    label: string;
    rating: number;
    vehicle_id?: string | null;
}

const trips = ref<TripBlock[]>([]);
const routes = ref<RouteOption[]>([]);
const vehicles = ref<VehicleOption[]>([]);
const drivers = ref<DriverOption[]>([]);

const isLoading = ref(true);
const errorMsg = ref('');
const saving = ref(false);
const saveError = ref('');
const saveSuccess = ref(false);

// Single trip form
const form = ref({
    route_id: '',
    vehicle_id: '',
    driver_id: '',
    depart_at: '',
    price: 150000,
    note: '',
});

// Bulk create state
const bulkEnabled = ref(false);
const bulkTimes = ref<string[]>(['06:00']);
const allTimes = [
    '06:00',
    '07:00',
    '08:00',
    '09:00',
    '10:00',
    '11:00',
    '12:00',
    '13:00',
    '14:00',
    '15:00',
    '16:00',
    '17:00',
    '18:00',
    '19:00',
    '20:00',
];
const bulkFrom = ref('');
const bulkTo = ref('');
const bulkDays = ref<number[]>([1, 2, 3, 4, 5]); // Mon–Fri
const dayLabels = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];

const toggleTime = (t: string) => {
    const idx = bulkTimes.value.indexOf(t);
    if (idx >= 0) bulkTimes.value.splice(idx, 1);
    else bulkTimes.value.push(t);
};

const toggleDay = (d: number) => {
    const idx = bulkDays.value.indexOf(d);
    if (idx >= 0) bulkDays.value.splice(idx, 1);
    else bulkDays.value.push(d);
};

// Calendar view — tuần đang xem (điều hướng được sang tuần cũ/mới)
const today = new Date();

// Thứ Hai đầu tuần của một ngày bất kỳ
const startOfWeek = (d: Date) => {
    const x = new Date(d);
    x.setDate(x.getDate() - (x.getDay() === 0 ? 6 : x.getDay() - 1));
    x.setHours(0, 0, 0, 0);
    return x;
};

const weekStart = ref(startOfWeek(today));

const weekDays = computed(() =>
    Array.from({ length: 7 }, (_, i) => {
        const d = new Date(weekStart.value);
        d.setDate(weekStart.value.getDate() + i);
        return {
            date: d,
            label: d.toLocaleDateString('vi-VN', {
                weekday: 'short',
                day: 'numeric',
            }),
        };
    }),
);

const isCurrentWeek = computed(
    () => weekStart.value.getTime() === startOfWeek(today).getTime(),
);

const shiftWeek = (deltaWeeks: number) => {
    const d = new Date(weekStart.value);
    d.setDate(d.getDate() + deltaWeeks * 7);
    weekStart.value = d;
};
const prevWeek = () => shiftWeek(-1);
const nextWeek = () => shiftWeek(1);
const goCurrentWeek = () => {
    weekStart.value = startOfWeek(today);
};

// Giờ xuất phát sớm nhất cho phép = hiện tại + 30' (khớp luật đặt vé phía khách).
// Định dạng cho input datetime-local theo giờ địa phương.
const minDepart = computed(() => {
    const d = new Date(Date.now() + 30 * 60 * 1000);
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
});

const tripsForDay = (day: Date) =>
    trips.value.filter(
        (t) => new Date(t.depart_at).toDateString() === day.toDateString(),
    );

// Y-m-d theo giờ địa phương (không dùng toISOString để tránh lệch múi giờ)
const toYmd = (d: Date) =>
    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

const statusColor: Record<string, string> = {
    scheduled: 'bg-amber-100 text-amber-800 border border-amber-200',
    boarding: 'bg-blue-100 text-blue-800 border border-blue-200',
    in_progress: 'bg-green-100 text-green-800 border border-green-200',
    completed: 'bg-slate-200 text-slate-600 border border-slate-300',
    cancelled: 'bg-red-50 text-red-400 border border-red-200 line-through',
};

const statusLabel: Record<string, string> = {
    scheduled: 'Đã lên lịch',
    boarding: 'Đang đón khách',
    in_progress: 'Đang chạy',
    completed: 'Hoàn thành',
    cancelled: 'Đã huỷ',
};

// ─── Chi tiết chuyến (modal khi nhấp vào lịch) ───────────────────────────
const selectedTrip = ref<TripBlock | null>(null);
const cancelReason = ref('');
const cancelLoading = ref(false);
const showCancel = ref(false);

const openTrip = (trip: TripBlock) => {
    selectedTrip.value = trip;
    showCancel.value = false;
    cancelReason.value = '';
};
const closeTrip = () => {
    selectedTrip.value = null;
};

// ─── Chuyến quá giờ (chưa chạy nhưng đã qua giờ khởi hành) ────────────────
const isOverdue = (trip: TripBlock) =>
    ['scheduled', 'boarding'].includes(trip.status) &&
    new Date(trip.depart_at).getTime() < Date.now();

const overdueTrips = computed(() => trips.value.filter(isOverdue));

const completeLoading = ref(false);
const confirmCompleteTrip = async () => {
    if (!selectedTrip.value) return;
    completeLoading.value = true;
    const { error } = await operatorApi.completeTrip(selectedTrip.value.id);
    completeLoading.value = false;
    if (error) {
        saveError.value =
            typeof error === 'string' ? error : 'Không thể xác nhận hoàn tất';
        return;
    }
    closeTrip();
    load();
};

const fmtMoney = (v?: number) =>
    v != null ? new Intl.NumberFormat('vi-VN').format(v) + 'đ' : '—';
const fmtDateTime = (s?: string) =>
    s
        ? new Date(s).toLocaleString('vi-VN', {
              dateStyle: 'short',
              timeStyle: 'short',
          })
        : '—';
const fmtTime = (s?: string) =>
    s
        ? new Date(s).toLocaleTimeString('vi-VN', {
              hour: '2-digit',
              minute: '2-digit',
          })
        : '—';

const confirmCancelTrip = async () => {
    if (!selectedTrip.value || !cancelReason.value.trim()) return;
    cancelLoading.value = true;
    const { error } = await operatorApi.cancelTrip(
        selectedTrip.value.id,
        cancelReason.value.trim(),
    );
    cancelLoading.value = false;
    if (error) {
        saveError.value = error;
        return;
    }
    closeTrip();
    load();
};

// ─── Popup danh sách khách ───────────────────────────────────────────────
const showPassengers = ref(false);
const passengers = ref<any[]>([]);
const paxLoading = ref(false);
const paxError = ref('');

const openPassengers = async () => {
    if (!selectedTrip.value) return;
    showPassengers.value = true;
    paxLoading.value = true;
    paxError.value = '';
    passengers.value = [];
    const { data, error } = await operatorApi.getTripManifest(
        selectedTrip.value.id,
    );
    paxLoading.value = false;
    if (error) {
        paxError.value =
            typeof error === 'string'
                ? error
                : 'Không tải được danh sách khách';
        return;
    }
    passengers.value = (data as any)?.passengers ?? [];
};
const closePassengers = () => {
    showPassengers.value = false;
};

const load = async () => {
    isLoading.value = true;
    errorMsg.value = '';

    const [tripsRes, routesRes, vehiclesRes, driversRes] = await Promise.all([
        operatorApi.getTrips({
            date_from: toYmd(weekDays.value[0].date),
            date_to: toYmd(weekDays.value[6].date),
        }),
        operatorApi.getRoutes(),
        operatorApi.getVehicles(),
        operatorApi.getDrivers(),
    ]);

    isLoading.value = false;

    if (tripsRes.error) {
        errorMsg.value = 'Không thể tải dữ liệu chuyến';
        return;
    }

    trips.value = (tripsRes.data as TripBlock[]) ?? [];
    routes.value = ((routesRes.data as any[]) ?? []).map((r: any) => ({
        id: r.id,
        label: `${r.origin_city} → ${r.dest_city}`,
    }));
    vehicles.value = ((vehiclesRes.data as any[]) ?? []).map((v: any) => ({
        id: v.id,
        label: `${v.plate_number} — ${v.vehicle_type}`,
        driver_id: v.current_driver_id ?? null,
    }));
    // Chỉ tài xế đã được admin duyệt (verified) mới được xếp vào chuyến
    drivers.value = ((driversRes.data as any[]) ?? [])
        .filter((d: any) => d.status === 'verified')
        .map((d: any) => ({
            id: d.id,
            label: d.full_name ?? '',
            rating: d.rating_avg,
            vehicle_id: d.current_vehicle_id ?? null,
        }));
};

// Option 3 hybrid: tự điền chéo xe ↔ tài xế mặc định (vẫn cho phép đổi)
watch(
    () => form.value.driver_id,
    (driverId) => {
        const driver = drivers.value.find((d) => d.id === driverId);
        if (driver?.vehicle_id && driver.vehicle_id !== form.value.vehicle_id) {
            form.value.vehicle_id = driver.vehicle_id;
        }
    },
);
watch(
    () => form.value.vehicle_id,
    (vehicleId) => {
        const vehicle = vehicles.value.find((v) => v.id === vehicleId);
        if (vehicle?.driver_id && vehicle.driver_id !== form.value.driver_id) {
            form.value.driver_id = vehicle.driver_id;
        }
    },
);

const createTrip = async () => {
    if (
        !form.value.route_id ||
        !form.value.vehicle_id ||
        !form.value.driver_id ||
        !form.value.depart_at
    ) {
        saveError.value = 'Vui lòng điền đầy đủ thông tin bắt buộc';
        return;
    }
    saving.value = true;
    saveError.value = '';
    saveSuccess.value = false;

    const { error } = await operatorApi.createTrip(form.value);
    saving.value = false;

    if (error) {
        saveError.value = error;
        return;
    }

    saveSuccess.value = true;
    form.value.depart_at = '';
    form.value.note = '';
    await load();

    setTimeout(() => (saveSuccess.value = false), 3000);
};

const createBulk = async () => {
    if (
        !form.value.route_id ||
        !form.value.vehicle_id ||
        !form.value.driver_id ||
        !bulkFrom.value ||
        !bulkTo.value ||
        bulkTimes.value.length === 0
    ) {
        saveError.value =
            'Vui lòng chọn đầy đủ tuyến, xe, tài xế, khoảng ngày và ít nhất 1 giờ';
        return;
    }

    const trips: any[] = [];
    const from = new Date(bulkFrom.value);
    const to = new Date(bulkTo.value);
    for (let d = new Date(from); d <= to; d.setDate(d.getDate() + 1)) {
        if (!bulkDays.value.includes(d.getDay())) continue;
        for (const t of bulkTimes.value) {
            const dt = new Date(d);
            const [hh, mm] = t.split(':');
            dt.setHours(+hh, +mm, 0, 0);
            trips.push({
                route_id: form.value.route_id,
                vehicle_id: form.value.vehicle_id,
                driver_id: form.value.driver_id,
                depart_at: dt.toISOString(),
                base_price: form.value.price,
            });
        }
    }

    saving.value = true;
    saveError.value = '';
    const { error } = await operatorApi.bulkCreateTrips(trips);
    saving.value = false;
    if (error) {
        saveError.value = error;
        return;
    }

    saveSuccess.value = true;
    await load();
    setTimeout(() => (saveSuccess.value = false), 3000);
};

// Đổi tuần → tải lại chuyến của tuần đó
watch(weekStart, () => load());

onMounted(() => load());
</script>

<template>
    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-800">Lịch chạy</h1>
                <p class="mt-0.5 text-sm text-slate-500">
                    Tạo và quản lý chuyến đi
                </p>
            </div>
        </div>

        <!-- Error loading -->
        <div
            v-if="errorMsg"
            class="mb-4 flex items-center justify-between rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
        >
            {{ errorMsg }}
            <button class="underline" @click="load">Thử lại</button>
        </div>

        <!-- Cảnh báo chuyến quá giờ cần xử lý -->
        <div
            v-if="overdueTrips.length > 0"
            class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4"
        >
            <div class="flex items-start gap-3">
                <svg
                    class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z"
                    />
                </svg>
                <div class="flex-1 text-sm">
                    <p class="font-semibold text-red-800">
                        {{ overdueTrips.length }} chuyến đã quá giờ khởi hành
                        cần xử lý
                    </p>
                    <p class="mt-0.5 text-red-700">
                        Hãy mở từng chuyến để
                        <strong>Xác nhận đã chạy xong</strong> (nếu thực tế đã
                        chạy) hoặc <strong>Hủy chuyến</strong>. Nếu không xử lý,
                        hệ thống sẽ tự hủy &amp; hoàn tiền cho khách sau 2 giờ.
                    </p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <button
                            v-for="t in overdueTrips"
                            :key="t.id"
                            type="button"
                            @click="openTrip(t)"
                            class="rounded-lg border border-red-200 bg-white px-2.5 py-1 text-xs font-medium text-red-700 transition-colors hover:bg-red-100"
                        >
                            {{ fmtDateTime(t.depart_at) }} ·
                            {{ t.route?.origin_city }}→{{ t.route?.dest_city }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-6">
            <!-- LEFT: Calendar -->
            <div
                class="min-w-0 flex-1 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="flex items-center justify-between gap-3 border-b border-slate-100 px-6 py-4"
                >
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="prevWeek"
                            title="Tuần trước"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition-colors hover:bg-slate-50"
                        >
                            ‹
                        </button>
                        <span
                            class="min-w-[180px] text-center text-sm font-medium text-slate-700"
                        >
                            {{ weekDays[0].date.toLocaleDateString('vi-VN') }} –
                            {{ weekDays[6].date.toLocaleDateString('vi-VN') }}
                        </span>
                        <button
                            type="button"
                            @click="nextWeek"
                            title="Tuần sau"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition-colors hover:bg-slate-50"
                        >
                            ›
                        </button>
                    </div>
                    <button
                        v-if="!isCurrentWeek"
                        type="button"
                        @click="goCurrentWeek"
                        class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100"
                    >
                        Về tuần này
                    </button>
                    <span v-else class="text-xs text-slate-400"
                        >Tuần hiện tại</span
                    >
                </div>

                <div v-if="isLoading" class="space-y-2 p-4">
                    <div
                        v-for="i in 5"
                        :key="i"
                        class="h-12 animate-pulse rounded bg-slate-100"
                    />
                </div>

                <div v-else class="grid grid-cols-7 divide-x divide-slate-100">
                    <div
                        v-for="day in weekDays"
                        :key="day.label"
                        class="min-h-[300px]"
                    >
                        <div
                            class="border-b border-slate-100 px-2 py-2 text-center"
                            :class="
                                day.date.toDateString() === today.toDateString()
                                    ? 'bg-amber-50'
                                    : ''
                            "
                        >
                            <p class="text-xs font-medium text-slate-500">
                                {{ day.label }}
                            </p>
                        </div>
                        <div
                            class="max-h-[340px] space-y-1 overflow-y-auto p-1"
                        >
                            <button
                                v-for="trip in tripsForDay(day.date)"
                                :key="trip.id"
                                type="button"
                                @click="openTrip(trip)"
                                class="w-full cursor-pointer rounded-md px-1.5 py-1 text-left transition hover:ring-2 hover:ring-amber-300"
                                :class="
                                    isOverdue(trip)
                                        ? 'border border-red-300 bg-red-100 text-red-800 ring-1 ring-red-300'
                                        : (statusColor[trip.status] ??
                                          'bg-slate-100 text-slate-600')
                                "
                            >
                                <span
                                    class="block text-xs leading-tight font-bold"
                                    >{{ fmtTime(trip.depart_at) }}</span
                                >
                                <span
                                    v-if="isOverdue(trip)"
                                    class="block text-[10px] leading-tight font-semibold"
                                    >⏰ Quá giờ</span
                                >
                                <span
                                    class="block text-[10px] leading-tight opacity-80"
                                >
                                    👤 {{ trip.passengers_count ?? 0 }}/{{
                                        trip.total_seats ?? '—'
                                    }}
                                </span>
                            </button>
                            <!-- Empty day placeholder -->
                            <p
                                v-if="tripsForDay(day.date).length === 0"
                                class="py-4 text-center text-xs text-slate-300"
                            >
                                —
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Form panel -->
            <div class="w-80 flex-shrink-0 space-y-4">
                <!-- Success alert -->
                <div
                    v-if="saveSuccess"
                    class="flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                >
                    <svg
                        class="h-4 w-4 flex-shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    Tạo chuyến thành công!
                </div>

                <!-- Error -->
                <div
                    v-if="saveError"
                    class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                >
                    {{ saveError }}
                </div>

                <!-- Form card -->
                <div
                    class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <h2 class="font-semibold text-slate-800">Tạo chuyến mới</h2>

                    <!-- Route -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-slate-600"
                            >Tuyến đường *</label
                        >
                        <select
                            v-model="form.route_id"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                        >
                            <option value="">Chọn tuyến...</option>
                            <option
                                v-for="r in routes"
                                :key="r.id"
                                :value="r.id"
                            >
                                {{ r.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Vehicle -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-slate-600"
                            >Xe *</label
                        >
                        <select
                            v-model="form.vehicle_id"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                        >
                            <option value="">Chọn xe...</option>
                            <option
                                v-for="v in vehicles"
                                :key="v.id"
                                :value="v.id"
                            >
                                {{ v.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Driver -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-slate-600"
                            >Tài xế *</label
                        >
                        <select
                            v-model="form.driver_id"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                        >
                            <option value="">Chọn tài xế...</option>
                            <option
                                v-for="d in drivers"
                                :key="d.id"
                                :value="d.id"
                            >
                                {{ d.label }} ⭐{{ d.rating }}
                            </option>
                        </select>
                    </div>

                    <!-- Depart datetime -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-slate-600"
                            >Ngày & Giờ khởi hành *</label
                        >
                        <input
                            v-model="form.depart_at"
                            type="datetime-local"
                            :min="minDepart"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                        />
                        <p class="mt-1 text-[11px] text-slate-400">
                            Phải cách hiện tại ít nhất 30 phút để khách kịp đặt
                            vé
                        </p>
                    </div>

                    <!-- Price -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-slate-600"
                            >Giá vé (đ)</label
                        >
                        <input
                            v-model.number="form.price"
                            type="number"
                            step="10000"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                        />
                    </div>

                    <!-- Notes -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-slate-600"
                            >Ghi chú</label
                        >
                        <textarea
                            v-model="form.note"
                            rows="2"
                            placeholder="Ghi chú thêm..."
                            class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                        />
                    </div>

                    <button
                        :disabled="saving"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-amber-500 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-amber-600 disabled:bg-amber-300"
                        @click="createTrip"
                    >
                        <svg
                            v-if="saving"
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
                        {{ saving ? 'Đang tạo...' : 'Tạo chuyến' }}
                    </button>
                </div>

                <!-- Bulk create -->
                <div
                    class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <button
                        class="flex w-full items-center justify-between text-sm font-semibold text-slate-700"
                        @click="bulkEnabled = !bulkEnabled"
                    >
                        <span>Tạo lịch hàng loạt</span>
                        <svg
                            :class="bulkEnabled ? 'rotate-180' : ''"
                            class="h-4 w-4 text-slate-400 transition-transform"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </button>

                    <div
                        v-if="bulkEnabled"
                        class="space-y-3 border-t border-slate-100 pt-1"
                    >
                        <!-- Time slots -->
                        <div>
                            <p
                                class="mb-2 text-xs font-semibold text-slate-600"
                            >
                                Chọn giờ xuất phát
                            </p>
                            <div class="grid grid-cols-5 gap-1.5">
                                <button
                                    v-for="t in allTimes"
                                    :key="t"
                                    :class="
                                        bulkTimes.includes(t)
                                            ? 'bg-amber-500 text-white'
                                            : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
                                    "
                                    class="rounded-lg py-1.5 text-xs font-medium transition-colors"
                                    @click="toggleTime(t)"
                                >
                                    {{ t }}
                                </button>
                            </div>
                        </div>

                        <!-- Date range -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label
                                    class="mb-1 block text-xs font-medium text-slate-600"
                                    >Từ ngày</label
                                >
                                <input
                                    v-model="bulkFrom"
                                    type="date"
                                    class="w-full rounded-lg border border-slate-200 px-2 py-2 text-xs focus:border-amber-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-xs font-medium text-slate-600"
                                    >Đến ngày</label
                                >
                                <input
                                    v-model="bulkTo"
                                    type="date"
                                    class="w-full rounded-lg border border-slate-200 px-2 py-2 text-xs focus:border-amber-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        <!-- Day of week -->
                        <div>
                            <p
                                class="mb-2 text-xs font-semibold text-slate-600"
                            >
                                Các ngày trong tuần
                            </p>
                            <div class="flex gap-1">
                                <button
                                    v-for="(label, idx) in dayLabels"
                                    :key="idx"
                                    :class="
                                        bulkDays.includes(idx)
                                            ? 'bg-amber-500 text-white'
                                            : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
                                    "
                                    class="flex-1 rounded-lg py-1.5 text-xs font-medium transition-colors"
                                    @click="toggleDay(idx)"
                                >
                                    {{ label }}
                                </button>
                            </div>
                        </div>

                        <button
                            :disabled="saving"
                            class="w-full rounded-lg bg-slate-800 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-slate-900 disabled:bg-slate-400"
                            @click="createBulk"
                        >
                            {{ saving ? 'Đang tạo...' : 'Tạo hàng loạt' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Modal chi tiết chuyến ─────────────────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="selectedTrip"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div class="absolute inset-0 bg-black/40" @click="closeTrip" />
                <div
                    class="relative w-full max-w-md rounded-2xl bg-white shadow-xl"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-6 py-4"
                    >
                        <div>
                            <p class="font-mono font-bold text-slate-800">
                                {{ selectedTrip.tracking_code ?? '—' }}
                            </p>
                            <span
                                class="mt-1 inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="
                                    statusColor[selectedTrip.status] ??
                                    'bg-slate-100 text-slate-600'
                                "
                            >
                                {{
                                    statusLabel[selectedTrip.status] ??
                                    selectedTrip.status
                                }}
                            </span>
                        </div>
                        <button
                            @click="closeTrip"
                            class="text-2xl leading-none text-slate-400 hover:text-slate-600"
                        >
                            &times;
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="space-y-3 px-6 py-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Tuyến</span>
                            <span class="font-medium text-slate-800"
                                >{{ selectedTrip.route?.origin_city }} →
                                {{ selectedTrip.route?.dest_city }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Khởi hành → Đến</span>
                            <span class="font-medium text-slate-800"
                                >{{ fmtDateTime(selectedTrip.depart_at) }} →
                                {{ fmtTime(selectedTrip.arrive_at) }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Tài xế</span>
                            <span class="font-medium text-slate-800">{{
                                selectedTrip.driver?.full_name ?? '—'
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Xe</span>
                            <span
                                class="font-mono font-medium text-slate-800"
                                >{{ selectedTrip.vehicle?.plate ?? '—' }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Khách / Chỗ</span>
                            <span class="font-medium text-slate-800"
                                >{{ selectedTrip.passengers_count ?? 0 }}/{{
                                    selectedTrip.total_seats ?? '—'
                                }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Giá vé</span>
                            <span class="font-medium text-slate-800">{{
                                fmtMoney(selectedTrip.base_price)
                            }}</span>
                        </div>
                        <div v-if="selectedTrip.notes" class="pt-1">
                            <span class="text-slate-500">Ghi chú: </span>
                            <span class="text-slate-700">{{
                                selectedTrip.notes
                            }}</span>
                        </div>
                    </div>

                    <!-- Cảnh báo chuyến quá giờ -->
                    <div
                        v-if="isOverdue(selectedTrip) && !showCancel"
                        class="px-6 pb-1"
                    >
                        <div
                            class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700"
                        >
                            ⏰ Chuyến này
                            <strong>đã quá giờ khởi hành</strong> mà chưa cập
                            nhật trạng thái. Nếu thực tế đã chạy, bấm
                            <strong>"Đã chạy xong"</strong> để chốt chuyến
                            (không hoàn tiền khách). Nếu nhà xe không chạy, bấm
                            <strong>"Hủy chuyến"</strong> để hoàn tiền. Hệ thống
                            sẽ tự hủy &amp; hoàn tiền nếu không xử lý trong 2
                            giờ.
                        </div>
                    </div>

                    <!-- Cancel reason (khi bấm Hủy) -->
                    <div v-if="showCancel" class="px-6 pb-2">
                        <label
                            class="mb-1 block text-xs font-medium text-slate-600"
                            >Lý do hủy chuyến</label
                        >
                        <input
                            v-model="cancelReason"
                            type="text"
                            placeholder="Nhập lý do..."
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                        />
                    </div>

                    <!-- Footer actions -->
                    <div
                        class="flex items-center gap-3 border-t border-slate-100 px-6 py-4"
                    >
                        <button
                            @click="openPassengers"
                            class="flex-1 rounded-lg bg-amber-500 px-4 py-2 text-center text-sm font-medium text-white transition-colors hover:bg-amber-600"
                        >
                            Danh sách khách
                        </button>
                        <template
                            v-if="
                                ['scheduled', 'boarding'].includes(
                                    selectedTrip.status,
                                )
                            "
                        >
                            <template v-if="!showCancel">
                                <button
                                    v-if="isOverdue(selectedTrip)"
                                    @click="confirmCompleteTrip"
                                    :disabled="completeLoading"
                                    class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700 disabled:opacity-50"
                                >
                                    {{
                                        completeLoading
                                            ? 'Đang lưu...'
                                            : 'Đã chạy xong'
                                    }}
                                </button>
                                <button
                                    @click="showCancel = true"
                                    class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50"
                                >
                                    Hủy chuyến
                                </button>
                            </template>
                            <button
                                v-else
                                @click="confirmCancelTrip"
                                :disabled="
                                    cancelLoading || !cancelReason.trim()
                                "
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700 disabled:opacity-50"
                            >
                                {{
                                    cancelLoading
                                        ? 'Đang hủy...'
                                        : 'Xác nhận hủy'
                                }}
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ─── Popup danh sách khách ─────────────────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="showPassengers"
                class="fixed inset-0 z-[60] flex items-center justify-center p-4"
            >
                <div
                    class="absolute inset-0 bg-black/50"
                    @click="closePassengers"
                />
                <div
                    class="relative flex max-h-[85vh] w-full max-w-2xl flex-col rounded-2xl bg-white shadow-xl"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-6 py-4"
                    >
                        <div>
                            <h3 class="font-semibold text-slate-800">
                                Danh sách khách
                            </h3>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ selectedTrip?.tracking_code }} ·
                                {{ selectedTrip?.route?.origin_city }} →
                                {{ selectedTrip?.route?.dest_city }} ·
                                {{ passengers.length }} khách
                            </p>
                        </div>
                        <button
                            @click="closePassengers"
                            class="text-2xl leading-none text-slate-400 hover:text-slate-600"
                        >
                            &times;
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto">
                        <div
                            v-if="paxLoading"
                            class="p-10 text-center text-sm text-slate-400"
                        >
                            Đang tải danh sách khách...
                        </div>
                        <div
                            v-else-if="paxError"
                            class="p-10 text-center text-sm text-red-500"
                        >
                            {{ paxError }}
                        </div>
                        <div
                            v-else-if="passengers.length === 0"
                            class="p-12 text-center text-sm text-slate-400"
                        >
                            Chưa có khách đặt vé cho chuyến này
                        </div>
                        <table v-else class="w-full text-sm">
                            <thead class="sticky top-0 bg-slate-50">
                                <tr>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-medium text-slate-500 uppercase"
                                    >
                                        #
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-medium text-slate-500 uppercase"
                                    >
                                        Khách
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-center text-xs font-medium text-slate-500 uppercase"
                                    >
                                        Ghế
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-medium text-slate-500 uppercase"
                                    >
                                        Điểm đón
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-center text-xs font-medium text-slate-500 uppercase"
                                    >
                                        Trạng thái
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(p, i) in passengers"
                                    :key="i"
                                    :class="
                                        p.status === 'no_show'
                                            ? 'bg-red-50'
                                            : p.status === 'checked_in'
                                              ? 'bg-green-50/40'
                                              : ''
                                    "
                                >
                                    <td class="px-4 py-2.5 text-slate-500">
                                        {{ i + 1 }}
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <p class="font-medium text-slate-800">
                                            {{ p.contact_name }}
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            {{ p.contact_phone }} ·
                                            {{ p.booking_code }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span
                                            class="rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800"
                                            >{{ p.seat_code ?? '—' }}</span
                                        >
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-700">
                                        {{ p.pickup_stop ?? '—' }}
                                        <span
                                            v-if="p.pickup_address"
                                            class="block max-w-[180px] truncate text-xs text-slate-400"
                                            >{{ p.pickup_address }}</span
                                        >
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span
                                            :class="[
                                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                                paxStatusMap[p.status]?.class ??
                                                    'bg-slate-100 text-slate-600',
                                            ]"
                                        >
                                            {{
                                                paxStatusMap[p.status]?.label ??
                                                'Chờ'
                                            }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
