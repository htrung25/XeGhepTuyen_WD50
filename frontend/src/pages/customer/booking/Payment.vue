<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { customerApi } from '@/api/customer.api';
import { useCustomerStore } from '@/stores/customer.store';

const router = useRouter();
const store = useCustomerStore();
const draft = store.bookingDraft;

const selectedMethod = ref<'momo' | 'vnpay' | 'wallet' | 'cash'>('vnpay');
const isLoading = ref(false);
const errorMsg = ref('');
const walletBalance = ref(store.walletBalance);
const bookingData = ref<any>(null);
const loadingBooking = ref(true);

// SePay variables
const showSepayModal = ref(false);
const sepayQrUrl = ref('');
const sepayBankInfo = ref<any>(null);
const isCopied = ref(false);
let sepayPollingInterval: ReturnType<typeof setInterval> | null = null;

const paySeconds = ref(900);
let countdown: ReturnType<typeof setInterval> | null = null;
const countdownLabel = computed(() => {
    const m = Math.floor(paySeconds.value / 60);
    const s = paySeconds.value % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
});
const countdownUrgent = computed(() => paySeconds.value < 180);

const subtotal = computed(() =>
    draft.seats.reduce((sum, s) => sum + s.price, 0),
);
const total = computed(() =>
    Math.max(0, subtotal.value - draft.voucher_discount),
);

function fmt(v: number) {
    return new Intl.NumberFormat('vi-VN').format(v) + 'đ';
}

const paymentMethods = computed(() => [
    {
        key: 'vnpay' as const,
        label: 'Chuyển khoản VietQR',
        desc: 'Quét mã VietQR chuyển khoản ngân hàng tự động 24/7',
        badge: 'Tự động',
        badgeColor: 'bg-green-100 text-green-700',
        icon: '🏦',
        disabled: false,
    },
    {
        key: 'momo' as const,
        label: 'Ví MoMo',
        desc: 'Quét mã QR hoặc ứng dụng MoMo',
        badge: null,
        badgeColor: '',
        icon: '💜',
        disabled: false,
    },
    {
        key: 'wallet' as const,
        label: 'Ví XeGhepTuyen-Fgroup',
        desc:
            walletBalance.value >= total.value
                ? `Số dư: ${fmt(walletBalance.value)}`
                : `Số dư không đủ — Hiện có ${fmt(walletBalance.value)}`,
        badge: walletBalance.value >= total.value ? null : 'Không đủ tiền',
        badgeColor: 'bg-red-100 text-red-600',
        icon: '👛',
        disabled: walletBalance.value < total.value,
    },
    {
        key: 'cash' as const,
        label: 'Tiền mặt',
        desc: 'Thanh toán tiền mặt khi lên xe',
        badge: draft.voucher_discount > 0 ? 'Không áp dụng với voucher' : null,
        badgeColor: 'bg-amber-100 text-amber-700',
        icon: '💵',
        disabled: draft.voucher_discount > 0,
    },
]);

function copyText(text: string) {
    navigator.clipboard.writeText(text);
    isCopied.value = true;
    setTimeout(() => {
        isCopied.value = false;
    }, 2000);
}

function startSepayPolling(bookingId: string) {
    if (sepayPollingInterval) clearInterval(sepayPollingInterval);
    sepayPollingInterval = setInterval(async () => {
        const { data } = await customerApi.getBooking(bookingId);
        if (
            data &&
            (data.payment_status === 'paid' ||
                data.booking_status === 'confirmed')
        ) {
            if (sepayPollingInterval) clearInterval(sepayPollingInterval);
            showSepayModal.value = false;
            router.push(`/booking/${bookingId}/confirmation`);
        }
    }, 3000);
}

