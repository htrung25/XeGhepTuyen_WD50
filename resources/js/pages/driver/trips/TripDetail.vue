<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { driverApi } from '@/api/driver.api'
import { useDriverStore, type Passenger } from '@/stores/driver.store'

const route   = useRoute()
const router  = useRouter()
const store   = useDriverStore()
const tripId  = route.params.id as string

const trip          = ref<any>(null)
const passengers    = ref<Passenger[]>([])
const isLoading     = ref(true)
const actionLoading = ref(false)
const errorMsg      = ref('')
const expanded      = ref<string | null>(null)
const showConfirm   = ref<'start' | 'complete' | null>(null)
const successMsg    = ref('')
const absentLoading = ref<string | null>(null)

const checkedIn = computed(() => passengers.value.filter(p => p.checked_in).length)
const checkinPct = computed(() =>
  passengers.value.length > 0 ? Math.round((checkedIn.value / passengers.value.length) * 100) : 0
)

const statusConfig = {
  scheduled:   { label: 'Sắp tới',    cls: 'bg-blue-100 text-blue-700',   headerCls: 'bg-blue-600'  },
  in_progress: { label: 'Đang chạy',  cls: 'bg-green-100 text-green-700', headerCls: 'bg-green-600' },
  completed:   { label: 'Hoàn thành', cls: 'bg-gray-100 text-gray-500',   headerCls: 'bg-gray-500'  },
  cancelled:   { label: 'Đã hủy',     cls: 'bg-red-100 text-red-600',     headerCls: 'bg-red-500'   },
} as const

// trip.status là any (ref<any>) — ép về key hợp lệ để TS không báo implicit-any khi index.
function statusInfo(status: string) {
  return statusConfig[status as keyof typeof statusConfig]
}

function fmtTime(iso: string) {
  return new Date(iso).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}
