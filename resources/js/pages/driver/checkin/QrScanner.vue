<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { driverApi } from '@/api/driver.api'

const route   = useRoute()
const tripId  = route.params.tripId as string

type ScanState = 'idle' | 'scanning' | 'success' | 'error' | 'cash'

const scanState    = ref<ScanState>('idle')
const scanResult   = ref<any>(null)
const errorText    = ref('')
const isProcessing = ref(false)
const manualCode   = ref('')
const lastToken    = ref('')
const recentScans  = ref<Array<{ name: string; seat: string; time: string }>>([])
let html5QrCode: any = null
let scannerStarted  = false

const fmtCurrency = (v: number) => new Intl.NumberFormat('vi-VN').format(v) + 'đ'

async function handleQrResult(qrToken: string, cashCollected = false) {
  if (isProcessing.value || !qrToken.trim()) return
  isProcessing.value = true
  scanState.value    = 'scanning'

  const { data, error } = await driverApi.checkin({ qr_token: qrToken.trim(), cash_collected: cashCollected })
  isProcessing.value = false

  if (error) {
    errorText.value = typeof error === 'string' ? error : 'Mã QR không hợp lệ hoặc đã sử dụng'
    scanState.value = 'error'
    return
  }

  // Vé tiền mặt chưa thu → hiện popup thu tiền, chưa check-in
  if (data?.requires_cash) {
    scanResult.value = data
    lastToken.value  = qrToken.trim()
    scanState.value  = 'cash'
    return
  }

  scanResult.value = data
  scanState.value  = 'success'
  recentScans.value.unshift({
    name: data?.passenger_name ?? '—',
    seat: (data?.seat_codes ?? []).join(', '),
    time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
  })
  if (recentScans.value.length > 5) recentScans.value.pop()
}

// Tài xế bấm "Đã thu" → check-in kèm xác nhận thu tiền mặt
async function confirmCash() {
  await handleQrResult(lastToken.value, true)
}

async function handleManualSubmit() {
  if (!manualCode.value.trim()) return
  await handleQrResult(manualCode.value.trim())
  manualCode.value = ''
}

function resetScan() {
  scanState.value  = 'idle'
  scanResult.value = null
  errorText.value  = ''
  if (html5QrCode && scannerStarted) {
    try { html5QrCode.resume?.() } catch (_) {}
  }
}

onMounted(async () => {
  try {
    const { Html5QrcodeScanner } = await import('html5-qrcode')
    html5QrCode = new Html5QrcodeScanner(
      'qr-reader',
      { fps: 10, qrbox: { width: 240, height: 240 }, rememberLastUsedCamera: true },
      false
    )
    html5QrCode.render(
      (decodedText: string) => handleQrResult(decodedText),
      () => {}
    )
    scannerStarted = true
    scanState.value = 'idle'
  } catch (e) {
    console.warn('QR scanner unavailable:', e)
    scanState.value = 'idle'
  }
})

onUnmounted(() => {
  try { html5QrCode?.clear?.() } catch (_) {}
})
</script>

