<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { customerApi } from '@/api/customer.api'
import { useCustomerStore } from '@/stores/customer.store'
import { useCustomerAuthStore } from '@/stores/customer.auth.store'

const router = useRouter()
const store  = useCustomerStore()
const auth   = useCustomerAuthStore()
const draft  = store.bookingDraft

const selectedMethod = ref<'momo' | 'vnpay' | 'wallet' | 'cash'>('momo')
const isLoading      = ref(false)
const errorMsg       = ref('')
const walletBalance  = ref(store.walletBalance)
const bookingData    = ref<any>(null)
const loadingBooking = ref(true)

const paySeconds = ref(900)
let countdown: ReturnType<typeof setInterval> | null = null
const countdownLabel = computed(() => {
  const m = Math.floor(paySeconds.value / 60)
  const s = paySeconds.value % 60
  return `${m}:${s.toString().padStart(2, '0')}`
})
const countdownUrgent = computed(() => paySeconds.value < 180)

const subtotal = computed(() => draft.seats.reduce((sum, s) => sum + s.price, 0))
const total    = computed(() => Math.max(0, subtotal.value - draft.voucher_discount))

function fmt(v: number) { return new Intl.NumberFormat('vi-VN').format(v) + 'đ' }

const paymentMethods = computed(() => [
  {
    key: 'momo' as const,
    label: 'Ví MoMo',
    desc: 'Quét mã QR hoặc nhập số điện thoại MoMo',
    badge: 'Phổ biến nhất',
    badgeColor: 'bg-green-100 text-green-700',
    icon: '💜',
    disabled: false,
  },
  {
    key: 'vnpay' as const,
    label: 'VNPay',
    desc: 'Thẻ ATM nội địa / Visa / Mastercard / QR Code',
    badge: null,
    badgeColor: '',
    icon: '🏦',
    disabled: false,
  },
  {
    key: 'wallet' as const,
    label: 'Ví XeGhep',
    desc: walletBalance.value >= total.value
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
])

async function pay() {
  if (isLoading.value) return
  isLoading.value = true
  errorMsg.value = ''
  const bookingId = store.currentBookingId
  if (!bookingId) {
    errorMsg.value = 'Không tìm thấy thông tin đặt vé. Vui lòng thử lại.'
    isLoading.value = false
    return
  }
  const { data, error } = await customerApi.initiatePayment({
    booking_id: bookingId,
    method: selectedMethod.value,
  })
  isLoading.value = false
  if (error) { errorMsg.value = error as string; return }
  if (selectedMethod.value === 'cash' || selectedMethod.value === 'wallet') {
    router.push(`/booking/${bookingId}/confirmation`)
    return
  }
  if (data?.payment_url) {
    window.location.href = data.payment_url
  } else {
    errorMsg.value = 'Không thể khởi tạo thanh toán. Vui lòng thử lại.'
  }
}

onMounted(async () => {
  if (!store.currentBookingId) { router.replace('/home'); return }
  const { data } = await customerApi.getBooking(store.currentBookingId)
  loadingBooking.value = false
  bookingData.value = data
  const { data: wallet } = await customerApi.getWallet()
  if (wallet) { walletBalance.value = wallet.balance; store.walletBalance = wallet.balance }
  countdown = setInterval(() => {
    if (paySeconds.value > 0) paySeconds.value--
    else { clearInterval(countdown!); router.replace('/home') }
  }, 1000)
})

onUnmounted(() => { if (countdown) clearInterval(countdown) })
</script>

<template>
  <div class="max-w-5xl mx-auto px-6 py-8">
    <!-- Step indicator -->
    <div class="flex items-center justify-center gap-2 mb-8">
      <div v-for="(step, i) in ['Chọn ghế', 'Thông tin', 'Thanh toán']" :key="i"
        class="flex items-center gap-2">
        <div :class="['w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold',
          i < 2 ? 'bg-blue-100 text-blue-400' : 'bg-blue-600 text-white']">
          <svg v-if="i < 2" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <span v-else>3</span>
        </div>
        <span :class="['text-sm font-medium', i === 2 ? 'text-blue-700' : 'text-gray-400']">{{ step }}</span>
        <span v-if="i < 2" class="text-gray-300 mx-1">→</span>
      </div>
    </div>

    <div class="grid grid-cols-[1fr_340px] gap-8">
      <!-- ─── LEFT: Payment Methods ──────────────────── -->
      <div class="space-y-4">
        <h2 class="text-lg font-bold text-gray-900">Chọn phương thức thanh toán</h2>

        <!-- Countdown banner -->
        <div :class="['flex items-center justify-between p-4 rounded-xl border',
          countdownUrgent ? 'bg-red-50 border-red-200' : 'bg-orange-50 border-orange-200']">
          <div class="flex items-center gap-2">
            <span class="text-lg">⏳</span>
            <span :class="['text-sm font-medium', countdownUrgent ? 'text-red-700' : 'text-orange-800']">
              Đơn hàng sẽ hết hạn sau
            </span>
          </div>
          <span :class="['text-lg font-bold tabular-nums', countdownUrgent ? 'text-red-600' : 'text-orange-600']">
            {{ countdownLabel }}
          </span>
        </div>
        <p v-if="countdownUrgent" class="text-xs text-red-600 font-medium -mt-2">
          Vui lòng hoàn tất thanh toán ngay để không mất ghế!
        </p>

        <!-- Payment method cards -->
        <div class="space-y-3">
          <label v-for="method in paymentMethods" :key="method.key"
            :class="['flex items-center gap-4 p-4 rounded-xl border-2 transition-all',
              method.disabled ? 'opacity-50 cursor-not-allowed border-gray-200 bg-gray-50' :
              selectedMethod === method.key
                ? 'border-blue-600 bg-blue-50 shadow-sm cursor-pointer'
                : 'border-gray-200 bg-white hover:border-gray-300 cursor-pointer']">
            <input type="radio" name="payment" :value="method.key" v-model="selectedMethod"
              :disabled="method.disabled"
              class="w-4 h-4 border-gray-300 text-blue-600 focus:ring-blue-500" />
            <div class="text-2xl shrink-0">{{ method.icon }}</div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-gray-900 text-sm">{{ method.label }}</p>
              <p class="text-xs text-gray-500 mt-0.5">{{ method.desc }}</p>
            </div>
            <div class="shrink-0 flex items-center gap-2">
              <span v-if="method.badge"
                :class="['text-xs font-semibold px-2.5 py-1 rounded-full', method.badgeColor]">
                {{ method.badge }}
              </span>
              <span v-if="selectedMethod === method.key && !method.disabled"
                class="text-xs font-semibold text-blue-600 bg-blue-100 px-2.5 py-1 rounded-full">
                Đã chọn ✓
              </span>
            </div>
          </label>
        </div>

        <div v-if="errorMsg" class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
          {{ errorMsg }}
        </div>

        <button @click="pay" :disabled="isLoading"
          class="w-full py-4 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-bold text-base rounded-xl transition-colors flex items-center justify-center gap-3 shadow-lg shadow-blue-100">
          <div v-if="isLoading" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
          <span>{{ isLoading ? 'Đang xử lý...' : 'Xác nhận & Thanh toán' }}</span>
          <span v-if="!isLoading">→</span>
        </button>

        <p class="text-xs text-gray-400 text-center">🔒 Thông tin thanh toán được mã hóa SSL 256-bit</p>
      </div>

      <!-- ─── RIGHT: Order Summary ────────────────────── -->
      <div class="sticky top-20">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
          <h3 class="font-semibold text-gray-900 mb-4">Tóm tắt đơn hàng</h3>
          <div v-if="bookingData" class="space-y-2.5 text-sm mb-4 pb-4 border-b border-gray-100">
            <div class="flex justify-between">
              <span class="text-gray-500">Mã đặt vé</span>
              <span class="font-mono font-bold text-gray-900 text-xs">{{ bookingData.booking_code }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Tuyến</span>
              <span class="font-medium text-gray-900">Hà Nội → Hải Phòng</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Ghế</span>
              <span class="font-medium">{{ draft.seat_codes.join(', ') }}</span>
            </div>
          </div>
          <div v-else-if="loadingBooking" class="animate-pulse space-y-2 mb-4 pb-4 border-b border-gray-100">
            <div class="h-4 bg-gray-200 rounded w-full" />
            <div class="h-4 bg-gray-200 rounded w-3/4" />
          </div>
          <div class="space-y-2 text-sm mb-4">
            <div class="flex justify-between">
              <span class="text-gray-500">Tạm tính</span>
              <span>{{ fmt(subtotal) }}</span>
            </div>
            <div v-if="draft.voucher_discount > 0" class="flex justify-between text-green-600">
              <span>Giảm giá</span>
              <span>–{{ fmt(draft.voucher_discount) }}</span>
            </div>
          </div>
          <div class="flex justify-between py-3 border-t border-gray-200">
            <span class="font-bold text-gray-900">Tổng thanh toán</span>
            <span class="text-2xl font-bold text-blue-600 tabular-nums">{{ fmt(total) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
