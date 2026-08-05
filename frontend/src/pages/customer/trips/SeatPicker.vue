<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { customerApi } from '@/api/customer.api';
import { useCustomerAuthStore } from '@/stores/customer.auth.store';
import { useCustomerStore } from '@/stores/customer.store';
import type { SeatInfo } from '@/stores/customer.store';
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

// Nhóm ghế theo tiền tố chữ cái của seat_code (A1,A2 -> hàng "A"), đúng cấu
// trúc hàng thật do backend sinh (TripService::getSeatTemplate) — mỗi chữ cái
// ứng với ĐÚNG MỘT hàng ghế vật lý, sắp theo thứ tự số trong hàng để khớp
// đúng slot của layout bên dưới.
const seatGrid = computed(() => {
    const seatList = seats.value.filter((s) => s.status !== 'driver');
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
});

// Mặt cắt ngang THẬT của từng loại xe — khớp 1-1 với TripService::getSeatTemplate
// (backend/app/Services/TripService.php). Mỗi phần tử là 1 hàng ghế vật lý: `slots`
// liệt kê vị trí trái→phải trong hàng, số = chỉ số ghế trong hàng (đã sort ở seatGrid),
// 'aisle' = lối đi giữa xe. `front: true` = hàng đặt cạnh ghế tài xế (đầu xe).
type LayoutSlot = number | 'aisle';
interface LayoutRow {
    front?: boolean;
    slots: LayoutSlot[];
}
const VEHICLE_LAYOUTS: Record<string, LayoutRow[]> = {
    // Sedan 4 chỗ: tài xế + 1 khách hàng trước, băng ghế sau 3 chỗ
    sedan_4: [{ front: true, slots: [0] }, { slots: [0, 1, 2] }],
    // MPV 7 chỗ: tài xế + 1 khách hàng trước, 2 hàng băng ghế sau mỗi hàng 3 chỗ
    mpv_7: [
        { front: true, slots: [0] },
        { slots: [0, 1, 2] },
        { slots: [0, 1, 2] },
    ],
    // Limousine van 9 chỗ: hàng đầu tài xế + 2 khách hàng ngồi sát nhau hết bề
    // rộng xe (chưa có lối đi) — đúng bố trí limousine thật: lối đi CHỈ NẰM
    // MỘT BÊN (phía cửa lùa), 2 hàng giữa mỗi hàng 2 ghế dồn về phía đối diện
    // cửa, hàng cuối băng ghế 3 chỗ hết bề rộng xe (qua khỏi khu vực cửa)
    van_9: [
        { front: true, slots: [0, 1] },
        { slots: [0, 1, 'aisle'] },
        { slots: [0, 1, 'aisle'] },
        { slots: [0, 1, 2] },
    ],
    // Minibus 16 chỗ: tài xế + 1 khách hàng trước, 4 hàng ghế đơn/đôi có lối
    // đi giữa, hàng cuối băng ghế 3 chỗ sát đuôi xe
    minibus_16: [
        { front: true, slots: [0] },
        { slots: [0, 'aisle', 1, 2] },
        { slots: [0, 'aisle', 1, 2] },
        { slots: [0, 'aisle', 1, 2] },
        { slots: [0, 'aisle', 1, 2] },
        { slots: [0, 1, 2] },
    ],
};

// Fallback đơn giản (không dùng lưới cột) khi gặp loại xe lạ hoặc dữ liệu ghế
// không khớp layout đã khai báo — tránh vỡ UI thay vì cố ép vào lưới sai.
type RenderSlot = { type: 'seat'; seat: SeatInfo } | { type: 'aisle' };
interface FallbackRow {
    front: boolean;
    slots: RenderSlot[];
}

const layout = computed(
    () => VEHICLE_LAYOUTS[tripInfo.value?.vehicle?.vehicle_type ?? ''],
);

const layoutMatches = computed(() => {
    const rows = seatGrid.value;
    const l = layout.value;
    return !!(
        l &&
        l.length === rows.length &&
        l.every(
            (lr, ri) =>
                lr.slots.filter((s) => s !== 'aisle').length ===
                rows[ri].length,
        )
    );
});

