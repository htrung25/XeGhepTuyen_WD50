<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { customerApi } from '@/api/customer.api'
import { useCustomerStore, type SeatInfo } from '@/stores/customer.store'
import { useCustomerAuthStore } from '@/stores/customer.auth.store'

const route  = useRoute()
const router = useRouter()
const store  = useCustomerStore()
const auth   = useCustomerAuthStore()

const tripId      = route.params.id as string
const seats       = ref<SeatInfo[]>([])
const isLoading   = ref(true)
const errorMsg    = ref('')
const selected    = ref<string[]>([])
const lockLoading = ref(false)
const tripInfo    = ref<any>(null)

const maxSeats = computed(() => store.searchParams.passengers || 1)

function seatClasses(s: SeatInfo) {
  if (s.status === 'driver')    return 'bg-gray-200 text-gray-400 cursor-not-allowed border-gray-200'
  if (s.status === 'booked')    return 'bg-red-100 text-red-400 cursor-not-allowed border-red-200'
  if (s.status === 'locked')    return 'bg-yellow-100 text-yellow-600 cursor-not-allowed border-yellow-300'
  if (selected.value.includes(s.seat_code))
    return 'bg-blue-600 text-white border-blue-600 shadow-md'
  return 'bg-white text-gray-700 border-gray-300 hover:border-blue-400 hover:bg-blue-50 cursor-pointer'
}

function toggleSeat(s: SeatInfo) {
  if (s.status !== 'available') return
  const idx = selected.value.indexOf(s.seat_code)
  if (idx >= 0) {
    selected.value.splice(idx, 1)
  } else if (selected.value.length < maxSeats.value) {
    selected.value.push(s.seat_code)
  }
}

const seatGrid = computed(() => {
  const rows: SeatInfo[][] = []
  const seatList = seats.value.filter(s => s.status !== 'driver')
  for (let i = 0; i < seatList.length; i += 2) {
    rows.push(seatList.slice(i, i + 2))
  }
  return rows
})

const selectedSeats = computed(() =>
  seats.value.filter(s => selected.value.includes(s.seat_code))
)

const totalPrice = computed(() =>
  selectedSeats.value.reduce((sum, s) => sum + s.price, 0)
)

function fmt(v: number) {
  return new Intl.NumberFormat('vi-VN').format(v) + 'đ'
}

async function proceedToCheckout() {
  if (!auth.isAuthenticated) {
    router.push({ path: '/login', query: { redirect: route.fullPath } })
    return
  }
  if (selected.value.length === 0) return

  lockLoading.value = true
  const { error } = await customerApi.lockSeats({ trip_id: tripId, seat_ids: selectedSeats.value.map(s => s.id) })
  lockLoading.value = false
  if (error) { errorMsg.value = 'Không thể giữ ghế. Vui lòng thử lại.'; return }

  store.bookingDraft.seats = selectedSeats.value
  store.bookingDraft.seat_codes = selected.value
  router.push('/booking/checkout')
}

let echoChannel: any = null

onMounted(async () => {
  // Đảm bảo draft luôn có trip_id (hỗ trợ vào thẳng link /trips/:id/seats,
  // không qua trang kết quả tìm kiếm) — nếu thiếu, Checkout sẽ đá về /home.
  store.bookingDraft.trip_id = tripId

  isLoading.value = true
  const [seatsRes, tripRes] = await Promise.all([
    customerApi.getTripSeats(tripId),
    customerApi.getPublicTrip(tripId),
  ])
  isLoading.value = false

  if (seatsRes.error) { errorMsg.value = 'Không thể tải sơ đồ ghế.'; return }
  seats.value    = seatsRes.data ?? []
  tripInfo.value = tripRes.data ?? null

  // WebSocket real-time seat updates
  if ((window as any).Echo) {
    echoChannel = (window as any).Echo.channel(`trips.${tripId}`)
      .listen('.seat.status.updated', (e: any) => {
        const seat = seats.value.find(s => s.id === e.seat_id)
        if (seat) {
          seat.status = e.status
          if (e.status !== 'available' && selected.value.includes(seat.seat_code)) {
            selected.value = selected.value.filter(c => c !== seat.seat_code)
          }
        }
      })
  }
})

