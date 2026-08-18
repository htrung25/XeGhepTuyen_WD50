<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { customerApi } from '@/api/customer.api';
import { formatRouteLabel } from '@/lib/route-label';
import { VEHICLE_SEAT_ROWS } from '@/lib/vehicle-seat-layout';
import { useCustomerAuthStore } from '@/stores/customer.auth.store';
import { useCustomerStore } from '@/stores/customer.store';
import type { SeatInfo } from '@/stores/customer.store';
import DriverSeat from './DriverSeat.vue';
import SeatButton from './SeatButton.vue';

const route = useRoute();
const router = useRouter();
const store = useCustomerStore();
const auth = useCustomerAuthStore();

const tripId = route.params.id as string;
const seats = ref<SeatInfo[]>([]);
const isLoading = ref(true);
const errorMsg = ref('');
const selected = ref<string[]>([]);
const lockLoading = ref(false);
const tripInfo = ref<any>(null);

const maxSeats = computed(() => store.searchParams.passengers || 1);

function toggleSeat(s: SeatInfo) {
    if (s.status !== 'available') return;
    const idx = selected.value.indexOf(s.seat_code);
    if (idx >= 0) {
        selected.value.splice(idx, 1);
    } else if (selected.value.length < maxSeats.value) {
        selected.value.push(s.seat_code);
    }
}

function groupSeatsByPrefix(seatList: SeatInfo[]): SeatInfo[][] {
    const rowsByPrefix = new Map<string, SeatInfo[]>();
    for (const seat of seatList) {
        const rowKey = seat.seat_code.replace(/\d+$/, '');
        if (!rowsByPrefix.has(rowKey)) rowsByPrefix.set(rowKey, []);
        rowsByPrefix.get(rowKey)!.push(seat);
    }
    const seatNumber = (code: string) =>
        parseInt(code.match(/\d+$/)?.[0] ?? '0', 10);
    for (const row of rowsByPrefix.values()) {
        row.sort((a, b) => seatNumber(a.seat_code) - seatNumber(b.seat_code));
    }
    return Array.from(rowsByPrefix.values());
}

// Dùng đúng ma trận mã ghế của từng vehicle_type. Nếu là chuyến cũ có bộ mã
// khác quy ước hiện tại, giữ fallback theo tiền tố để không làm ẩn ghế đang bán.
const seatGrid = computed(() => {
    const seatList = seats.value.filter(
        (s) => s.status !== 'driver' && s.status !== 'disabled',
    );
    const vehicleType = tripInfo.value?.vehicle?.vehicle_type ?? '';
    const expectedRows = VEHICLE_SEAT_ROWS[vehicleType];

    if (expectedRows) {
        const seatsByCode = new Map(
            seatList.map((seat) => [seat.seat_code, seat]),
        );
        const mappedRows = expectedRows.map((codes) =>
            codes
                .map((code) => seatsByCode.get(code))
                .filter((seat): seat is SeatInfo => Boolean(seat)),
        );
        const mappedSeatCount = mappedRows.reduce(
            (total, row) => total + row.length,
            0,
        );

        if (
            mappedSeatCount === seatList.length &&
            mappedRows.every((row) => row.length > 0)
        ) {
            return mappedRows;
        }
    }

    return groupSeatsByPrefix(seatList);
});

// ─── Sơ đồ ghế theo MẶT CẮT NGANG THẬT của từng loại xe ─────────────────────
// Khoang xe là MỘT lưới CSS: mỗi cột ứng với đúng một vị trí ghế vật lý xuyên
// suốt mọi hàng (nhờ vậy ghế các hàng luôn thẳng cột với nhau). Mỗi hàng khai
// báo RÕ ghế thứ j nằm ở cột nào, nên khớp 1-1 với sơ đồ nhà sản xuất — cột bị
// bỏ trống chính là lối đi. Số ghế mỗi hàng khớp TripService::getSeatTemplate
// (backend/app/Services/TripService.php).
const SEAT_W = 48; // px — bề rộng 1 ghế (khớp w-12 của SeatButton)
const AISLE_W = 22; // px — bề rộng lối đi hẹp giữa 2 cụm ghế (xe 16 chỗ)