const fallbackRows = computed<FallbackRow[]>(() =>
    seatGrid.value.map((row, ri) => ({
        front: ri === 0,
        slots: row.map((seat) => ({ type: 'seat' as const, seat })),
    })),
);

// ─── Sơ đồ dạng LƯỚI CSS thật — mỗi cột giữ ĐÚNG một vị trí ghế vật lý xuyên
// suốt mọi hàng (ghế hàng dưới thẳng cột với ghế hàng trên), khớp cách bố trí
// thật của xe khách (ảnh mặt cắt ngang xe limousine/minibus thực tế).
const SEAT_COL = 48; // px — khớp bề rộng nút ghế (w-12)
const AISLE_COL = 20; // px — bề rộng lối đi ở GIỮA 2 cụm ghế (kiểu minibus)
const WALKWAY_COL = 64; // px — bề rộng lối đi MỘT BÊN chạy dọc tới cửa lùa (kiểu limousine van), rộng hơn hẳn lối đi giữa vì đây là khoảng trống thay cho cả 1 cụm ghế
const DRIVER_COL = 48; // px — cột dành riêng cho ghế lái, rỗng ở các hàng khác
const GAP = 8; // px — khoảng cách giữa các cột lưới VÀ giữa các ghế trong 1 hàng không lối đi

interface GridSpec {
    leftCols: number;
    rightCols: number;
    hasAisle: boolean;
    totalCols: number;
    templateColumns: string;
}

function computeGridSpec(rows: LayoutRow[]): GridSpec {
    let leftCols = 0;
    let rightCols = 0;
    let hasAisle = false;
    let maxSlots = 0;
    for (const row of rows) {
        maxSlots = Math.max(maxSlots, row.slots.length);
        const aisleIdx = row.slots.indexOf('aisle');
        if (aisleIdx !== -1) {
            hasAisle = true;
            leftCols = Math.max(leftCols, aisleIdx);
            rightCols = Math.max(rightCols, row.slots.length - aisleIdx - 1);
        }
    }
    if (hasAisle) {
        // rightCols=0 nghĩa là lối đi chạy MỘT BÊN tới cửa lùa (không có ghế
        // phía bên kia) — cần rộng hơn hẳn lối đi giữa 2 cụm ghế đối xứng.
        const aisleWidth = rightCols === 0 ? WALKWAY_COL : AISLE_COL;
        return {
            leftCols,
            rightCols,
            hasAisle,
            totalCols: 1 + leftCols + 1 + rightCols,
            templateColumns: `${DRIVER_COL}px repeat(${leftCols}, ${SEAT_COL}px) ${aisleWidth}px repeat(${rightCols}, ${SEAT_COL}px)`,
        };
    }
    // Không xe nào trong bảng có lối đi (sedan/mpv) — chỉ cần 1 cột hành khách
    // đủ rộng cho hàng đông ghế nhất, ghế trong hàng tự dàn đều bằng flex.
    const passengerW = maxSlots * SEAT_COL + (maxSlots - 1) * GAP;
    return {
        leftCols: 0,
        rightCols: 0,
        hasAisle: false,
        totalCols: 2,
        templateColumns: `${DRIVER_COL}px minmax(${passengerW}px, auto)`,
    };
}

const gridSpec = computed<GridSpec | null>(() =>
    layout.value ? computeGridSpec(layout.value) : null,
);

interface GridCell {
    kind: 'seat' | 'group';
    seat?: SeatInfo;
    seats?: SeatInfo[];
    gridColumn: string;
}
interface GridRow {
    front: boolean;
    cells: GridCell[];
}

