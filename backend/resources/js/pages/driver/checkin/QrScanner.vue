<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { driverApi } from '@/api/driver.api';

const route = useRoute();
const tripId = route.params.tripId as string;

type ScanState = 'idle' | 'scanning' | 'success' | 'error' | 'cash';

const scanState = ref<ScanState>('idle');
const scanResult = ref<any>(null);
const errorText = ref('');
const isProcessing = ref(false);
const manualCode = ref('');
const lastToken = ref('');
const recentScans = ref<Array<{ name: string; seat: string; time: string }>>(
    [],
);
let html5QrCode: any = null;
let scannerStarted = false;

const fmtCurrency = (v: number) =>
    new Intl.NumberFormat('vi-VN').format(v) + 'đ';

async function handleQrResult(qrToken: string, cashCollected = false) {
    if (isProcessing.value || !qrToken.trim()) return;
    isProcessing.value = true;
    scanState.value = 'scanning';

    const { data, error } = await driverApi.checkin({
        qr_token: qrToken.trim(),
        cash_collected: cashCollected,
    });
    isProcessing.value = false;

    if (error) {
        errorText.value =
            typeof error === 'string'
                ? error
                : 'Mã QR không hợp lệ hoặc đã sử dụng';
        scanState.value = 'error';
        return;
    }

    // Vé tiền mặt chưa thu → hiện popup thu tiền, chưa check-in
    if (data?.requires_cash) {
        scanResult.value = data;
        lastToken.value = qrToken.trim();
        scanState.value = 'cash';
        return;
    }

    scanResult.value = data;
    scanState.value = 'success';
    recentScans.value.unshift({
        name: data?.passenger_name ?? '—',
        seat: (data?.seat_codes ?? []).join(', '),
        time: new Date().toLocaleTimeString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit',
        }),
    });
    if (recentScans.value.length > 5) recentScans.value.pop();
}

// Tài xế bấm "Đã thu" → check-in kèm xác nhận thu tiền mặt
async function confirmCash() {
    await handleQrResult(lastToken.value, true);
}

async function handleManualSubmit() {
    if (!manualCode.value.trim()) return;
    await handleQrResult(manualCode.value.trim());
    manualCode.value = '';
}

function resetScan() {
    scanState.value = 'idle';
    scanResult.value = null;
    errorText.value = '';
    if (html5QrCode && scannerStarted) {
        try {
            html5QrCode.resume?.();
        } catch {}
    }
}

onMounted(async () => {
    try {
        const { Html5QrcodeScanner } = await import('html5-qrcode');
        html5QrCode = new Html5QrcodeScanner(
            'qr-reader',
            {
                fps: 10,
                qrbox: { width: 240, height: 240 },
                rememberLastUsedCamera: true,
            },
            false,
        );
        html5QrCode.render(
            (decodedText: string) => handleQrResult(decodedText),
            () => {},
        );
        scannerStarted = true;
        scanState.value = 'idle';
    } catch (e) {
        console.warn('QR scanner unavailable:', e);
        scanState.value = 'idle';
    }
});

onUnmounted(() => {
    try {
        html5QrCode?.clear?.();
    } catch {}
});
</script>

