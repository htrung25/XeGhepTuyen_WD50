<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { customerApi } from '@/api/customer.api';
import { useCustomerStore } from '@/stores/customer.store';
import type { RouteStop } from '@/stores/customer.store';

const router = useRouter();
const store = useCustomerStore();
const draft = store.bookingDraft;

const pickupStops = ref<RouteStop[]>([]);
const dropoffStops = ref<RouteStop[]>([]);
const tripData = ref<any>(null);
const isLoading = ref(true);
const submitLoading = ref(false);
const errorMsg = ref('');
const voucherLoading = ref(false);
const voucherMsg = ref('');
const voucherOk = ref(false);

// Countdown (10 min lock)
const lockSeconds = ref(600);
let countdownInterval: ReturnType<typeof setInterval> | null = null;
const countdownLabel = computed(() => {
    const m = Math.floor(lockSeconds.value / 60);
    const s = lockSeconds.value % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
});

const subtotal = computed(() =>
    draft.seats.reduce((sum, s) => sum + s.price, 0),
);
const total = computed(() =>
    Math.max(0, subtotal.value - draft.voucher_discount),
);
const errors = ref<Record<string, string>>({});

const hanoiStops = [
    'Mỹ Đình',
    'Cầu Giấy',
    'Trung Hòa',
    'Giải Phóng',
    'Gia Lâm',
];
const haiphongStops = [
    'An Dương',
    'Cầu Rào',
    'Lạch Tray',
    'Trung tâm HP',
    'Máy Tơ',
];

function fmt(v: number) {
    return new Intl.NumberFormat('vi-VN').format(v) + 'đ';
}

function validate() {
    errors.value = {};
    if (!draft.passenger_name?.trim())
        errors.value.passenger_name = 'Vui lòng nhập họ tên';
    if (!/^(0[3|5|7|8|9])[0-9]{8}$/.test(draft.passenger_phone ?? ''))
        errors.value.passenger_phone =
            'Số điện thoại không hợp lệ (10 số, bắt đầu bằng 0)';
    if (!draft.pickup_stop_id)
        errors.value.pickup_stop_id = 'Vui lòng chọn điểm đón';
    if (!draft.dropoff_stop_id)
        errors.value.dropoff_stop_id = 'Vui lòng chọn điểm trả';
    return Object.keys(errors.value).length === 0;
}

async function applyVoucher() {
    if (!draft.voucher_code.trim()) return;
    voucherLoading.value = true;
    voucherMsg.value = '';
    const { data, error } = await customerApi.applyVoucher({
        code: draft.voucher_code,
        trip_id: draft.trip_id,
        amount: subtotal.value,
    });
    voucherLoading.value = false;
    if (error) {
        voucherMsg.value = 'Mã không hợp lệ hoặc đã hết hạn';
        voucherOk.value = false;
        draft.voucher_discount = 0;
    } else {
        draft.voucher_discount = data?.discount_amount ?? 0;
        voucherMsg.value = `Giảm ${fmt(draft.voucher_discount)} — Mã ${draft.voucher_code.toUpperCase()} hợp lệ`;
        voucherOk.value = true;
    }
}

async function submit() {
    if (!validate()) return;
    submitLoading.value = true;
    errorMsg.value = '';
    const { data, error } = await customerApi.createBooking({
        trip_id: draft.trip_id,
        seat_ids: draft.seats.map((s) => s.id),
        pickup_stop_id: draft.pickup_stop_id,
        dropoff_stop_id: draft.dropoff_stop_id,
        pickup_address: draft.pickup_detail || undefined,
        note: draft.note || undefined,
        voucher_code: draft.voucher_code || undefined,
        passenger_count: draft.seats.length,
        contact_name: draft.passenger_name,
        contact_phone: draft.passenger_phone,
        // Method thật được chọn ở trang Payment; gửi tạm 'momo' để tạo booking (Pending/Unpaid)
        payment_method: 'momo',
        passengers: draft.seats.map((s, i) => ({
            full_name: draft.passenger_name,
            phone: i === 0 ? draft.passenger_phone : undefined,
        })),
    });
    submitLoading.value = false;
    if (error) {
        errorMsg.value = error as string;
        return;
    }
    store.currentBookingId = data?.id ?? null;
    router.push('/booking/payment');
}

onMounted(async () => {
    if (!draft.trip_id) {
        router.replace('/home');
        return;
    }
    isLoading.value = true;
    const { data } = await customerApi.getPublicTrip(draft.trip_id);
    isLoading.value = false;
    tripData.value = data;

    // Build stop lists từ chi tiết chuyến (BE trả pickup_stops/dropoff_stops đã lọc sẵn)
    pickupStops.value = (data?.pickup_stops ?? []) as RouteStop[];
    dropoffStops.value = (data?.dropoff_stops ?? []) as RouteStop[];

    // Start lock countdown
    countdownInterval = setInterval(() => {
        if (lockSeconds.value > 0) lockSeconds.value--;
        else {
            clearInterval(countdownInterval!);
            router.replace('/home');
        }
    }, 1000);
});