/** Vị trí cột cho từng ghế trong hàng, hoặc 'span' = băng ghế trải hết bề rộng. */
type RowPlacement = number[] | 'span';

interface VehicleLayout {
    /** Bề rộng từng cột của khoang xe (px) */
    colWidths: number[];
    /** Cột đặt ghế lái (0-indexed) */
    driverCol: number;
    /** Có vách ngăn giữa khoang lái và khoang khách không */
    partition: boolean;
    rows: RowPlacement[];
}

const VEHICLE_LAYOUTS: Record<string, VehicleLayout> = {
    // Sedan 4 chỗ: tài xế + 1 khách phía trước (cách nhau bởi bệ tì tay giữa),
    // băng ghế sau 3 chỗ trải hết bề rộng.
    sedan_4: {
        colWidths: [SEAT_W, SEAT_W, SEAT_W],
        driverCol: 0,
        partition: false,
        rows: [[2], [0, 1, 2]],
    },
    // MPV 7 chỗ: tài xế + 1 khách phía trước, 2 băng ghế sau mỗi băng 3 chỗ.
    mpv_7: {
        colWidths: [SEAT_W, SEAT_W, SEAT_W],
        driverCol: 0,
        partition: false,
        rows: [[2], [0, 1, 2], [0, 1, 2]],
    },
    // Van 9 chỗ theo ảnh nghiệp vụ: A1-A2 cạnh tài xế; B1-B2 và B3-B4 là hai
    // hàng đôi có lối đi giữa; C1-C3 là hàng cuối trải hết bề rộng.
    van_9: {
        colWidths: [SEAT_W, SEAT_W, SEAT_W],
        driverCol: 0,
        partition: true,
        rows: [
            [1, 2],
            [0, 2],
            [0, 2],
            [0, 1, 2],
        ],
    },
    // Limousine 12 chỗ: 2 ghế đầu cạnh tài xế và 5 hàng ghế thương gia đôi,
    // mỗi hàng chừa lối đi ở giữa.
    limousine_12: {
        colWidths: [SEAT_W, SEAT_W, SEAT_W],
        driverCol: 0,
        partition: true,
        rows: [
            [1, 2],
            [0, 2],
            [0, 2],
            [0, 2],
            [0, 2],
            [0, 2],
        ],
    },
    // Minibus 16 chỗ: tài xế + 1 khách hàng đầu; 4 hàng ghế 1+2 với lối đi hẹp
    // giữa; băng ghế cuối 3 chỗ trải hết bề rộng (vắt qua cả lối đi).
    minibus_16: {
        colWidths: [SEAT_W, AISLE_W, SEAT_W, SEAT_W],
        driverCol: 0,
        partition: true,
        rows: [[2], [0, 2, 3], [0, 2, 3], [0, 2, 3], [0, 2, 3], 'span'],
    },
};

const layout = computed(
    () => VEHICLE_LAYOUTS[tripInfo.value?.vehicle?.vehicle_type ?? ''],
);

// Chỉ dùng sơ đồ thật khi số hàng VÀ số ghế mỗi hàng khớp đúng dữ liệu backend
// trả về — gặp loại xe lạ / dữ liệu cũ thì rơi về fallback thay vì vỡ bố cục.
const layoutMatches = computed(() => {
    const l = layout.value;
    const rows = seatGrid.value;
    if (!l || l.rows.length !== rows.length) return false;
    return l.rows.every((placement, i) =>
        placement === 'span'
            ? rows[i].length > 0 && rows[i].length <= l.colWidths.length
            : placement.length === rows[i].length,
    );
});

const templateColumns = computed(() =>
    (layout.value?.colWidths ?? []).map((w) => `${w}px`).join(' '),
);

interface CabinRow {
    /** Ghế đặt vào cột cụ thể (gridColumn 1-indexed) */
    placed: { seat: SeatInfo; gridColumn: number }[];
    /** Băng ghế trải hết bề rộng khoang, dàn đều 2 mép */
    spanned: SeatInfo[] | null;
    /** Dòng trong lưới: hàng 1 = vô lăng, 2 = hàng ghế đầu, 3 = vách ngăn */
    gridRow: number;
}