onUnmounted(() => {
  if (echoChannel) (window as any).Echo?.leave(`trips.${tripId}`)
})
</script>

<template>
  <div class="max-w-5xl mx-auto px-6 py-8">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
      <router-link to="/home" class="hover:text-blue-600 transition-colors">Trang chủ</router-link>
      <span>›</span>
      <router-link to="/search" class="hover:text-blue-600 transition-colors">Kết quả</router-link>
      <span>›</span>
      <span class="text-gray-900 font-medium">Chọn ghế</span>
    </nav>

    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-24">
      <div class="flex flex-col items-center gap-3 text-gray-500">
        <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin" />
        <span class="text-sm">Đang tải sơ đồ ghế...</span>
      </div>
    </div>

    <!-- Error -->
    <div v-else-if="errorMsg && seats.length === 0"
      class="bg-red-50 border border-red-200 rounded-xl p-6 text-red-700 text-center">
      <p class="font-medium mb-3">{{ errorMsg }}</p>
      <button @click="$router.back()" class="px-5 py-2 border border-red-300 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors">
        ← Quay lại
      </button>
    </div>

    <div v-else class="grid grid-cols-[1fr_340px] gap-8">
      <!-- ─── LEFT: Trip info + Seat map ────────────── -->
      <div class="space-y-6">
        <!-- Trip info card -->
        <div v-if="tripInfo" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center font-bold text-blue-700 text-lg">
              {{ tripInfo.driver?.full_name?.charAt(0) ?? 'T' }}
            </div>
            <div>
              <p class="font-semibold text-gray-900">{{ tripInfo.driver?.full_name ?? 'Tài xế' }}</p>
              <div class="flex items-center gap-1 mt-0.5">
                <svg class="w-3.5 h-3.5 text-yellow-400 fill-yellow-400" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <span class="text-xs text-gray-600 font-medium">{{ tripInfo.driver?.rating_avg?.toFixed(1) ?? '4.8' }} sao</span>
                <span class="text-xs text-gray-400">·</span>
                <span class="text-xs text-gray-500">{{ tripInfo.vehicle?.plate_number ?? '30A-12345' }}</span>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-2 flex-wrap">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 text-xs rounded-md font-medium">📶 WiFi</span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 text-xs rounded-md font-medium">❄️ Điều hòa</span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 text-xs rounded-md font-medium">🔌 Cổng USB</span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 text-xs rounded-md font-medium">💧 Nước uống</span>
          </div>
        </div>

        <!-- Seat map -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
          <h3 class="font-semibold text-gray-900 mb-1">Sơ đồ ghế</h3>
          <p class="text-xs text-gray-500 mb-5">
            Chọn tối đa {{ maxSeats }} ghế. Click vào ghế trống để chọn.
          </p>

          <!-- Loading skeleton -->
          <div v-if="isLoading" class="flex flex-col gap-3 items-center">
            <div v-for="i in 4" :key="i" class="flex gap-3">
              <div class="w-14 h-10 bg-gray-200 rounded-lg animate-pulse" />
              <div class="w-14 h-10 bg-gray-200 rounded-lg animate-pulse" />
            </div>
          </div>

          <!-- Car visual -->
          <div v-else class="flex flex-col items-center gap-2">
            <!-- Driver row -->
            <div class="w-full max-w-xs flex items-center justify-between mb-2">
              <div class="w-14 h-10 bg-gray-200 text-gray-400 rounded-lg border border-gray-200
                          flex items-center justify-center text-xs font-medium">
                Tài xế
              </div>
              <div class="text-xs text-gray-400 italic">Đầu xe</div>
            </div>

            <!-- Seat rows -->
            <div v-for="(row, ri) in seatGrid" :key="ri" class="flex gap-4 w-full max-w-xs justify-center">
              <button v-for="seat in row" :key="seat.seat_code"
                @click="toggleSeat(seat)"
                :disabled="seat.status !== 'available'"
                :class="['w-14 h-12 rounded-lg border-2 text-sm font-bold transition-all', seatClasses(seat)]">
                {{ seat.seat_code }}
              </button>
              <!-- Fill empty if odd -->
              <div v-if="row.length < 2" class="w-14" />
            </div>
          </div>

          <!-- Legend -->
          <div class="flex flex-wrap items-center gap-4 mt-6 pt-5 border-t border-gray-100">
            <div class="flex items-center gap-2 text-xs text-gray-600">
              <div class="w-6 h-5 bg-white border-2 border-gray-300 rounded" />
              Trống
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-600">
              <div class="w-6 h-5 bg-blue-600 border-2 border-blue-600 rounded" />
              Đã chọn
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-600">
              <div class="w-6 h-5 bg-red-100 border-2 border-red-200 rounded" />
              Đã đặt
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-600">
              <div class="w-6 h-5 bg-yellow-100 border-2 border-yellow-300 rounded" />
              Đang giữ
            </div>
          </div>

          <p class="text-xs text-gray-400 mt-2 italic">
            Ghế màu vàng đang được người khác giữ tạm, sẽ tự giải phóng sau vài phút nếu họ không thanh toán.
          </p>
        </div>
      </div>

      <!-- ─── RIGHT: Order Summary ───────────────────── -->
      <div class="sticky top-20">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
          <h3 class="font-semibold text-gray-900 mb-4">Thông tin chuyến đi</h3>

          <div v-if="tripInfo" class="space-y-3 mb-4 pb-4 border-b border-gray-100">
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-500">Tuyến</span>
              <span class="font-medium text-gray-900">
                {{ tripInfo.route?.origin_city ?? 'Hà Nội' }} → {{ tripInfo.route?.dest_city ?? 'Hải Phòng' }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-500">Ngày</span>
              <span class="font-medium text-gray-900">
                {{ tripInfo.depart_at ? new Date(tripInfo.depart_at).toLocaleDateString('vi-VN', { weekday: 'short', day: '2-digit', month: '2-digit', year: 'numeric' }) : '—' }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-500">Giờ</span>
              <span class="font-medium text-gray-900">
                {{ tripInfo.depart_at ? new Date(tripInfo.depart_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false }) : '—' }}
              </span>
            </div>
          </div>

          <!-- Selected seats -->
          <div class="mb-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Ghế đã chọn</p>
            <div v-if="selected.length === 0"
              class="text-sm text-gray-400 italic py-2">Chưa chọn ghế nào</div>
            <div v-else class="flex flex-wrap gap-2">
              <span v-for="code in selected" :key="code"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-100 text-blue-800 text-sm font-semibold rounded-lg">
                {{ code }}
                <button @click="selected = selected.filter(c => c !== code)"
                  class="w-4 h-4 rounded-full bg-blue-200 hover:bg-blue-300 flex items-center justify-center text-xs font-bold transition-colors">
                  ×
                </button>
              </span>
            </div>
          </div>

          <!-- Price summary -->
          <div class="bg-gray-50 rounded-xl p-4 mb-4">
            <div class="flex items-center justify-between text-sm mb-1">
              <span class="text-gray-500">
                {{ tripInfo?.price ? new Intl.NumberFormat('vi-VN').format(tripInfo.price) + 'đ' : '—' }} × {{ selected.length }} ghế
              </span>
              <span class="font-semibold text-gray-900">
                {{ totalPrice > 0 ? new Intl.NumberFormat('vi-VN').format(totalPrice) + 'đ' : '—' }}
              </span>
            </div>
          </div>

          <!-- Error -->
          <div v-if="errorMsg"
            class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-xs">
            {{ errorMsg }}
          </div>

          <!-- CTA -->
          <button @click="proceedToCheckout"
            :disabled="selected.length === 0 || lockLoading"
            class="w-full py-3.5 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed text-white font-bold text-sm rounded-xl transition-colors flex items-center justify-center gap-2">
            <div v-if="lockLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            <span>{{ lockLoading ? 'Đang giữ ghế...' : 'Tiếp tục đặt vé' }}</span>
            <span v-if="!lockLoading">→</span>
          </button>

          <p class="text-xs text-gray-400 text-center mt-3">
            Ghế sẽ được giữ 10 phút trong lúc bạn điền thông tin đặt vé
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