onUnmounted(() => {
    if (countdownInterval) clearInterval(countdownInterval);
});
</script>

<template>
    <div class="mx-auto max-w-5xl px-6 py-8">
        <!-- Step indicator -->
        <div class="mb-8 flex items-center justify-center gap-2">
            <div
                v-for="(step, i) in ['Chọn ghế', 'Thông tin', 'Thanh toán']"
                :key="i"
                class="flex items-center gap-2"
            >
                <div
                    :class="[
                        'flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold',
                        i === 0
                            ? 'bg-blue-100 text-blue-400 ring-1 ring-blue-200'
                            : i === 1
                              ? 'bg-blue-600 text-white'
                              : 'bg-gray-100 text-gray-400',
                    ]"
                >
                    <svg
                        v-if="i === 0"
                        class="h-3.5 w-3.5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="3"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    <span v-else>{{ i + 1 }}</span>
                </div>
                <span
                    :class="[
                        'text-sm font-medium',
                        i === 1 ? 'text-blue-700' : 'text-gray-400',
                    ]"
                    >{{ step }}</span
                >
                <span v-if="i < 2" class="mx-1 text-gray-300">→</span>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="flex justify-center py-16">
            <div
                class="h-8 w-8 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"
            />
        </div>

        <div v-else class="grid grid-cols-[1fr_340px] gap-8">
            <!-- ─── LEFT: Form ─────────────────────────────── -->
            <div class="space-y-5">
                <!-- Contact info -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <h2 class="mb-4 font-semibold text-gray-900">
                        Thông tin liên hệ
                    </h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Họ và tên <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="draft.passenger_name"
                                type="text"
                                placeholder="Nguyễn Văn A"
                                :class="[
                                    'w-full rounded-lg border px-3.5 py-2.5 text-sm transition-colors focus:ring-2 focus:ring-blue-500 focus:outline-none',
                                    errors.passenger_name
                                        ? 'border-red-400 bg-red-50'
                                        : 'border-gray-300',
                                ]"
                            />
                            <p
                                v-if="errors.passenger_name"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ errors.passenger_name }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Số điện thoại
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="draft.passenger_phone"
                                type="tel"
                                placeholder="0901234567"
                                :class="[
                                    'w-full rounded-lg border px-3.5 py-2.5 text-sm transition-colors focus:ring-2 focus:ring-blue-500 focus:outline-none',
                                    errors.passenger_phone
                                        ? 'border-red-400 bg-red-50'
                                        : 'border-gray-300',
                                ]"
                            />
                            <p
                                v-if="errors.passenger_phone"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ errors.passenger_phone }}
                            </p>
                            <p class="mt-1 text-xs text-gray-400">
                                Tài xế sẽ liên hệ qua số này
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pickup & Dropoff -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <h2 class="mb-4 font-semibold text-gray-900">
                        Điểm đón &amp; trả
                    </h2>
                    <div class="mb-4 grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Điểm đón <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="draft.pickup_stop_id"
                                :class="[
                                    'w-full rounded-lg border px-3.5 py-2.5 text-sm transition-colors focus:ring-2 focus:ring-blue-500 focus:outline-none',
                                    errors.pickup_stop_id
                                        ? 'border-red-400 bg-red-50'
                                        : 'border-gray-300',
                                ]"
                            >
                                <option value="">-- Chọn điểm đón --</option>
                                <template v-if="pickupStops.length">
                                    <option
                                        v-for="s in pickupStops"
                                        :key="s.id"
                                        :value="s.id"
                                    >
                                        {{ s.stop_name }}
                                    </option>
                                </template>
                                <template v-else>
                                    <option
                                        v-for="name in hanoiStops"
                                        :key="name"
                                        :value="name"
                                    >
                                        {{ name }}
                                    </option>
                                </template>
                            </select>
                            <p
                                v-if="errors.pickup_stop_id"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ errors.pickup_stop_id }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Điểm trả <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="draft.dropoff_stop_id"
                                :class="[
                                    'w-full rounded-lg border px-3.5 py-2.5 text-sm transition-colors focus:ring-2 focus:ring-blue-500 focus:outline-none',
                                    errors.dropoff_stop_id
                                        ? 'border-red-400 bg-red-50'
                                        : 'border-gray-300',
                                ]"
                            >
                                <option value="">-- Chọn điểm trả --</option>
                                <template v-if="dropoffStops.length">
                                    <option
                                        v-for="s in dropoffStops"
                                        :key="s.id"
                                        :value="s.id"
                                    >
                                        {{ s.stop_name }}
                                    </option>
                                </template>
                                <template v-else>
                                    <option
                                        v-for="name in haiphongStops"
                                        :key="name"
                                        :value="name"
                                    >
                                        {{ name }}
                                    </option>
                                </template>
                            </select>
                            <p
                                v-if="errors.dropoff_stop_id"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ errors.dropoff_stop_id }}
                            </p>
                        </div>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                            >Địa chỉ chi tiết
                            <span class="font-normal text-gray-400"
                                >(không bắt buộc)</span
                            ></label
                        >
                        <input
                            v-model="draft.pickup_detail"
                            type="text"
                            placeholder="Số nhà, tên ngõ để tài xế dễ tìm hơn..."
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm transition-colors focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Note -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <h2 class="mb-4 font-semibold text-gray-900">
                        Ghi chú
                        <span class="text-sm font-normal text-gray-400"
                            >(không bắt buộc)</span
                        >
                    </h2>
                    <textarea
                        v-model="draft.note"
                        rows="3"
                        placeholder="Ghi chú cho tài xế, ví dụ: có 1 xe đạp, mang nhiều hành lý..."
                        class="w-full resize-none rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm transition-colors focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    />
                </div>

                <!-- Voucher -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <h2 class="mb-4 font-semibold text-gray-900">
                        Mã giảm giá
                    </h2>
                    <div class="flex gap-3">
                        <input
                            v-model="draft.voucher_code"
                            type="text"
                            placeholder="Nhập mã giảm giá..."
                            @keyup.enter="applyVoucher"
                            class="flex-1 rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm uppercase transition-colors placeholder:normal-case focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                        <button
                            @click="applyVoucher"
                            :disabled="
                                voucherLoading || !draft.voucher_code.trim()
                            "
                            class="rounded-lg border border-blue-600 px-5 py-2.5 text-sm font-medium text-blue-600 transition-colors hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{
                                voucherLoading ? 'Đang kiểm tra...' : 'Áp dụng'
                            }}
                        </button>
                    </div>
                    <p
                        v-if="voucherMsg"
                        :class="[
                            'mt-2 text-sm font-medium',
                            voucherOk ? 'text-green-600' : 'text-red-500',
                        ]"
                    >
                        {{ voucherOk ? '✓' : '✗' }} {{ voucherMsg }}
                    </p>
                </div>
            </div>

            <!-- ─── RIGHT: Order Summary ────────────────────── -->
            <div class="sticky top-20">
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <!-- Countdown -->
                    <div
                        class="mb-4 flex items-center justify-between rounded-lg border border-orange-200 bg-orange-50 p-3"
                    >
                        <span class="text-xs font-medium text-orange-800"
                            >⏱ Còn thời gian giữ ghế</span
                        >
                        <span
                            class="text-sm font-bold text-orange-600 tabular-nums"
                            >{{ countdownLabel }}</span
                        >
                    </div>

                    <h3 class="mb-4 font-semibold text-gray-900">
                        Tóm tắt đặt vé
                    </h3>

                    <!-- Trip summary -->
                    <div
                        v-if="tripData"
                        class="mb-4 space-y-2 border-b border-gray-100 pb-4 text-sm"
                    >
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tuyến</span>
                            <span class="font-medium text-gray-900"
                                >{{ tripData.route?.origin_city ?? 'HN' }} →
                                {{ tripData.route?.dest_city ?? 'HP' }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Giờ đi</span>
                            <span class="font-medium">{{
                                tripData.depart_at
                                    ? new Date(
                                          tripData.depart_at,
                                      ).toLocaleTimeString('vi-VN', {
                                          hour: '2-digit',
                                          minute: '2-digit',
                                          hour12: false,
                                      })
                                    : '—'
                            }}</span>
                        </div>
                    </div>

                    <!-- Seats -->
                    <div class="mb-4 border-b border-gray-100 pb-4">
                        <p
                            class="mb-2 text-xs font-medium tracking-wide text-gray-500 uppercase"
                        >
                            Ghế đã chọn
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="s in draft.seat_codes"
                                :key="s"
                                class="rounded-md bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-700"
                                >{{ s }}</span
                            >
                        </div>
                    </div>

                    <!-- Price breakdown -->
                    <div class="mb-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tạm tính</span>
                            <span>{{ fmt(subtotal) }}</span>
                        </div>
                        <div
                            v-if="draft.voucher_discount > 0"
                            class="flex justify-between text-green-600"
                        >
                            <span>Giảm giá</span>
                            <span>–{{ fmt(draft.voucher_discount) }}</span>
                        </div>
                    </div>

                    <div
                        class="mb-4 flex justify-between border-t border-gray-200 py-3"
                    >
                        <span class="font-semibold text-gray-900"
                            >Tổng cộng</span
                        >
                        <span class="text-xl font-bold text-blue-600">{{
                            fmt(total)
                        }}</span>
                    </div>

                    <!-- Error -->
                    <div
                        v-if="errorMsg"
                        class="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-600"
                    >
                        {{ errorMsg }}
                    </div>

                    <!-- Submit -->
                    <button
                        @click="submit"
                        :disabled="submitLoading"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3.5 text-sm font-bold text-white transition-colors hover:bg-blue-700 disabled:opacity-60"
                    >
                        <div
                            v-if="submitLoading"
                            class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                        />
                        <span>{{
                            submitLoading
                                ? 'Đang xử lý...'
                                : 'Chọn thanh toán →'
                        }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