async function pay() {
    if (isLoading.value) return;
    isLoading.value = true;
    errorMsg.value = '';
    const bookingId = store.currentBookingId;
    if (!bookingId) {
        errorMsg.value = 'Không tìm thấy thông tin đặt vé. Vui lòng thử lại.';
        isLoading.value = false;
        return;
    }
    const { data, error } = await customerApi.initiatePayment({
        booking_id: bookingId,
        method: selectedMethod.value,
    });
    isLoading.value = false;
    if (error) {
        errorMsg.value = error as string;
        return;
    }
    if (selectedMethod.value === 'cash' || selectedMethod.value === 'wallet') {
        router.push(`/booking/${bookingId}/confirmation`);
        return;
    }

    if (selectedMethod.value === 'vnpay') {
        if (data?.payment_url && data?.bank_info) {
            sepayQrUrl.value = data.payment_url;
            sepayBankInfo.value = data.bank_info;
            showSepayModal.value = true;
            startSepayPolling(bookingId);
        } else {
            errorMsg.value =
                'Không thể khởi tạo mã QR chuyển khoản. Vui lòng thử lại.';
        }
        return;
    }

    if (data?.payment_url) {
        window.location.href = data.payment_url;
    } else {
        errorMsg.value = 'Không thể khởi tạo thanh toán. Vui lòng thử lại.';
    }
}

onMounted(async () => {
    if (!store.currentBookingId) {
        router.replace('/home');
        return;
    }
    const { data } = await customerApi.getBooking(store.currentBookingId);
    loadingBooking.value = false;
    bookingData.value = data;
    const { data: wallet } = await customerApi.getWallet();
    if (wallet) {
        walletBalance.value = wallet.balance;
        store.walletBalance = wallet.balance;
    }
    countdown = setInterval(() => {
        if (paySeconds.value > 0) paySeconds.value--;
        else {
            clearInterval(countdown!);
            router.replace('/home');
        }
    }, 1000);
});

onUnmounted(() => {
    if (countdown) clearInterval(countdown);
    if (sepayPollingInterval) clearInterval(sepayPollingInterval);
});
</script>