<template>
  <div class="p-6 max-w-5xl mx-auto">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-5">
      <router-link :to="`/driver/trips/${tripId}`" class="hover:text-green-600 transition-colors">← Chi tiết chuyến</router-link>
      <span>/</span>
      <span class="text-gray-700 font-medium">Quét QR check-in</span>
    </div>

    <h1 class="text-xl font-bold text-gray-900 mb-1">Quét vé hành khách</h1>
    <p class="text-sm text-gray-500 mb-6">Hướng camera vào mã QR trên vé điện tử của hành khách</p>

    <div class="grid grid-cols-[1fr_340px] gap-6">

      <!-- ─── LEFT: Scanner ─────────────────────────────────── -->
      <div class="space-y-4">

        <!-- Camera scanner card -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Camera quét QR</h2>
            <div :class="['flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full',
              scanState === 'scanning' ? 'bg-blue-100 text-blue-700' :
              scanState === 'success'  ? 'bg-green-100 text-green-700' :
              scanState === 'error'    ? 'bg-red-100 text-red-600' :
              'bg-gray-100 text-gray-500']">
              <div :class="['w-1.5 h-1.5 rounded-full',
                scanState === 'scanning' ? 'bg-blue-500 animate-pulse' :
                scanState === 'success'  ? 'bg-green-500' :
                scanState === 'error'    ? 'bg-red-500' : 'bg-gray-400']" />
              {{ scanState === 'scanning' ? 'Đang xử lý' : scanState === 'success' ? 'Thành công' : scanState === 'error' ? 'Lỗi' : 'Sẵn sàng' }}
            </div>
          </div>

          <!-- QR reader container -->
          <div class="p-4">
            <div id="qr-reader" class="w-full rounded-xl overflow-hidden" style="max-width: 480px; margin: 0 auto" />
            <p class="text-xs text-gray-400 text-center mt-3">Đưa mã QR vào khung hình để tự động quét</p>
          </div>
        </div>

        <!-- Manual input fallback -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
          <h3 class="font-semibold text-gray-700 text-sm mb-3">Hoặc nhập mã thủ công</h3>
          <div class="flex gap-2">
            <input v-model="manualCode" type="text" placeholder="Nhập mã QR token..."
              class="flex-1 h-11 px-4 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors font-mono"
              @keyup.enter="handleManualSubmit" />
            <button @click="handleManualSubmit" :disabled="!manualCode.trim() || isProcessing"
              class="px-5 py-2.5 bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white text-sm font-semibold rounded-lg transition-colors whitespace-nowrap flex items-center gap-2">
              <div v-if="isProcessing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
              <span>{{ isProcessing ? '...' : 'Xác nhận' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- ─── RIGHT: Result + recent ────────────────────────── -->
      <div class="space-y-4 self-start sticky top-6">

        <!-- Cash collection prompt -->
        <div v-if="scanState === 'cash'"
          class="bg-amber-50 border-2 border-amber-400 rounded-xl p-5">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 bg-amber-500 rounded-full flex items-center justify-center flex-shrink-0 text-2xl">💵</div>
            <div>
              <h3 class="font-bold text-amber-800 text-lg">Thu tiền mặt</h3>
              <p class="text-amber-600 text-sm">Khách thanh toán bằng tiền mặt</p>
            </div>
          </div>
          <div class="space-y-2 text-sm mb-3">
            <div class="flex justify-between">
              <span class="text-amber-700">Tên khách</span>
              <span class="font-semibold text-amber-900">{{ scanResult?.passenger_name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-amber-700">Mã vé</span>
              <span class="font-semibold text-amber-900 font-mono">{{ scanResult?.booking_code }}</span>
            </div>
          </div>
          <div class="bg-white rounded-lg p-3 mb-4 text-center border border-amber-200">
            <p class="text-xs text-amber-600 mb-0.5">Số tiền cần thu</p>
            <p class="text-2xl font-bold text-amber-700">{{ fmtCurrency(scanResult?.amount_due ?? 0) }}</p>
          </div>
          <button @click="confirmCash" :disabled="isProcessing"
            class="w-full py-3 bg-amber-600 hover:bg-amber-700 disabled:opacity-60 text-white font-bold rounded-xl transition-colors mb-2">
            {{ isProcessing ? 'Đang xử lý...' : 'Đã thu tiền & Check-in' }}
          </button>
          <button @click="resetScan" :disabled="isProcessing"
            class="w-full py-2 text-amber-700 font-medium rounded-xl hover:bg-amber-100 transition-colors text-sm">
            Huỷ
          </button>
        </div>

        <!-- Success result -->
        <div v-else-if="scanState === 'success'"
          class="bg-green-50 border-2 border-green-400 rounded-xl p-5">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <div>
              <h3 class="font-bold text-green-800 text-lg">Check-in thành công!</h3>
              <p class="text-green-600 text-sm">Hành khách đã được xác nhận</p>
            </div>
          </div>
          <div class="space-y-2 text-sm mb-4">
            <div class="flex justify-between">
              <span class="text-green-700">Tên khách</span>
              <span class="font-semibold text-green-900">{{ scanResult?.passenger_name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-green-700">Số ghế</span>
              <span class="font-semibold text-green-900 font-mono">{{ (scanResult?.seat_codes ?? []).join(', ') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-green-700">Điểm đón</span>
              <span class="font-semibold text-green-900 text-right max-w-[60%]">{{ scanResult?.pickup_stop?.stop_name }}</span>
            </div>
            <div v-if="scanResult?.cash_collected" class="flex justify-between pt-2 border-t border-green-200">
              <span class="text-green-700">💵 Đã thu tiền mặt</span>
              <span class="font-bold text-green-900">{{ fmtCurrency(scanResult.cash_collected) }}</span>
            </div>
          </div>
          <button @click="resetScan"
            class="w-full py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition-colors">
            Quét tiếp →
          </button>
        </div>

        <!-- Error result -->
        <div v-else-if="scanState === 'error'"
          class="bg-red-50 border-2 border-red-300 rounded-xl p-5">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
            <div>
              <h3 class="font-bold text-red-800">Không hợp lệ</h3>
              <p class="text-red-600 text-sm">{{ errorText }}</p>
            </div>
          </div>
          <button @click="resetScan"
            class="w-full py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition-colors text-sm">
            Thử lại
          </button>
        </div>

        <!-- Idle placeholder -->
        <div v-else class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 text-center">
          <div class="text-4xl mb-3">📷</div>
          <p class="font-medium text-gray-600">Sẵn sàng quét</p>
          <p class="text-sm text-gray-400 mt-1">Kết quả check-in sẽ hiển thị ở đây</p>
        </div>

        <!-- Recent scans -->
        <div v-if="recentScans.length > 0" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700 text-sm">Đã quét gần đây</h3>
          </div>
          <div class="divide-y divide-gray-100">
            <div v-for="(scan, i) in recentScans" :key="i"
              class="flex items-center gap-3 px-4 py-3">
              <div class="w-7 h-7 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ scan.name }}</p>
                <p class="text-xs text-gray-400 font-mono">Ghế {{ scan.seat }}</p>
              </div>
              <span class="text-xs text-gray-400 shrink-0">{{ scan.time }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
