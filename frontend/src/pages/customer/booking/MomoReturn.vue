<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { customerApi } from '@/api/customer.api';

const route = useRoute();
const router = useRouter();

type State = 'checking' | 'paid' | 'pending' | 'failed' | 'error';

const state = ref<State>('checking');
const message = ref('');
const bookingId = ref<string | null>(null);
const bookingCode = ref<string | null>(null);

const orderId = String(route.query.orderId ?? '');
// resultCode CHỈ dùng để hiểu ý định của khách (0 = đã trả, 1006 = tự huỷ…).
// KHÔNG dùng nó để kết luận đã thanh toán: tham số trên URL do client giữ nên
// sửa được — trạng thái thật luôn lấy từ DB, do IPN server-to-server ghi.
const resultCode = Number(route.query.resultCode ?? -1);

let pollTimer: ReturnType<typeof setInterval> | null = null;
let stopTimer: ReturnType<typeof setTimeout> | null = null;

function stopPolling() {
    if (pollTimer) clearInterval(pollTimer);
    if (stopTimer) clearTimeout(stopTimer);
    pollTimer = null;
    stopTimer = null;
}

async function check(): Promise<boolean> {
    const { data, error } = await customerApi.getPaymentByOrder(orderId);
    if (error) {
        state.value = 'error';
        message.value =
            typeof error === 'string'
                ? error
                : 'Không tra cứu được trạng thái thanh toán.';
        return true;
    }

    bookingId.value = data?.booking_id ?? null;
    bookingCode.value = data?.booking_code ?? null;

    if (
        data?.payment_status === 'paid' ||
        data?.booking_status === 'confirmed'
    ) {
        state.value = 'paid';
        return true;
    }
    return false;
}

onMounted(async () => {
    if (!orderId) {
        state.value = 'error';
        message.value = 'Thiếu mã giao dịch trong đường dẫn.';
        return;
    }

    if (await check()) return;

    // Khách thường quay về TRƯỚC khi MoMo kịp gọi IPN, nên hỏi lại vài nhịp thay
    // vì báo thất bại ngay.
    if (resultCode !== 0) {
        state.value = 'failed';
        message.value =
            resultCode === 1006
                ? 'Bạn đã huỷ giao dịch trên MoMo.'
                : 'Giao dịch chưa hoàn tất trên MoMo.';
        return;
    }

    state.value = 'pending';
    pollTimer = setInterval(async () => {
        if (await check()) stopPolling();
    }, 3000);
    stopTimer = setTimeout(() => {
        stopPolling();
        if (state.value === 'pending') {
            message.value =
                'MoMo chưa xác nhận xong. Vé sẽ tự cập nhật khi có kết quả — bạn kiểm tra lại ở mục Vé của tôi.';
        }
    }, 45000);
});

onUnmounted(stopPolling);

function goToBooking() {
    if (bookingId.value)
        router.push(`/booking/${bookingId.value}/confirmation`);
    else router.push('/bookings');
}
</script>

<template>
    <div class="mx-auto flex max-w-lg flex-col items-center px-4 py-16">
        <!-- Đang tra cứu / chờ IPN -->
        <div
            v-if="state === 'checking' || state === 'pending'"
            class="w-full rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm"
        >
            <div
                class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"
            />
            <h1 class="text-lg font-bold text-gray-900">
                Đang xác nhận thanh toán
            </h1>
            <p class="mt-2 text-sm text-gray-500">
                {{
                    message ||
                    'Vui lòng đợi trong giây lát, chúng tôi đang đối soát với MoMo.'
                }}
            </p>
            <button
                v-if="message"
                @click="goToBooking"
                class="mt-5 rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50"
            >
                Xem vé của tôi
            </button>
        </div>

        <!-- Thành công -->
        <div
            v-else-if="state === 'paid'"
            class="w-full rounded-2xl border border-green-200 bg-white p-8 text-center shadow-sm"
        >
            <div
                class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-3xl"
            >
                ✅
            </div>
            <h1 class="text-lg font-bold text-gray-900">
                Thanh toán thành công
            </h1>
            <p class="mt-2 text-sm text-gray-500">
                Vé
                <span v-if="bookingCode" class="font-mono font-semibold">{{
                    bookingCode
                }}</span>
                đã được xác nhận.
            </p>
            <button
                @click="goToBooking"
                class="mt-5 rounded-xl bg-blue-600 px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-blue-700"
            >
                Xem vé
            </button>
        </div>

        <!-- Huỷ / thất bại -->
        <div
            v-else-if="state === 'failed'"
            class="w-full rounded-2xl border border-amber-200 bg-white p-8 text-center shadow-sm"
        >
            <div
                class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-3xl"
            >
                ⚠️
            </div>
            <h1 class="text-lg font-bold text-gray-900">
                Thanh toán chưa hoàn tất
            </h1>
            <p class="mt-2 text-sm text-gray-500">{{ message }}</p>
            <p class="mt-1 text-xs text-gray-400">
                Ghế vẫn được giữ trong thời gian còn hiệu lực — bạn có thể thanh
                toán lại.
            </p>
            <div class="mt-5 flex justify-center gap-3">
                <button
                    @click="goToBooking"
                    class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-blue-700"
                >
                    Thử lại
                </button>
                <router-link
                    to="/bookings"
                    class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50"
                >
                    Vé của tôi
                </router-link>
            </div>
        </div>

        <!-- Lỗi tra cứu -->
        <div
            v-else
            class="w-full rounded-2xl border border-red-200 bg-white p-8 text-center shadow-sm"
        >
            <div
                class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-3xl"
            >
                ❌
            </div>
            <h1 class="text-lg font-bold text-gray-900">
                Không tra cứu được giao dịch
            </h1>
            <p class="mt-2 text-sm text-gray-500">{{ message }}</p>
            <router-link
                to="/bookings"
                class="mt-5 inline-block rounded-xl bg-blue-600 px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-blue-700"
            >
                Vé của tôi
            </router-link>
        </div>
    </div>
</template>