const cabinRows = computed<CabinRow[]>(() => {
    if (!layoutMatches.value) return [];
    const l = layout.value!;

    return seatGrid.value.map((rowSeats, i) => {
        const gridRow = i === 0 ? 2 : i + 3;
        const placement = l.rows[i];
        if (placement === 'span') {
            return { placed: [], spanned: rowSeats, gridRow };
        }
        return {
            placed: rowSeats.map((seat, j) => ({
                seat,
                gridColumn: placement[j] + 1,
            })),
            spanned: null,
            gridRow,
        };
    });
});

// Fallback: loại xe chưa khai báo sơ đồ — xếp mỗi hàng thành một dòng ghế đơn
// giản, vẫn dùng đúng hình ghế/khung xe.
const fallbackRows = computed(() => seatGrid.value);

const selectedSeats = computed(() =>
    seats.value.filter((s) => selected.value.includes(s.seat_code)),
);

const totalPrice = computed(() =>
    selectedSeats.value.reduce((sum, s) => sum + s.price, 0),
);

async function proceedToCheckout() {
    if (!auth.isAuthenticated) {
        router.push({ path: '/login', query: { redirect: route.fullPath } });
        return;
    }
    if (selected.value.length === 0) return;

    lockLoading.value = true;
    const { error } = await customerApi.lockSeats({
        trip_id: tripId,
        seat_ids: selectedSeats.value.map((s) => s.id),
    });
    lockLoading.value = false;
    if (error) {
        errorMsg.value = 'Không thể giữ ghế. Vui lòng thử lại.';
        return;
    }

    store.bookingDraft.seats = selectedSeats.value;
    store.bookingDraft.seat_codes = selected.value;
    router.push('/booking/checkout');
}

onMounted(async () => {
    // Đảm bảo draft luôn có trip_id (hỗ trợ vào thẳng link /trips/:id/seats,
    // không qua trang kết quả tìm kiếm) — nếu thiếu, Checkout sẽ đá về /home.
    store.bookingDraft.trip_id = tripId;

    isLoading.value = true;
    const [seatsRes, tripRes] = await Promise.all([
        customerApi.getTripSeats(tripId),
        customerApi.getPublicTrip(tripId),
    ]);
    isLoading.value = false;

    if (seatsRes.error) {
        errorMsg.value = 'Không thể tải sơ đồ ghế.';
        return;
    }
    seats.value = seatsRes.data ?? [];
    tripInfo.value = tripRes.data ?? null;

    if (store.bookingDraft.trip_id === tripId) {
        const draftSeatCodes = store.bookingDraft.seat_codes ?? [];
        selected.value = draftSeatCodes.filter((code) => {
            const seat = seats.value.find((s) => s.seat_code === code);
            return seat && seat.status === 'available';
        });
    }
});
</script>