<template>
    <div class="mx-auto max-w-5xl p-6">
        <!-- Breadcrumb -->
        <div class="mb-5 flex items-center gap-2 text-sm text-gray-500">
            <router-link
                :to="`/driver/trips/${tripId}`"
                class="transition-colors hover:text-green-600"
                >← Chi tiết chuyến</router-link
            >
            <span>/</span>
            <span class="font-medium text-gray-700">Quét QR check-in</span>
        </div>

        <h1 class="mb-1 text-xl font-bold text-gray-900">Quét vé hành khách</h1>
        <p class="mb-6 text-sm text-gray-500">
            Hướng camera vào mã QR trên vé điện tử của hành khách
        </p>

        <div class="grid grid-cols-[1fr_340px] gap-6">
            <!-- ─── LEFT: Scanner ─────────────────────────────────── -->
            <div class="space-y-4">
                <!-- Camera scanner card -->
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <div
                        class="flex items-center justify-between border-b border-gray-100 px-5 py-3"
                    >
                        <h2 class="font-semibold text-gray-900">
                            Camera quét QR
                        </h2>
                        <div
                            :class="[
                                'flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium',
                                scanState === 'scanning'
                                    ? 'bg-blue-100 text-blue-700'
                                    : scanState === 'success'
                                      ? 'bg-green-100 text-green-700'
                                      : scanState === 'error'
                                        ? 'bg-red-100 text-red-600'
                                        : 'bg-gray-100 text-gray-500',
                            ]"
                        >
                            <div
                                :class="[
                                    'h-1.5 w-1.5 rounded-full',
                                    scanState === 'scanning'
                                        ? 'animate-pulse bg-blue-500'
                                        : scanState === 'success'
                                          ? 'bg-green-500'
                                          : scanState === 'error'
                                            ? 'bg-red-500'
                                            : 'bg-gray-400',
                                ]"
                            />
                            {{
                                scanState === 'scanning'
                                    ? 'Đang xử lý'
                                    : scanState === 'success'
                                      ? 'Thành công'
                                      : scanState === 'error'
                                        ? 'Lỗi'
                                        : 'Sẵn sàng'
                            }}
                        </div>
                    </div>

                    <!-- QR reader container -->
                    <div class="p-4">
                        <div
                            id="qr-reader"
                            class="w-full overflow-hidden rounded-xl"
                            style="max-width: 480px; margin: 0 auto"
                        />
                        <p class="mt-3 text-center text-xs text-gray-400">
                            Đưa mã QR vào khung hình để tự động quét
                        </p>
                    </div>
                </div>

                <!-- Manual input fallback -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <h3 class="mb-3 text-sm font-semibold text-gray-700">
                        Hoặc nhập mã thủ công
                    </h3>
                    <div class="flex gap-2">
                        <input
                            v-model="manualCode"
                            type="text"
                            placeholder="Nhập mã QR token..."
                            class="h-11 flex-1 rounded-lg border border-gray-300 px-4 font-mono text-sm transition-colors focus:ring-2 focus:ring-green-500 focus:outline-none"
                            @keyup.enter="handleManualSubmit"
                        />
                        <button
                            @click="handleManualSubmit"
                            :disabled="!manualCode.trim() || isProcessing"
                            class="flex items-center gap-2 rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold whitespace-nowrap text-white transition-colors hover:bg-green-700 disabled:opacity-60"
                        >
                            <div
                                v-if="isProcessing"
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                            />
                            <span>{{ isProcessing ? '...' : 'Xác nhận' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─── RIGHT: Result + recent ────────────────────────── -->
            <div class="sticky top-6 space-y-4 self-start">
                <!-- Cash collection prompt -->
                <div
                    v-if="scanState === 'cash'"
                    class="rounded-xl border-2 border-amber-400 bg-amber-50 p-5"
                >
                    <div class="mb-4 flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-amber-500 text-2xl"
                        >
                            💵
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-amber-800">
                                Thu tiền mặt
                            </h3>
                            <p class="text-sm text-amber-600">
                                Khách thanh toán bằng tiền mặt
                            </p>
                        </div>
                    </div>
                    <div class="mb-3 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-amber-700">Tên khách</span>
                            <span class="font-semibold text-amber-900">{{
                                scanResult?.passenger_name
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-amber-700">Mã vé</span>
                            <span
                                class="font-mono font-semibold text-amber-900"
                                >{{ scanResult?.booking_code }}</span
                            >
                        </div>
                    </div>
                    <div
                        class="mb-4 rounded-lg border border-amber-200 bg-white p-3 text-center"
                    >
                        <p class="mb-0.5 text-xs text-amber-600">
                            Số tiền cần thu
                        </p>
                        <p class="text-2xl font-bold text-amber-700">
                            {{ fmtCurrency(scanResult?.amount_due ?? 0) }}
                        </p>
                    </div>
                    <button
                        @click="confirmCash"
                        :disabled="isProcessing"
                        class="mb-2 w-full rounded-xl bg-amber-600 py-3 font-bold text-white transition-colors hover:bg-amber-700 disabled:opacity-60"
                    >
                        {{
                            isProcessing
                                ? 'Đang xử lý...'
                                : 'Đã thu tiền & Check-in'
                        }}
                    </button>
                    <button
                        @click="resetScan"
                        :disabled="isProcessing"
                        class="w-full rounded-xl py-2 text-sm font-medium text-amber-700 transition-colors hover:bg-amber-100"
                    >
                        Huỷ
                    </button>
                </div>

                <!-- Success result -->
                <div
                    v-else-if="scanState === 'success'"
                    class="rounded-xl border-2 border-green-400 bg-green-50 p-5"
                >
                    <div class="mb-4 flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-green-500"
                        >
                            <svg
                                class="h-7 w-7 text-white"
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
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-green-800">
                                Check-in thành công!
                            </h3>
                            <p class="text-sm text-green-600">
                                Hành khách đã được xác nhận
                            </p>
                        </div>
                    </div>
                    <div class="mb-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-green-700">Tên khách</span>
                            <span class="font-semibold text-green-900">{{
                                scanResult?.passenger_name
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-green-700">Số ghế</span>
                            <span
                                class="font-mono font-semibold text-green-900"
                                >{{
                                    (scanResult?.seat_codes ?? []).join(', ')
                                }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-green-700">Điểm đón</span>
                            <span
                                class="max-w-[60%] text-right font-semibold text-green-900"
                                >{{ scanResult?.pickup_stop?.stop_name }}</span
                            >
                        </div>
                        <div
                            v-if="scanResult?.cash_collected"
                            class="flex justify-between border-t border-green-200 pt-2"
                        >
                            <span class="text-green-700"
                                >💵 Đã thu tiền mặt</span
                            >
                            <span class="font-bold text-green-900">{{
                                fmtCurrency(scanResult.cash_collected)
                            }}</span>
                        </div>
                    </div>
                    <button
                        @click="resetScan"
                        class="w-full rounded-xl bg-green-600 py-3 font-bold text-white transition-colors hover:bg-green-700"
                    >
                        Quét tiếp →
                    </button>
                </div>

                <!-- Error result -->
                <div
                    v-else-if="scanState === 'error'"
                    class="rounded-xl border-2 border-red-300 bg-red-50 p-5"
                >
                    <div class="mb-3 flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100"
                        >
                            <svg
                                class="h-5 w-5 text-red-600"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="3"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-red-800">Không hợp lệ</h3>
                            <p class="text-sm text-red-600">{{ errorText }}</p>
                        </div>
                    </div>
                    <button
                        @click="resetScan"
                        class="w-full rounded-xl bg-red-600 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-700"
                    >
                        Thử lại
                    </button>
                </div>

                <!-- Idle placeholder -->
                <div
                    v-else
                    class="rounded-xl border border-gray-200 bg-white p-6 text-center shadow-sm"
                >
                    <div class="mb-3 text-4xl">📷</div>
                    <p class="font-medium text-gray-600">Sẵn sàng quét</p>
                    <p class="mt-1 text-sm text-gray-400">
                        Kết quả check-in sẽ hiển thị ở đây
                    </p>
                </div>

                <!-- Recent scans -->
                <div
                    v-if="recentScans.length > 0"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <div class="border-b border-gray-100 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-700">
                            Đã quét gần đây
                        </h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="(scan, i) in recentScans"
                            :key="i"
                            class="flex items-center gap-3 px-4 py-3"
                        >
                            <div
                                class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-green-100"
                            >
                                <svg
                                    class="h-3.5 w-3.5 text-green-600"
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
                            </div>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-medium text-gray-900"
                                >
                                    {{ scan.name }}
                                </p>
                                <p class="font-mono text-xs text-gray-400">
                                    Ghế {{ scan.seat }}
                                </p>
                            </div>
                            <span class="shrink-0 text-xs text-gray-400">{{
                                scan.time
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