function fmtDateTime(iso: string) {
  return new Date(iso).toLocaleString('vi-VN', { weekday: 'short', month: 'numeric', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function startTrip() {
  actionLoading.value = true
  const { error } = await driverApi.startTrip(tripId)
  actionLoading.value = false
  showConfirm.value   = null
  if (error) { errorMsg.value = typeof error === 'string' ? error : 'Có lỗi xảy ra'; return }
  trip.value.status   = 'in_progress'
  successMsg.value    = 'Chuyến đã bắt đầu!'
  setTimeout(() => { successMsg.value = '' }, 3000)
}

async function completeTrip() {
  actionLoading.value = true
  const { error } = await driverApi.completeTrip(tripId)
  actionLoading.value = false
  showConfirm.value   = null
  if (error) { errorMsg.value = typeof error === 'string' ? error : 'Có lỗi xảy ra'; return }
  setTimeout(() => router.push('/driver/dashboard'), 1500)
}

async function markAbsent(p: Passenger) {
  absentLoading.value = p.id
  const { error } = await driverApi.markAbsent({ trip_id: tripId, booking_id: p.booking_id })
  absentLoading.value = null
  if (error) return
  p.booking_status = 'no_show'
  expanded.value   = null
}

onMounted(async () => {
  isLoading.value = true
  const [tripRes, passRes] = await Promise.all([
    driverApi.getTrip(tripId),
    driverApi.getPassengers(tripId),
  ])
  isLoading.value = false
  if (tripRes.error) { errorMsg.value = tripRes.error as string; return }
  trip.value       = tripRes.data
  passengers.value = passRes.data ?? []
  store.activeTrip = trip.value
  store.passengers = passengers.value
})
</script>

<template>
  <div class="p-6 max-w-5xl mx-auto">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-5">
      <router-link to="/driver/dashboard" class="hover:text-green-600 transition-colors">← Lịch chạy</router-link>
      <span>/</span>
      <span class="text-gray-700 font-medium">Chi tiết chuyến</span>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-4">
      <div class="h-32 bg-gray-200 rounded-xl animate-pulse" />
      <div v-for="i in 4" :key="i" class="h-20 bg-gray-100 rounded-xl animate-pulse" />
    </div>

    <!-- Error -->
    <div v-else-if="errorMsg && !trip"
      class="bg-red-50 border border-red-200 rounded-xl p-6 text-red-700 text-center">
      <p class="font-medium mb-3">{{ errorMsg }}</p>
      <router-link to="/driver/dashboard"
        class="px-5 py-2 border border-red-300 text-red-600 rounded-lg text-sm hover:bg-red-50">
        ← Quay lại
      </router-link>
    </div>

    <div v-else-if="trip" class="grid grid-cols-[1fr_300px] gap-6">

      <!-- ─── LEFT: Trip info + passenger list ─────────────── -->
      <div class="space-y-4">

        <!-- Trip header card -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div :class="['px-5 py-4 text-white', statusInfo(trip.status)?.headerCls ?? 'bg-gray-500']">
            <div class="flex items-center justify-between">
              <h1 class="text-lg font-bold">
                {{ trip.route?.origin_city }} → {{ trip.route?.dest_city }}
              </h1>
              <span class="text-sm font-medium bg-white/20 px-3 py-1 rounded-full">
                {{ statusInfo(trip.status)?.label }}
              </span>
            </div>
            <p class="text-white/80 text-sm mt-1">{{ fmtDateTime(trip.depart_at) }} → {{ fmtTime(trip.arrive_at) }}</p>
          </div>
          <div class="px-5 py-3 grid grid-cols-3 gap-4 text-sm border-b border-gray-100">
            <div>
              <p class="text-gray-400 text-xs">Biển số xe</p>
              <p class="font-semibold font-mono text-gray-900 mt-0.5">{{ trip.vehicle?.plate_number }}</p>
            </div>
            <div>
              <p class="text-gray-400 text-xs">Loại xe</p>
              <p class="font-semibold text-gray-900 mt-0.5">{{ trip.vehicle?.vehicle_type }}</p>
            </div>
            <div>
              <p class="text-gray-400 text-xs">Số ghế</p>
              <p class="font-semibold text-gray-900 mt-0.5">{{ trip.vehicle?.seat_count }} chỗ</p>
            </div>
          </div>

          <!-- Check-in progress -->
          <div class="px-5 py-3">
            <div class="flex items-center justify-between text-sm mb-2">
              <span class="text-gray-600 font-medium">Check-in hành khách</span>
              <span class="font-bold text-green-600">{{ checkedIn }}/{{ passengers.length }}</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2">
              <div class="bg-green-500 h-2 rounded-full transition-all duration-500"
                :style="{ width: checkinPct + '%' }" />
            </div>
            <p class="text-xs text-gray-400 mt-1.5">{{ checkinPct }}% hành khách đã check-in</p>
          </div>
        </div>

        <!-- Success/Error messages -->
        <div v-if="successMsg" class="bg-green-50 border border-green-200 rounded-xl p-3 text-green-700 text-sm font-medium flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          {{ successMsg }}
        </div>
        <div v-if="errorMsg && trip" class="bg-red-50 border border-red-200 rounded-xl p-3 text-red-600 text-sm">
          {{ errorMsg }}
        </div>

        <!-- Passenger list -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Danh sách hành khách</h2>
            <span class="text-sm text-gray-500">{{ passengers.length }} người</span>
          </div>

          <div v-if="passengers.length === 0" class="p-8 text-center text-gray-400">
            <p class="text-2xl mb-2">👥</p>
            <p>Chưa có hành khách đặt chỗ</p>
          </div>

          <div v-else class="divide-y divide-gray-100">
            <div v-for="(p, idx) in passengers" :key="p.id">
              <!-- Main row -->
              <button @click="expanded = expanded === p.id ? null : p.id"
                class="w-full flex items-center gap-4 px-5 py-4 hover:bg-gray-50 transition-colors text-left">
                <!-- Seat number -->
                <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-600 font-bold text-sm shrink-0">
                  {{ idx + 1 }}
                </div>
                <!-- Info -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-0.5">
                    <span class="font-semibold text-gray-900">{{ p.passenger_name }}</span>
                    <span v-for="code in p.seat_codes" :key="code"
                      class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded font-mono">{{ code }}</span>
                    <span v-if="p.booking_status === 'no_show'"
                      class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded font-medium">Vắng</span>
                    <!-- Nhãn thanh toán -->
                    <span v-if="p.amount_due"
                      class="bg-amber-100 text-amber-700 text-xs px-2 py-0.5 rounded font-medium">
                      💵 Thu {{ new Intl.NumberFormat('vi-VN').format(p.amount_due) }}đ
                    </span>
                    <span v-else-if="p.payment_status === 'paid'"
                      class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded font-medium">✓ Đã thanh toán</span>
                  </div>
                  <p class="text-sm text-green-600 truncate">
                    Đón: {{ p.pickup_stop?.stop_name }}
                  </p>
                  <p class="text-xs text-gray-400 truncate">
                    Trả: {{ p.dropoff_stop?.stop_name }}
                  </p>
                </div>
                <!-- Phone + checkin status -->
                <div class="flex items-center gap-2 shrink-0">
                  <a :href="`tel:${p.passenger_phone}`" @click.stop
                    class="w-9 h-9 bg-green-100 text-green-700 rounded-lg flex items-center justify-center hover:bg-green-200 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                  </a>
                  <div :class="['w-9 h-9 rounded-lg flex items-center justify-center',
                    p.checked_in ? 'bg-green-500' : 'bg-gray-100 border border-gray-300']">
                    <svg v-if="p.checked_in" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
              </button>

              <!-- Expanded row -->
              <div v-if="expanded === p.id" class="bg-gray-50 px-5 py-3 border-t border-gray-100 flex items-center justify-between gap-4">
                <p class="text-sm text-gray-500 flex-1">
                  📍 {{ p.pickup_stop?.address }}
                </p>
                <button v-if="!p.checked_in && p.booking_status !== 'no_show'"
                  @click="markAbsent(p)"
                  :disabled="absentLoading === p.id"
                  class="px-4 py-2 bg-white border border-red-300 text-red-500 text-sm font-medium rounded-lg hover:bg-red-50 transition-colors disabled:opacity-60">
                  {{ absentLoading === p.id ? '...' : 'Đánh dấu vắng' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── RIGHT: Action panel ───────────────────────────── -->
      <div class="space-y-4 self-start sticky top-6">

        <!-- Action card -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
          <h3 class="font-semibold text-gray-900 mb-4">Thao tác chuyến đi</h3>

          <!-- Scheduled → Start -->
          <button v-if="trip.status === 'scheduled'"
            @click="showConfirm = 'start'"
            class="w-full py-3.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition-colors mb-3">
            🚦 Bắt đầu chuyến
          </button>

          <!-- In progress → QR + Complete -->
          <template v-else-if="trip.status === 'in_progress'">
            <router-link :to="`/driver/checkin/${tripId}`"
              class="w-full block py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-colors text-center mb-3">
              📷 Quét QR check-in
            </router-link>
            <button @click="showConfirm = 'complete'"
              class="w-full py-3.5 bg-gray-100 hover:bg-red-50 text-gray-700 hover:text-red-600 hover:border-red-300 font-bold rounded-xl border border-gray-200 transition-colors">
              🏁 Kết thúc chuyến
            </button>
          </template>

          <!-- Completed -->
          <div v-else-if="trip.status === 'completed'"
            class="w-full py-3.5 bg-gray-50 text-gray-500 font-semibold rounded-xl text-center border border-gray-200">
            ✅ Chuyến đã hoàn thành
          </div>

          <!-- Cancelled -->
          <div v-else-if="trip.status === 'cancelled'"
            class="w-full py-3.5 bg-red-50 text-red-500 font-semibold rounded-xl text-center border border-red-200">
            ❌ Chuyến đã bị hủy
          </div>
        </div>

        <!-- Navigate link -->
        <router-link v-if="trip.status === 'in_progress'" :to="`/driver/trips/${tripId}/navigate`"
          class="w-full block py-3 border border-gray-200 text-gray-600 hover:border-green-400 hover:text-green-700 hover:bg-green-50 font-medium rounded-xl text-center text-sm transition-colors">
          🗺️ Bật điều hướng GPS
        </router-link>

        <!-- Trip summary -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-sm space-y-2">
          <div class="flex justify-between text-gray-600">
            <span>Hành khách đặt</span>
            <span class="font-semibold text-gray-900">{{ passengers.length }}</span>
          </div>
          <div class="flex justify-between text-gray-600">
            <span>Đã check-in</span>
            <span class="font-semibold text-green-600">{{ checkedIn }}</span>
          </div>
          <div class="flex justify-between text-gray-600">
            <span>Vắng mặt</span>
            <span class="font-semibold text-red-500">{{ passengers.filter(p => p.booking_status === 'no_show').length }}</span>
          </div>
          <div class="border-t border-gray-100 pt-2 flex justify-between">
            <span class="font-semibold text-gray-700">Dự kiến thu</span>
            <span class="font-bold text-green-600">{{ new Intl.NumberFormat('vi-VN').format(trip.price * checkedIn) }}đ</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ─── Confirm modals ─────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showConfirm"
        class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4"
        @click.self="showConfirm = null">
        <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full">

          <!-- Start confirm -->
          <template v-if="showConfirm === 'start'">
            <div class="text-center mb-5">
              <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3 text-3xl">🚦</div>
              <h3 class="text-lg font-bold text-gray-900">Bắt đầu chuyến?</h3>
              <p class="text-gray-500 text-sm mt-1">
                Xác nhận bắt đầu chuyến
                {{ trip?.route?.origin_city }} → {{ trip?.route?.dest_city }}
              </p>
            </div>
            <div class="flex gap-3">
              <button @click="showConfirm = null"
                class="flex-1 py-3 border border-gray-200 text-gray-600 rounded-xl font-medium hover:bg-gray-50 transition-colors">
                Hủy
              </button>
              <button @click="startTrip" :disabled="actionLoading"
                class="flex-1 py-3 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 disabled:opacity-60 transition-colors flex items-center justify-center gap-2">
                <div v-if="actionLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                <span>{{ actionLoading ? '...' : 'Bắt đầu' }}</span>
              </button>
            </div>
          </template>

          <!-- Complete confirm -->
          <template v-else-if="showConfirm === 'complete'">
            <div class="text-center mb-5">
              <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3 text-3xl">🏁</div>
              <h3 class="text-lg font-bold text-gray-900">Kết thúc chuyến?</h3>
              <p class="text-gray-500 text-sm mt-1">
                Chỉ {{ checkedIn }}/{{ passengers.length }} hành khách đã check-in.
                Bạn có chắc muốn kết thúc?
              </p>
            </div>
            <div class="flex gap-3">
              <button @click="showConfirm = null"
                class="flex-1 py-3 border border-gray-200 text-gray-600 rounded-xl font-medium hover:bg-gray-50 transition-colors">
                Hủy
              </button>
              <button @click="completeTrip" :disabled="actionLoading"
                class="flex-1 py-3 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 disabled:opacity-60 transition-colors flex items-center justify-center gap-2">
                <div v-if="actionLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                <span>{{ actionLoading ? '...' : 'Kết thúc' }}</span>
              </button>
            </div>
          </template>
        </div>
      </div>
    </Teleport>
  </div>
</template>