<template>
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 sm:py-8">
        <!-- Breadcrumb -->
        <nav class="mb-6 flex items-center gap-2 text-sm text-gray-500">
            <router-link
                to="/home"
                class="transition-colors hover:text-blue-600"
                >Trang chủ</router-link
            >
            <span>›</span>
            <router-link
                to="/search"
                class="transition-colors hover:text-blue-600"
                >Kết quả</router-link
            >
            <span>›</span>
            <span class="font-medium text-gray-900">Chọn ghế</span>
        </nav>

        <!-- Loading -->
        <div v-if="isLoading" class="flex items-center justify-center py-24">
            <div class="flex flex-col items-center gap-3 text-gray-500">
                <div
                    class="h-8 w-8 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"
                />
                <span class="text-sm">Đang tải sơ đồ ghế...</span>
            </div>
        </div>

        <!-- Error -->
        <div
            v-else-if="errorMsg && seats.length === 0"
            class="rounded-xl border border-red-200 bg-red-50 p-6 text-center text-red-700"
        >
            <p class="mb-3 font-medium">{{ errorMsg }}</p>
            <button
                @click="$router.back()"
                class="rounded-lg border border-red-300 px-5 py-2 text-sm font-medium transition-colors hover:bg-red-100"
            >
                ← Quay lại
            </button>
        </div>

        <div
            v-else
            class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_340px] lg:gap-8"
        >
            <!-- ─── LEFT: Trip info + Seat map ────────────── -->
            <div class="space-y-6">
                <!-- Trip info card -->
                <div
                    v-if="tripInfo"
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="mb-4 flex items-center gap-4">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-lg font-bold text-blue-700"
                        >
                            {{ tripInfo.driver?.full_name?.charAt(0) ?? 'T' }}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">
                                {{ tripInfo.driver?.full_name ?? 'Tài xế' }}
                            </p>
                            <div class="mt-0.5 flex items-center gap-1">
                                <svg
                                    class="h-3.5 w-3.5 fill-yellow-400 text-yellow-400"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                    />
                                </svg>
                                <span class="text-xs font-medium text-gray-600"
                                    >{{
                                        tripInfo.driver?.rating_avg?.toFixed(
                                            1,
                                        ) ?? '4.8'
                                    }}
                                    sao</span
                                >
                                <span class="text-xs text-gray-400">·</span>
                                <span class="text-xs text-gray-500">{{
                                    tripInfo.vehicle?.plate_number ??
                                    '30A-12345'
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700"
                            >📶 WiFi</span
                        >
                        <span
                            class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700"
                            >❄️ Điều hòa</span
                        >
                        <span
                            class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700"
                            >🔌 Cổng USB</span
                        >
                        <span
                            class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700"
                            >💧 Nước uống</span
                        >
                    </div>
                </div>

                <!-- Seat map -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <h3 class="mb-1 font-semibold text-gray-900">Sơ đồ ghế</h3>
                    <p class="mb-5 text-xs text-gray-500">
                        Chọn tối đa {{ maxSeats }} ghế. Click vào ghế trống để
                        chọn.
                    </p>

                    <!-- Loading skeleton -->
                    <div
                        v-if="isLoading"
                        class="flex flex-col items-center gap-3"
                    >
                        <div v-for="i in 4" :key="i" class="flex gap-3">
                            <div
                                class="h-10 w-14 animate-pulse rounded-lg bg-gray-200"
                            />
                            <div
                                class="h-10 w-14 animate-pulse rounded-lg bg-gray-200"
                            />
                        </div>
                    </div>

                    <!-- Car visual: khung xe nhìn từ trên xuống + mặt cắt ngang
                    thật theo từng loại xe (khớp sơ đồ nhà sản xuất) -->
                    <div v-else class="flex justify-center overflow-x-auto">
                        <div class="relative shrink-0 px-3 pb-1">
                            <!-- Gương chiếu hậu 2 bên, ngang tầm kính lái -->
                            <div
                                class="absolute top-[34px] left-0 h-3 w-3 rounded-l-full border-2 border-r-0 border-gray-300 bg-gray-200"
                            />
                            <div
                                class="absolute top-[34px] right-0 h-3 w-3 rounded-r-full border-2 border-l-0 border-gray-300 bg-gray-200"
                            />

                            <!-- Thân xe: mũi bo tròn ở đầu, đuôi bo nhẹ -->
                            <div
                                class="overflow-hidden rounded-t-[2.75rem] rounded-b-2xl border-2 border-gray-300 bg-white"
                            >
                                <!-- Đầu xe: ca-pô + kính chắn gió -->
                                <div class="relative h-10">
                                    <div
                                        class="mx-auto mt-3 h-1 w-10 rounded-full bg-gray-200"
                                    />
                                    <div
                                        class="absolute inset-x-4 bottom-0 h-4 rounded-t-2xl border-2 border-b-0 border-gray-200 bg-gray-100"
                                    />
                                </div>

                                <!-- Khoang ghế -->
                                <div
                                    v-if="layoutMatches && layout"
                                    class="grid gap-x-1 gap-y-3 border-t-2 border-gray-200 bg-gray-50 px-4 pt-2 pb-4"
                                    :style="{
                                        gridTemplateColumns: templateColumns,
                                    }"
                                >
                                    <!-- Vô lăng, ngay phía trước ghế lái -->
                                    <div
                                        class="flex justify-center"
                                        :style="{
                                            gridColumn: layout.driverCol + 1,
                                            gridRow: 1,
                                        }"
                                    >
                                        <svg
                                            class="h-6 w-6 text-gray-500"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.75"
                                            viewBox="0 0 24 24"
                                        >
                                            <circle cx="12" cy="12" r="8.25" />
                                            <circle cx="12" cy="12" r="2" />
                                            <path
                                                stroke-linecap="round"
                                                d="M12 3.75v6.25M12 14v7.25M5.05 7.05l4.6 3.7M18.95 7.05l-4.6 3.7"
                                            />
                                        </svg>
                                    </div>

                                    <DriverSeat
                                        :style="{
                                            gridColumn: layout.driverCol + 1,
                                            gridRow: 2,
                                        }"
                                    />

                                    <!-- Vách ngăn khoang lái / khoang khách -->
                                    <div
                                        v-if="layout.partition"
                                        class="my-1 border-t-2 border-gray-200"
                                        :style="{
                                            gridColumn: '1 / -1',
                                            gridRow: 3,
                                        }"
                                    />

                                    <template
                                        v-for="(row, ri) in cabinRows"
                                        :key="ri"
                                    >
                                        <SeatButton
                                            v-for="p in row.placed"
                                            :key="p.seat.seat_code"
                                            :seat="p.seat"
                                            :selected="
                                                selected.includes(
                                                    p.seat.seat_code,
                                                )
                                            "
                                            :style="{
                                                gridColumn: p.gridColumn,
                                                gridRow: row.gridRow,
                                            }"
                                            @toggle="toggleSeat(p.seat)"
                                        />
                                        <div
                                            v-if="row.spanned"
                                            class="flex items-end justify-between"
                                            :style="{
                                                gridColumn: '1 / -1',
                                                gridRow: row.gridRow,
                                            }"
                                        >
                                            <SeatButton
                                                v-for="seat in row.spanned"
                                                :key="seat.seat_code"
                                                :seat="seat"
                                                :selected="
                                                    selected.includes(
                                                        seat.seat_code,
                                                    )
                                                "
                                                @toggle="toggleSeat(seat)"
                                            />
                                        </div>
                                    </template>
                                </div>

                                <!-- Fallback: loại xe chưa khai báo sơ đồ -->
                                <div
                                    v-else
                                    class="flex flex-col items-center gap-2 border-t-2 border-gray-200 bg-gray-50 px-4 pt-2 pb-4"
                                >
                                    <DriverSeat class="self-start" />
                                    <div
                                        v-for="(row, ri) in fallbackRows"
                                        :key="ri"
                                        class="flex items-end justify-center gap-2"
                                    >
                                        <SeatButton
                                            v-for="seat in row"
                                            :key="seat.seat_code"
                                            :seat="seat"
                                            :selected="
                                                selected.includes(
                                                    seat.seat_code,
                                                )
                                            "
                                            @toggle="toggleSeat(seat)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div
                        class="mt-6 flex flex-wrap items-center gap-4 border-t border-gray-100 pt-5"
                    >
                        <div
                            class="flex items-center gap-2 text-xs text-gray-600"
                        >
                            <div
                                class="h-5 w-6 rounded border-2 border-gray-300 bg-white"
                            />
                            Trống
                        </div>
                        <div
                            class="flex items-center gap-2 text-xs text-gray-600"
                        >
                            <div
                                class="h-5 w-6 rounded border-2 border-blue-600 bg-blue-600"
                            />
                            Đã chọn
                        </div>
                        <div
                            class="flex items-center gap-2 text-xs text-gray-600"
                        >
                            <div
                                class="h-5 w-6 rounded border-2 border-red-200 bg-red-100"
                            />
                            Đã đặt
                        </div>
                        <div
                            class="flex items-center gap-2 text-xs text-gray-600"
                        >
                            <div
                                class="h-5 w-6 rounded border-2 border-yellow-300 bg-yellow-100"
                            />
                            Đang giữ
                        </div>
                    </div>

                    <p class="mt-2 text-xs text-gray-400 italic">
                        Ghế màu vàng đang được người khác giữ tạm, sẽ tự giải
                        phóng sau vài phút nếu họ không thanh toán.
                    </p>
                </div>
            </div>

            <!-- ─── RIGHT: Order Summary ───────────────────── -->
            <div class="lg:sticky lg:top-20">
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <h3 class="mb-4 font-semibold text-gray-900">
                        Thông tin chuyến đi
                    </h3>

                    <div
                        v-if="tripInfo"
                        class="mb-4 space-y-3 border-b border-gray-100 pb-4"
                    >
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Tuyến</span>
                            <span class="font-medium text-gray-900">
                                {{ formatRouteLabel(tripInfo.route) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Ngày</span>
                            <span class="font-medium text-gray-900">
                                {{
                                    tripInfo.depart_at
                                        ? new Date(
                                              tripInfo.depart_at,
                                          ).toLocaleDateString('vi-VN', {
                                              weekday: 'short',
                                              day: '2-digit',
                                              month: '2-digit',
                                              year: 'numeric',
                                          })
                                        : '—'
                                }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Giờ</span>
                            <span class="font-medium text-gray-900">
                                {{
                                    tripInfo.depart_at
                                        ? new Date(
                                              tripInfo.depart_at,
                                          ).toLocaleTimeString('vi-VN', {
                                              hour: '2-digit',
                                              minute: '2-digit',
                                              hour12: false,
                                          })
                                        : '—'
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Selected seats -->
                    <div class="mb-4">
                        <p
                            class="mb-2 text-xs font-medium tracking-wide text-gray-500 uppercase"
                        >
                            Ghế đã chọn
                        </p>
                        <div
                            v-if="selected.length === 0"
                            class="py-2 text-sm text-gray-400 italic"
                        >
                            Chưa chọn ghế nào
                        </div>
                        <div v-else class="flex flex-wrap gap-2">
                            <span
                                v-for="code in selected"
                                :key="code"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-blue-100 px-3 py-1.5 text-sm font-semibold text-blue-800"
                            >
                                {{ code }}
                                <button
                                    @click="
                                        selected = selected.filter(
                                            (c) => c !== code,
                                        )
                                    "
                                    class="flex h-4 w-4 items-center justify-center rounded-full bg-blue-200 text-xs font-bold transition-colors hover:bg-blue-300"
                                >
                                    ×
                                </button>
                            </span>
                        </div>
                    </div>

                    <!-- Price summary -->
                    <div class="mb-4 rounded-xl bg-gray-50 p-4">
                        <div
                            class="mb-1 flex items-center justify-between text-sm"
                        >
                            <span class="text-gray-500">
                                {{
                                    tripInfo?.price
                                        ? new Intl.NumberFormat('vi-VN').format(
                                              tripInfo.price,
                                          ) + 'đ'
                                        : '—'
                                }}
                                × {{ selected.length }} ghế
                            </span>
                            <span class="font-semibold text-gray-900">
                                {{
                                    totalPrice > 0
                                        ? new Intl.NumberFormat('vi-VN').format(
                                              totalPrice,
                                          ) + 'đ'
                                        : '—'
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Error -->
                    <div
                        v-if="errorMsg"
                        class="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-600"
                    >
                        {{ errorMsg }}
                    </div>

                    <!-- CTA -->
                    <button
                        @click="proceedToCheckout"
                        :disabled="selected.length === 0 || lockLoading"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3.5 text-sm font-bold text-white transition-all hover:bg-blue-700 active:scale-[0.98] disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-400 disabled:active:scale-100"
                    >
                        <div
                            v-if="lockLoading"
                            class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                        />
                        <span>{{
                            lockLoading ? 'Đang giữ ghế...' : 'Tiếp tục đặt vé'
                        }}</span>
                        <span v-if="!lockLoading">→</span>
                    </button>

                    <p class="mt-3 text-center text-xs text-gray-400">
                        Ghế sẽ được giữ 10 phút trong lúc bạn điền thông tin đặt
                        vé
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