<template>
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 sm:py-8">
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
                        i < 2
                            ? 'bg-blue-100 text-blue-400'
                            : 'bg-blue-600 text-white',
                    ]"
                >
                    <svg
                        v-if="i < 2"
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
                    <span v-else>3</span>
                </div>
                <span
                    :class="[
                        'text-sm font-medium',
                        i === 2 ? 'text-blue-700' : 'text-gray-400',
                    ]"
                    >{{ step }}</span
                >
                <span v-if="i < 2" class="mx-1 text-gray-300">→</span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_340px] lg:gap-8">
            <!-- ─── LEFT: Payment Methods ──────────────────── -->
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-gray-900">
                    Chọn phương thức thanh toán
                </h2>

                <!-- Countdown banner -->
                <div
                    :class="[
                        'flex items-center justify-between rounded-xl border p-4',
                        countdownUrgent
                            ? 'border-red-200 bg-red-50'
                            : 'border-orange-200 bg-orange-50',
                    ]"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-lg">⏳</span>
                        <span
                            :class="[
                                'text-sm font-medium',
                                countdownUrgent
                                    ? 'text-red-700'
                                    : 'text-orange-800',
                            ]"
                        >
                            Đơn hàng sẽ hết hạn sau
                        </span>
                    </div>
                    <span
                        :class="[
                            'text-lg font-bold tabular-nums',
                            countdownUrgent
                                ? 'text-red-600'
                                : 'text-orange-600',
                        ]"
                    >
                        {{ countdownLabel }}
                    </span>
                </div>
                <p
                    v-if="countdownUrgent"
                    class="-mt-2 text-xs font-medium text-red-600"
                >
                    Vui lòng hoàn tất thanh toán ngay để không mất ghế!
                </p>

                <!-- Payment method cards -->
                <div class="space-y-3">
                    <label
                        v-for="method in paymentMethods"
                        :key="method.key"
                        :class="[
                            'flex items-center gap-4 rounded-xl border-2 p-4 transition-all',
                            method.disabled
                                ? 'cursor-not-allowed border-gray-200 bg-gray-50 opacity-50'
                                : selectedMethod === method.key
                                  ? 'cursor-pointer border-blue-600 bg-blue-50 shadow-sm'
                                  : 'cursor-pointer border-gray-200 bg-white hover:border-gray-300',
                        ]"
                    >
                        <input
                            type="radio"
                            name="payment"
                            :value="method.key"
                            v-model="selectedMethod"
                            :disabled="method.disabled"
                            class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <div class="shrink-0 text-2xl">{{ method.icon }}</div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900">
                                {{ method.label }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500">
                                {{ method.desc }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span
                                v-if="method.badge"
                                :class="[
                                    'rounded-full px-2.5 py-1 text-xs font-semibold',
                                    method.badgeColor,
                                ]"
                            >
                                {{ method.badge }}
                            </span>
                            <span
                                v-if="
                                    selectedMethod === method.key &&
                                    !method.disabled
                                "
                                class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-600"
                            >
                                Đã chọn ✓
                            </span>
                        </div>
                    </label>
                </div>

                <div
                    v-if="errorMsg"
                    class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                >
                    {{ errorMsg }}
                </div>

                <button
                    @click="pay"
                    :disabled="isLoading"
                    class="flex w-full items-center justify-center gap-3 rounded-xl bg-blue-600 py-4 text-base font-bold text-white shadow-lg shadow-blue-100 transition-colors hover:bg-blue-700 disabled:opacity-60"
                >
                    <div
                        v-if="isLoading"
                        class="h-5 w-5 animate-spin rounded-full border-2 border-white border-t-transparent"
                    />
                    <span>{{
                        isLoading ? 'Đang xử lý...' : 'Xác nhận & Thanh toán'
                    }}</span>
                    <span v-if="!isLoading">→</span>
                </button>

                <p class="text-center text-xs text-gray-400">
                    🔒 Thông tin thanh toán được mã hóa SSL 256-bit
                </p>
            </div>

            <!-- ─── RIGHT: Order Summary ────────────────────── -->
            <div class="lg:sticky lg:top-20">
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <h3 class="mb-4 font-semibold text-gray-900">
                        Tóm tắt đơn hàng
                    </h3>
                    <div
                        v-if="bookingData"
                        class="mb-4 space-y-2.5 border-b border-gray-100 pb-4 text-sm"
                    >
                        <div class="flex justify-between">
                            <span class="text-gray-500">Mã đặt vé</span>
                            <span
                                class="font-mono text-xs font-bold text-gray-900"
                                >{{ bookingData.booking_code }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tuyến</span>
                            <span class="font-medium text-gray-900"
                                >Hà Nội → Hải Phòng</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Ghế</span>
                            <span class="font-medium">{{
                                draft.seat_codes.join(', ')
                            }}</span>
                        </div>
                    </div>
                    <div
                        v-else-if="loadingBooking"
                        class="mb-4 animate-pulse space-y-2 border-b border-gray-100 pb-4"
                    >
                        <div class="h-4 w-full rounded bg-gray-200" />
                        <div class="h-4 w-3/4 rounded bg-gray-200" />
                    </div>
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
                        class="flex justify-between border-t border-gray-200 py-3"
                    >
                        <span class="font-bold text-gray-900"
                            >Tổng thanh toán</span
                        >
                        <span
                            class="text-2xl font-bold text-blue-600 tabular-nums"
                            >{{ fmt(total) }}</span
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- SEPAY VIETQR MODAL -->
        <div
            v-if="showSepayModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
        >
            <div
                class="relative flex w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-2xl"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-6 py-4"
                >
                    <div>
                        <h3
                            class="flex items-center gap-2 text-base font-bold text-slate-900"
                        >
                            <span>🏦</span> Thanh toán chuyển khoản VietQR
                        </h3>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Hệ thống ghi nhận giao dịch tự động trong 10-30 giây
                        </p>
                    </div>
                    <button
                        type="button"
                        class="p-1 text-slate-400 transition hover:text-slate-600"
                        @click="showSepayModal = false"
                    >
                        <svg
                            class="h-6 w-6"
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

                <!-- Content -->
                <div
                    class="grid grid-cols-1 gap-6 bg-slate-50/50 p-6 md:grid-cols-2"
                >
                    <!-- Left: QR Code -->
                    <div
                        class="flex flex-col items-center justify-center rounded-xl border border-slate-100 bg-white p-4 shadow-sm"
                    >
                        <img
                            :src="sepayQrUrl"
                            alt="VietQR SePay"
                            class="h-56 w-56 rounded-lg border border-slate-100 object-contain p-1"
                        />
                        <p
                            class="mt-3 text-center text-[11px] leading-relaxed text-slate-500"
                        >
                            Mở App Ngân hàng quét mã QR để điền nhanh mọi thông
                            tin chuyển khoản
                        </p>
                    </div>

                    <!-- Right: Bank Info details -->
                    <div class="space-y-4">
                        <div
                            class="space-y-3 rounded-xl border border-slate-100 bg-white p-4 shadow-sm"
                        >
                            <div
                                class="flex items-center justify-between border-b border-slate-100 pb-2 text-sm"
                            >
                                <span class="text-slate-500">Ngân hàng</span>
                                <span class="font-bold text-slate-800">{{
                                    sepayBankInfo?.bank_name
                                }}</span>
                            </div>

                            <div
                                class="flex items-center justify-between border-b border-slate-100 pb-2 text-sm"
                            >
                                <span class="text-slate-500">Số tài khoản</span>
                                <div
                                    class="flex items-center gap-1.5 font-bold text-slate-800"
                                >
                                    <span>{{ sepayBankInfo?.bank_acc }}</span>
                                    <button
                                        @click="
                                            copyText(sepayBankInfo?.bank_acc)
                                        "
                                        class="text-xs font-semibold text-green-600 hover:text-green-700"
                                    >
                                        Sao chép
                                    </button>
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-between border-b border-slate-100 pb-2 text-sm"
                            >
                                <span class="text-slate-500"
                                    >Chủ tài khoản</span
                                >
                                <span
                                    class="font-bold text-slate-800 uppercase"
                                    >{{ sepayBankInfo?.acc_name }}</span
                                >
                            </div>

                            <div
                                class="flex items-center justify-between border-b border-slate-100 pb-2 text-sm"
                            >
                                <span class="text-slate-500"
                                    >Số tiền cần chuyển</span
                                >
                                <span
                                    class="text-base font-bold text-green-600"
                                    >{{ fmt(sepayBankInfo?.amount) }}</span
                                >
                            </div>

                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="font-medium text-slate-500"
                                    >Nội dung bắt buộc</span
                                >
                                <div
                                    class="flex items-center gap-1.5 font-bold text-red-600"
                                >
                                    <span
                                        class="rounded border border-red-200 bg-red-50 px-2 py-0.5 font-mono text-xs font-bold text-red-600"
                                        >{{ sepayBankInfo?.code }}</span
                                    >
                                    <button
                                        @click="copyText(sepayBankInfo?.code)"
                                        class="text-xs font-semibold text-green-600 hover:text-green-700"
                                    >
                                        Sao chép
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Warning banner -->
                        <div
                            class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-relaxed text-amber-800"
                        >
                            <strong>⚠️ Chú ý:</strong> Bạn cần nhập
                            <strong>chính xác</strong> nội dung chuyển khoản ở
                            trên để hệ thống tự động xác thực vé.
                        </div>
                    </div>
                </div>

                <!-- Footer / Status checking -->
                <div
                    class="flex flex-col items-center gap-3 border-t border-slate-100 bg-white px-6 py-4"
                >
                    <div
                        class="flex items-center gap-2 text-sm font-semibold text-green-700"
                    >
                        <div
                            class="h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-green-600 border-t-transparent"
                        />
                        <span>Đang chờ chuyển khoản...</span>
                    </div>

                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                        @click="showSepayModal = false"
                    >
                        Hủy & Chọn phương thức khác
                    </button>
                </div>
            </div>

            <!-- Copied Toast -->
            <div
                v-if="isCopied"
                class="fixed bottom-10 left-1/2 z-50 -translate-x-1/2 rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-lg"
            >
                Đã sao chép vào bộ nhớ tạm!
            </div>
        </div>
    </div>
</template>