const gridRows = computed<GridRow[]>(() => {
    const spec = gridSpec.value;
    if (!spec || !layoutMatches.value) return [];

    return seatGrid.value.map((row, ri) => {
        const layoutRow = layout.value![ri];
        const aisleIdx = layoutRow.slots.indexOf('aisle');

        if (aisleIdx === -1) {
            const seatIdxs = layoutRow.slots as number[];
            // Hàng vừa khít cụm ghế bên trái (vd hàng ghế đầu cạnh tài xế) —
            // map THẲNG vào đúng các cột ghế trái, không tràn sang phần lối
            // đi phía dưới, để thẳng cột với B1/C1... của các hàng có lối đi.
            if (spec.leftCols > 0 && seatIdxs.length <= spec.leftCols) {
                return {
                    front: !!layoutRow.front,
                    cells: seatIdxs.map((seatIdx, i) => ({
                        kind: 'seat' as const,
                        seat: row[seatIdx],
                        gridColumn: `${2 + i} / ${3 + i}`,
                    })),
                };
            }
            // Hàng đông ghế hơn cụm ghế trái (vd băng ghế cuối) — tràn hết bề
            // rộng xe kể cả phần lối đi phía trên, ghế dàn đều 2 mép ngoài.
            return {
                front: !!layoutRow.front,
                cells: [
                    {
                        kind: 'group' as const,
                        seats: seatIdxs.map((i) => row[i]),
                        gridColumn: `2 / ${spec.totalCols + 1}`,
                    },
                ],
            };
        }

        const leftSlots = layoutRow.slots.slice(0, aisleIdx) as number[];
        const rightSlots = layoutRow.slots.slice(aisleIdx + 1) as number[];
        const leftStartCol = 2 + spec.leftCols - leftSlots.length; // áp sát lối đi
        const rightStartCol = 3 + spec.leftCols; // ngay sau lối đi

        const cells: GridCell[] = [
            ...leftSlots.map((seatIdx, i) => ({
                kind: 'seat' as const,
                seat: row[seatIdx],
                gridColumn: `${leftStartCol + i} / ${leftStartCol + i + 1}`,
            })),
            ...rightSlots.map((seatIdx, i) => ({
                kind: 'seat' as const,
                seat: row[seatIdx],
                gridColumn: `${rightStartCol + i} / ${rightStartCol + i + 1}`,
            })),
        ];

        return { front: !!layoutRow.front, cells };
    });
});

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

                    <!-- Car visual: khung xe + mặt cắt ngang thật theo từng loại xe -->
                    <div v-else class="flex justify-center overflow-x-auto">
                        <div class="relative shrink-0">
                            <!-- Gương chiếu hậu 2 bên -->
                            <div
                                class="absolute top-14 -left-2 h-3 w-4 rounded-full border-2 border-gray-300 bg-gray-100"
                            />
                            <div
                                class="absolute top-14 -right-2 h-3 w-4 rounded-full border-2 border-gray-300 bg-gray-100"
                            />

                            <!-- Khung thân xe -->
                            <div
                                class="rounded-[2rem] border-4 border-gray-200 bg-gray-50/60 px-5 pt-4 pb-6"
                            >
                                <!-- Kính chắn gió -->
                                <div
                                    class="mx-auto mb-2 h-3 w-20 rounded-t-full border-2 border-b-0 border-gray-300 bg-gray-100"
                                />

                                <div
                                    class="mb-3 flex flex-col items-center gap-1"
                                >
                                    <svg
                                        class="h-6 w-6 text-gray-300"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle cx="12" cy="12" r="8.25" />
                                        <circle cx="12" cy="12" r="2" />
                                        <path
                                            stroke-linecap="round"
                                            d="M12 3.75v6.25M12 14v7.25M5.05 7.05l4.6 3.7M18.95 7.05l-4.6 3.7M5.05 16.95l4.6-3.7M18.95 16.95l-4.6-3.7"
                                        />
                                    </svg>
                                    <span class="text-xs text-gray-400 italic"
                                        >Đầu xe</span
                                    >
                                </div>

                                <!-- Lưới ghế: mỗi cột giữ đúng 1 vị trí ghế xuyên suốt các hàng
                                nên ghế hàng dưới luôn thẳng cột với ghế hàng trên -->
                                <div
                                    v-if="layoutMatches && gridSpec"
                                    class="grid gap-2"
                                    :style="{
                                        gridTemplateColumns:
                                            gridSpec.templateColumns,
                                    }"
                                >
                                    <template
                                        v-for="(row, ri) in gridRows"
                                        :key="ri"
                                    >
                                        <!-- Ghế lái — cùng hình ghế thật nhưng màu trung tính -->
                                        <div
                                            v-if="row.front"
                                            class="relative h-14 w-12"
                                            :style="{
                                                gridColumn: '1 / 2',
                                                gridRow: ri + 1,
                                            }"
                                        >
                                            <svg
                                                viewBox="0 0 48 56"
                                                class="h-full w-full"
                                            >
                                                <path
                                                    d="M 10,16 Q 10,2 24,2 Q 38,2 38,16 L 38,28 Q 46,28 46,34 L 46,40 Q 46,46 38,46 Q 38,52 32,52 L 16,52 Q 10,52 10,46 Q 2,46 2,40 L 2,34 Q 2,28 10,28 L 10,16 Z"
                                                    class="fill-gray-200 stroke-gray-300"
                                                    stroke-width="2.5"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>
                                            <span
                                                class="pointer-events-none absolute inset-0 flex items-center justify-center pt-1 text-[9px] leading-tight font-medium text-gray-400"
                                            >
                                                Tài<br />xế
                                            </span>
                                        </div>

                                        <template
                                            v-for="(cell, ci) in row.cells"
                                            :key="ci"
                                        >
                                            <SeatButton
                                                v-if="cell.kind === 'seat'"
                                                :seat="cell.seat!"
                                                :selected="
                                                    selected.includes(
                                                        cell.seat!.seat_code,
                                                    )
                                                "
                                                :style="{
                                                    gridColumn: cell.gridColumn,
                                                    gridRow: ri + 1,
                                                }"
                                                @toggle="toggleSeat(cell.seat!)"
                                            />
                                            <div
                                                v-else
                                                :style="{
                                                    gridColumn: cell.gridColumn,
                                                    gridRow: ri + 1,
                                                }"
                                                :class="[
                                                    'flex items-end',
                                                    cell.seats!.length > 1
                                                        ? 'justify-between'
                                                        : 'justify-center',
                                                ]"
                                            >
                                                <SeatButton
                                                    v-for="seat in cell.seats"
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
                                    </template>
                                </div>

                                <!-- Fallback: loại xe lạ / dữ liệu không khớp layout đã khai báo -->
                                <div
                                    v-else
                                    class="flex flex-col items-center gap-2"
                                >
                                    <div
                                        v-for="(row, ri) in fallbackRows"
                                        :key="ri"
                                        class="flex w-full max-w-xs items-end justify-center gap-2"
                                    >
                                        <div
                                            v-if="row.front"
                                            class="relative h-14 w-12 shrink-0"
                                        >
                                            <svg
                                                viewBox="0 0 48 56"
                                                class="h-full w-full"
                                            >
                                                <path
                                                    d="M 10,16 Q 10,2 24,2 Q 38,2 38,16 L 38,28 Q 46,28 46,34 L 46,40 Q 46,46 38,46 Q 38,52 32,52 L 16,52 Q 10,52 10,46 Q 2,46 2,40 L 2,34 Q 2,28 10,28 L 10,16 Z"
                                                    class="fill-gray-200 stroke-gray-300"
                                                    stroke-width="2.5"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>
                                            <span
                                                class="pointer-events-none absolute inset-0 flex items-center justify-center pt-1 text-[9px] leading-tight font-medium text-gray-400"
                                            >
                                                Tài<br />xế
                                            </span>
                                        </div>

                                        <template
                                            v-for="(slot, si) in row.slots"
                                            :key="si"
                                        >
                                            <div
                                                v-if="slot.type === 'aisle'"
                                                class="w-5 shrink-0"
                                            />
                                            <SeatButton
                                                v-else
                                                :seat="slot.seat"
                                                :selected="
                                                    selected.includes(
                                                        slot.seat.seat_code,
                                                    )
                                                "
                                                @toggle="toggleSeat(slot.seat)"
                                            />
                                        </template>
                                    </div>
                                </div>

                                <div
                                    class="mt-3 text-center text-xs text-gray-400 italic"
                                >
                                    Đuôi xe
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
                                {{ tripInfo.route?.origin_city ?? 'Hà Nội' }} →
                                {{ tripInfo.route?.dest_city ?? 'Hải Phòng' }}
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
