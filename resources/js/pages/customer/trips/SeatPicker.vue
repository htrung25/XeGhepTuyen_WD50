<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { customerApi } from '@/api/customer.api';
import { useCustomerAuthStore } from '@/stores/customer.auth.store';
import { useCustomerStore } from '@/stores/customer.store';
import type { SeatInfo } from '@/stores/customer.store';

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

function seatClasses(s: SeatInfo) {
    if (s.status === 'driver')
        return 'bg-gray-200 text-gray-400 cursor-not-allowed border-gray-200';
    if (s.status === 'booked')
        return 'bg-red-100 text-red-400 cursor-not-allowed border-red-200';
    if (s.status === 'locked')
        return 'bg-yellow-100 text-yellow-600 cursor-not-allowed border-yellow-300';
    if (selected.value.includes(s.seat_code))
        return 'bg-blue-600 text-white border-blue-600 shadow-md';
    return 'bg-white text-gray-700 border-gray-300 hover:border-blue-400 hover:bg-blue-50 cursor-pointer';
}

function toggleSeat(s: SeatInfo) {
    if (s.status !== 'available') return;
    const idx = selected.value.indexOf(s.seat_code);
    if (idx >= 0) {
        selected.value.splice(idx, 1);
    } else if (selected.value.length < maxSeats.value) {
        selected.value.push(s.seat_code);
    }
}

const seatGrid = computed(() => {
    const rows: SeatInfo[][] = [];
    const seatList = seats.value.filter((s) => s.status !== 'driver');
    for (let i = 0; i < seatList.length; i += 2) {
        rows.push(seatList.slice(i, i + 2));
    }
    return rows;
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

let echoChannel: any = null;

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

    // WebSocket real-time seat updates
    if ((window as any).Echo) {
        echoChannel = (window as any).Echo.channel(`trips.${tripId}`).listen(
            '.seat.status.updated',
            (e: any) => {
                const seat = seats.value.find((s) => s.id === e.seat_id);
                if (seat) {
                    seat.status = e.status;
                    if (
                        e.status !== 'available' &&
                        selected.value.includes(seat.seat_code)
                    ) {
                        selected.value = selected.value.filter(
                            (c) => c !== seat.seat_code,
                        );
                    }
                }
            },
        );
    }
});

onUnmounted(() => {
    if (echoChannel) (window as any).Echo?.leave(`trips.${tripId}`);
});
</script>

<template>
    <div class="mx-auto max-w-5xl px-6 py-8">
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

        <div v-else class="grid grid-cols-[1fr_340px] gap-8">
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

                    <!-- Car visual -->
                    <div v-else class="flex flex-col items-center gap-2">
                        <!-- Driver row -->
                        <div
                            class="mb-2 flex w-full max-w-xs items-center justify-between"
                        >
                            <div
                                class="flex h-10 w-14 items-center justify-center rounded-lg border border-gray-200 bg-gray-200 text-xs font-medium text-gray-400"
                            >
                                Tài xế
                            </div>
                            <div class="text-xs text-gray-400 italic">
                                Đầu xe
                            </div>
                        </div>

                        <!-- Seat rows -->
                        <div
                            v-for="(row, ri) in seatGrid"
                            :key="ri"
                            class="flex w-full max-w-xs justify-center gap-4"
                        >
                            <button
                                v-for="seat in row"
                                :key="seat.seat_code"
                                @click="toggleSeat(seat)"
                                :disabled="seat.status !== 'available'"
                                :class="[
                                    'h-12 w-14 rounded-lg border-2 text-sm font-bold transition-all',
                                    seatClasses(seat),
                                ]"
                            >
                                {{ seat.seat_code }}
                            </button>
                            <!-- Fill empty if odd -->
                            <div v-if="row.length < 2" class="w-14" />
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
            <div class="sticky top-20">
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
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3.5 text-sm font-bold text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-400"
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
