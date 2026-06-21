<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { customerApi } from '@/api/customer.api'
import { useCustomerStore } from '@/stores/customer.store'

const route  = useRoute()
const router = useRouter()
const store  = useCustomerStore()

const bookingId = route.params.id as string || store.currentBookingId
const booking   = ref<any>(null)
const isLoading = ref(true)
const errorMsg  = ref('')

function fmt(v: number) { return new Intl.NumberFormat('vi-VN').format(v) + 'đ' }

function fmtDateTime(iso: string) {
  const d = new Date(iso)
  const days = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy']
  return `${days[d.getDay()]}, ${d.toLocaleDateString('vi-VN')}`
}

function fmtTime(iso: string) {
  return new Date(iso).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false })
}

onMounted(async () => {
  if (!bookingId) { router.replace('/home'); return }
  const { data, error } = await customerApi.getBooking(bookingId)
  isLoading.value = false
  if (error) { errorMsg.value = 'Không thể tải thông tin vé.'; return }
  booking.value = data
  store.resetBooking()
})
</script>

<template>
  <div class="max-w-5xl mx-auto px-6 py-12">
    <!-- Loading -->
    <div v-if="isLoading" class="flex justify-center py-20">
      <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin" />
    </div>

    <!-- Error -->
    <div v-else-if="errorMsg" class="text-center py-20">
      <p class="text-red-600 mb-4">{{ errorMsg }}</p>
      <router-link to="/home" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium">
        Về trang chủ
      </router-link>
    </div>

    <div v-else class="flex flex-col items-center">
      <!-- Success header -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Đặt vé thành công!</h1>
        <p class="text-gray-500">Vé đã được gửi qua SMS và email của bạn</p>
      </div>

      <!-- Ticket card -->
      <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <!-- Ticket header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm7 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM2.5 5h15l-1.5 8h-12L2.5 5z"/>
                </svg>
              </div>
              <span class="text-white font-bold">XeGhep.vn</span>
            </div>
            <span class="text-blue-100 text-xs font-medium px-2.5 py-1 bg-white/10 rounded-full">Vé điện tử</span>
          </div>
          <p class="text-blue-100 text-xs mb-1">Mã đặt vé</p>
          <p class="text-white font-mono text-2xl font-bold tracking-widest">
            {{ booking?.booking_code ?? 'HNHP000000' }}
          </p>
        </div>

        <!-- Ticket body -->
        <div class="px-6 py-5">
          <div class="grid grid-cols-2 gap-y-3 gap-x-6 text-sm mb-5">
            <div>
              <p class="text-xs text-gray-400 mb-0.5">Tuyến đường</p>
              <p class="font-semibold text-gray-900">Hà Nội → Hải Phòng</p>
            </div>
            <div>
              <p class="text-xs text-gray-400 mb-0.5">Ngày đi</p>
              <p class="font-semibold text-gray-900">
                {{ booking?.trip?.depart_at ? fmtDateTime(booking.trip.depart_at) : '—' }}
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-400 mb-0.5">Giờ xuất phát</p>
              <p class="font-semibold text-gray-900">
                {{ booking?.trip?.depart_at ? fmtTime(booking.trip.depart_at) : '—' }}
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-400 mb-0.5">Ghế</p>
              <p class="font-semibold text-gray-900">
                {{ booking?.passengers?.map((p: any) => p.seat?.seat_code).join(', ') ?? '—' }}
              </p>
            </div>
          </div>

          <!-- Dashed divider with scissors -->
          <div class="flex items-center gap-2 my-5">
            <div class="flex-1 border-t border-dashed border-gray-300" />
            <span class="text-gray-300 text-lg">✂</span>
            <div class="flex-1 border-t border-dashed border-gray-300" />
          </div>

          <div class="space-y-2 text-sm mb-5">
            <div class="flex items-start gap-2">
              <span class="text-blue-500 mt-0.5">📍</span>
              <div>
                <p class="text-xs text-gray-400">Điểm đón</p>
                <p class="font-medium text-gray-900">
                  {{ booking?.pickup_stop?.stop_name ?? 'Mỹ Đình' }}
                  <span class="text-gray-400 font-normal"> — {{ booking?.pickup_stop?.address ?? '20 Phạm Hùng' }}</span>
                </p>
              </div>
            </div>
            <div class="flex items-start gap-2">
              <span class="text-green-500 mt-0.5">🏁</span>
              <div>
                <p class="text-xs text-gray-400">Điểm trả</p>
                <p class="font-medium text-gray-900">
                  {{ booking?.dropoff_stop?.stop_name ?? 'Trung tâm HP' }}
                  <span class="text-gray-400 font-normal"> — {{ booking?.dropoff_stop?.address ?? '1 Đinh Tiên Hoàng' }}</span>
                </p>
              </div>
            </div>
            <div class="flex items-start gap-2">
              <span class="mt-0.5">👤</span>
              <div>
                <p class="text-xs text-gray-400">Hành khách</p>
                <p class="font-medium text-gray-900">
                  {{ booking?.contact_name ?? '—' }}
                  <span class="text-gray-400 font-normal"> · {{ booking?.contact_phone ?? '—' }}</span>
                </p>
              </div>
            </div>
            <div v-if="booking?.trip?.driver" class="flex items-start gap-2">
              <span class="mt-0.5">🚗</span>
              <div>
                <p class="text-xs text-gray-400">Tài xế</p>
                <p class="font-medium text-gray-900">
                  {{ booking.trip.driver.user?.full_name ?? 'Nguyễn Văn Tài' }}
                  <span class="text-yellow-500 ml-1">⭐ {{ booking.trip.driver.rating_avg?.toFixed(1) ?? '4.8' }}</span>
                  <span class="text-gray-400 font-normal"> · {{ booking.trip.vehicle?.plate_number ?? '—' }}</span>
                </p>
              </div>
            </div>
          </div>

          <!-- QR Code -->
          <div class="flex flex-col items-center py-4 border-t border-gray-100">
            <div class="w-36 h-36 bg-gray-100 rounded-xl flex items-center justify-center mb-3">
              <img v-if="booking?.qr_code" :src="booking.qr_code" alt="QR Vé" class="w-32 h-32 object-contain" />
              <div v-else class="text-center">
                <div class="text-4xl mb-1">📱</div>
                <p class="text-xs text-gray-400">QR đang tạo...</p>
              </div>
            </div>
            <p class="text-xs text-gray-500 text-center">Tài xế sẽ quét mã này khi đón bạn</p>
          </div>

          <!-- Action buttons -->
          <div class="flex gap-3 mt-4">
            <button class="flex-1 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
              📥 Tải vé PDF
            </button>
            <button class="flex-1 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
              🔗 Chia sẻ vé
            </button>
          </div>
        </div>
      </div>

      <!-- Bottom actions -->
      <div class="flex gap-4 mt-8">
        <router-link
          v-if="booking?.id"
          :to="`/customer/bookings/${booking.id}/track`"
          class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors shadow-sm">
          📡 Theo dõi chuyến đi
        </router-link>
        <router-link to="/home"
          class="px-8 py-3 border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors">
          Về trang chủ
        </router-link>
      </div>
    </div>
  </div>
</template>
