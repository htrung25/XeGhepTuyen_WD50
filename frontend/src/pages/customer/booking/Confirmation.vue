<script setup lang="ts">
import { ref, onBeforeUnmount, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { toast } from 'vue-sonner';
import { customerApi } from '@/api/customer.api';
import { useCustomerStore } from '@/stores/customer.store';

const route = useRoute();
const router = useRouter();
const store = useCustomerStore();

const bookingId = (route.params.id as string) || store.currentBookingId;
const booking = ref<any>(null);
const isLoading = ref(true);
const isDownloading = ref(false);
const isSharing = ref(false);
const errorMsg = ref('');
let qrPollTimer: ReturnType<typeof setTimeout> | null = null;
let qrPollAttempts = 0;
const MAX_QR_POLL_ATTEMPTS = 15;

function fmtDateTime(iso: string) {
    const d = new Date(iso);
    const days = [
        'Chủ nhật',
        'Thứ hai',
        'Thứ ba',
        'Thứ tư',
        'Thứ năm',
        'Thứ sáu',
        'Thứ bảy',
    ];
    return `${days[d.getDay()]}, ${d.toLocaleDateString('vi-VN')}`;
}

function fmtTime(iso: string) {
    return new Date(iso).toLocaleTimeString('vi-VN', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
}

async function pollQrCode() {
    if (!bookingId || booking.value?.qr_code) return;

    const { data } = await customerApi.getBookingQr(bookingId);
    if (data?.qr_code) {
        booking.value.qr_code = data.qr_code;
        return;
    }

    qrPollAttempts += 1;
    if (qrPollAttempts < MAX_QR_POLL_ATTEMPTS) {
        qrPollTimer = setTimeout(pollQrCode, 2000);
    }
}

async function downloadTicket() {
    if (!bookingId || isDownloading.value) return;

    isDownloading.value = true;
    const { data, error } = await customerApi.downloadBookingTicket(bookingId);
    isDownloading.value = false;

    if (error || !data) {
        toast.error(error || 'Không thể tải vé PDF.');
        return;
    }

    const url = URL.createObjectURL(data);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = `ve-${booking.value?.booking_code || bookingId}.pdf`;
    anchor.click();
    URL.revokeObjectURL(url);
}

async function shareTicket() {
    if (!bookingId || isSharing.value) return;

    isSharing.value = true;
    const shareUrl = window.location.href;
    const shareData = {
        title: `Vé XeGhep.vn ${booking.value?.booking_code || ''}`.trim(),
        text: `Thông tin vé ${booking.value?.trip?.route || ''}`.trim(),
        url: shareUrl,
    };

    try {
        if (navigator.share) {
            await navigator.share(shareData);
        } else {
            await navigator.clipboard.writeText(shareUrl);
            toast.success('Đã sao chép liên kết vé.');
        }
    } catch (error) {
        if ((error as DOMException)?.name !== 'AbortError') {
            toast.error('Không thể chia sẻ vé. Vui lòng thử lại.');
        }
    } finally {
        isSharing.value = false;
    }
}

onMounted(async () => {
    if (!bookingId) {
        router.replace('/home');
        return;
    }
    const { data, error } = await customerApi.getBooking(bookingId);
    isLoading.value = false;
    if (error) {
        errorMsg.value = 'Không thể tải thông tin vé.';
        return;
    }
    booking.value = data;
    if (!booking.value?.qr_code) void pollQrCode();
    store.resetBooking();
});

onBeforeUnmount(() => {
    if (qrPollTimer) clearTimeout(qrPollTimer);
});
</script>

<template>
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-12">
        <!-- Loading -->
        <div v-if="isLoading" class="flex justify-center py-20">
            <div
                class="h-8 w-8 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"
            />
        </div>

        <!-- Error -->
        <div v-else-if="errorMsg" class="py-20 text-center">
            <p class="mb-4 text-red-600">{{ errorMsg }}</p>
            <router-link
                to="/home"
                class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-medium text-white"
            >
                Về trang chủ
            </router-link>
        </div>

        <div v-else class="flex flex-col items-center">
            <!-- Success header -->
            <div class="mb-8 text-center">
                <div
                    class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-green-100"
                >
                    <svg
                        class="h-10 w-10 text-green-600"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                </div>
                <h1 class="mb-2 text-3xl font-bold text-gray-900">
                    Đặt vé thành công!
                </h1>
                <p class="text-gray-500">
                    Vé đã được gửi qua SMS và email của bạn
                </p>
            </div>

            <!-- Ticket card -->
            <div
                class="w-full max-w-lg overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xl"
            >
                <!-- Ticket header -->
                <div
                    class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/20"
                            >
                                <svg
                                    class="h-4 w-4 text-white"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm7 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM2.5 5h15l-1.5 8h-12L2.5 5z"
                                    />
                                </svg>
                            </div>
                            <span class="font-bold text-white">XeGhep.vn</span>
                        </div>
                        <span
                            class="rounded-full bg-white/10 px-2.5 py-1 text-xs font-medium text-blue-100"
                            >Vé điện tử</span
                        >
                    </div>
                    <p class="mb-1 text-xs text-blue-100">Mã đặt vé</p>
                    <p
                        class="font-mono text-2xl font-bold tracking-widest text-white"
                    >
                        {{ booking?.booking_code ?? 'HNHP000000' }}
                    </p>
                </div>

                <!-- Ticket body -->
                <div class="px-6 py-5">
                    <div class="mb-5 grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div>
                            <p class="mb-0.5 text-xs text-gray-400">
                                Tuyến đường
                            </p>
                            <p class="font-semibold text-gray-900">
                                {{ booking?.trip?.route ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="mb-0.5 text-xs text-gray-400">Ngày đi</p>
                            <p class="font-semibold text-gray-900">
                                {{
                                    booking?.trip?.depart_at
                                        ? fmtDateTime(booking.trip.depart_at)
                                        : '—'
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="mb-0.5 text-xs text-gray-400">
                                Giờ xuất phát
                            </p>
                            <p class="font-semibold text-gray-900">
                                {{
                                    booking?.trip?.depart_at
                                        ? fmtTime(booking.trip.depart_at)
                                        : '—'
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="mb-0.5 text-xs text-gray-400">Ghế</p>
                            <p class="font-semibold text-gray-900">
                                {{
                                    booking?.passengers
                                        ?.map((p: any) => p.seat_code)
                                        .filter(Boolean)
                                        .join(', ') ?? '—'
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Dashed divider with scissors -->
                    <div class="my-5 flex items-center gap-2">
                        <div
                            class="flex-1 border-t border-dashed border-gray-300"
                        />
                        <span class="text-lg text-gray-300">✂</span>
                        <div
                            class="flex-1 border-t border-dashed border-gray-300"
                        />
                    </div>

                    <div class="mb-5 space-y-3 text-sm">
                        <div class="flex items-start gap-2.5">
                            <span class="mt-0.5 text-blue-500">📍</span>
                            <div>
                                <p class="text-xs font-semibold text-gray-400">
                                    Điểm đón
                                </p>
                                <p class="mt-0.5 font-bold text-slate-800">
                                    {{ booking?.pickup_stop?.stop_name }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{
                                        booking?.pickup_address ||
                                        booking?.pickup_stop?.address
                                    }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="mt-0.5 text-green-500">🏁</span>
                            <div>
                                <p class="text-xs font-semibold text-gray-400">
                                    Điểm trả
                                </p>
                                <p class="mt-0.5 font-bold text-slate-800">
                                    {{ booking?.dropoff_stop?.stop_name }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{
                                        booking?.dropoff_address ||
                                        booking?.dropoff_stop?.address
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-5 space-y-2 text-sm">
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5">👤</span>
                            <div>
                                <p class="text-xs text-gray-400">Hành khách</p>
                                <p class="font-medium text-gray-900">
                                    {{ booking?.contact_name ?? '—' }}
                                    <span class="font-normal text-gray-400">
                                        ·
                                        {{
                                            booking?.contact_phone ?? '—'
                                        }}</span
                                    >
                                </p>
                            </div>
                        </div>
                        <div
                            v-if="booking?.trip?.driver_name || booking?.trip?.vehicle"
                            class="flex items-start gap-2"
                        >
                            <span class="mt-0.5">🚗</span>
                            <div>
                                <p class="text-xs text-gray-400">Tài xế</p>
                                <p class="font-medium text-gray-900">
                                    {{
                                        booking.trip.driver_name ?? '—'
                                    }}
                                    <span class="font-normal text-gray-400">
                                        ·
                                        {{
                                            booking.trip.vehicle?.plate ?? '—'
                                        }}</span
                                    >
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- QR Code -->
                    <div
                        class="flex flex-col items-center border-t border-gray-100 py-4"
                    >
                        <div
                            class="mb-3 flex h-36 w-36 items-center justify-center rounded-xl bg-gray-100"
                        >
                            <img
                                v-if="booking?.qr_code"
                                :src="booking.qr_code"
                                alt="QR Vé"
                                class="h-32 w-32 object-contain"
                            />
                            <div v-else class="text-center">
                                <div class="mb-1 text-4xl">📱</div>
                                <p class="text-xs text-gray-400">
                                    QR đang tạo...
                                </p>
                            </div>
                        </div>
                        <p class="text-center text-xs text-gray-500">
                            Tài xế sẽ quét mã này khi đón bạn
                        </p>
                    </div>

                    <!-- Action buttons -->
                    <div class="mt-4 flex gap-3">
                        <button
                            type="button"
                            :disabled="isDownloading"
                            @click="downloadTicket"
                            class="flex-1 rounded-lg border border-gray-300 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {{ isDownloading ? 'Đang tạo PDF...' : '📥 Tải vé PDF' }}
                        </button>
                        <button
                            type="button"
                            :disabled="isSharing"
                            @click="shareTicket"
                            class="flex-1 rounded-lg border border-gray-300 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {{ isSharing ? 'Đang chia sẻ...' : '🔗 Chia sẻ vé' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bottom actions -->
            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:gap-4">
                <router-link
                    v-if="booking?.id"
                    :to="`/customer/bookings/${booking.id}/track`"
                    class="rounded-xl bg-blue-600 px-8 py-3 font-semibold text-white shadow-sm transition-colors hover:bg-blue-700"
                >
                    📡 Theo dõi chuyến đi
                </router-link>
                <router-link
                    to="/home"
                    class="rounded-xl border border-gray-300 px-8 py-3 font-medium text-gray-700 transition-colors hover:bg-gray-50"
                >
                    Về trang chủ
                </router-link>
            </div>
        </div>
    </div>
</template>
