<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { driverApi } from '@/api/driver.api'
import { useDriverAuthStore } from '@/stores/driver.auth.store'
import { useDriverStore, type DriverTrip } from '@/stores/driver.store'

const auth  = useDriverAuthStore()
const store = useDriverStore()

const trips     = ref<DriverTrip[]>([])
const earnings  = ref<any>(null)
const isLoading = ref(true)
const errorMsg  = ref('')

const statusConfig = {
  scheduled:   { label: 'Sắp tới',    cls: 'bg-gray-100 text-gray-600' },
  in_progress: { label: 'Đang chạy',  cls: 'bg-green-100 text-green-700 animate-pulse' },
  completed:   { label: 'Hoàn thành', cls: 'bg-blue-50 text-blue-600'  },
  cancelled:   { label: 'Đã hủy',     cls: 'bg-red-100 text-red-600'   },
} as const

function fmt(v: number) { return new Intl.NumberFormat('vi-VN').format(v) + 'đ' }
function fmtTime(iso: string) {
  return new Date(iso).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}

const kpis = computed(() => {
  const today = trips.value
  const done  = today.filter(t => t.status === 'completed')
  return [
    { label: 'Chuyến hôm nay',    value: today.length,                          icon: '🚐', color: 'text-green-600' },
    { label: 'Thu nhập hôm nay',  value: fmt(done.reduce((s, t) => s + t.price * (t.passengers_count ?? 0), 0)), icon: '💰', color: 'text-green-600', wide: true },
    { label: 'Tháng này',         value: (earnings.value?.trip_count ?? 0) + ' chuyến', icon: '📅', color: 'text-gray-800' },
    { label: 'Đánh giá',          value: (auth.user?.rating_avg?.toFixed(1) ?? '4.8') + ' ★', icon: '⭐', color: 'text-yellow-600' },
  ]
})

const maxBar = computed(() =>
  Math.max(...(earnings.value?.daily_amounts ?? [0]), 1)
)
const dayLabels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN']

async function load() {
  isLoading.value = true
  errorMsg.value  = ''
  const [tripsRes, earnRes] = await Promise.all([
    driverApi.getTrips({ date: 'today' }),
    driverApi.getEarnings({ period: 'week' }),
  ])
  isLoading.value = false
  if (tripsRes.error) { errorMsg.value = tripsRes.error as string; return }
  trips.value          = tripsRes.data ?? []
  store.todayTrips     = trips.value
  if (!earnRes.error && earnRes.data) {
    earnings.value     = earnRes.data
    store.weekEarnings = earnRes.data.total ?? 0
  }
}

onMounted(load)
</script>

<template>
  <div class="p-6">

    <!-- Page header -->
    <div class="mb-6">
      <h1 class="text-xl font-bold text-gray-900">
        Xin chào, {{ auth.user?.full_name?.split(' ').at(-1) ?? 'Tài xế' }} 👋
      </h1>
      <p class="text-sm text-gray-500 mt-0.5">
        {{ new Date().toLocaleDateString('vi-VN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
      </p>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-4">
      <div class="grid grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="h-24 bg-gray-200 rounded-xl animate-pulse" />
      </div>
      <div class="h-64 bg-gray-200 rounded-xl animate-pulse" />
      <div class="h-48 bg-gray-200 rounded-xl animate-pulse" />
    </div>

    <!-- Error -->
    <div v-else-if="errorMsg"
      class="bg-red-50 border border-red-200 rounded-xl p-5 text-red-700 flex items-center justify-between">
      <span>{{ errorMsg }}</span>
      <button @click="load" class="text-sm font-semibold text-red-600 hover:underline">Thử lại</button>
    </div>

    <div v-else class="space-y-5">

      <!-- ─── KPI cards row ─────────────────────────────────── -->
      <div class="grid grid-cols-4 gap-4">
        <div v-for="kpi in kpis" :key="kpi.label"
          class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
          <div class="flex items-start justify-between mb-2">
            <span class="text-gray-400 text-xs font-medium">{{ kpi.label }}</span>
            <span class="text-xl">{{ kpi.icon }}</span>
          </div>
          <p :class="['text-2xl font-black', kpi.color]">{{ kpi.value }}</p>
        </div>
      </div>

      <!-- ─── Chuyến hôm nay table ──────────────────────────── -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="font-bold text-gray-900">Chuyến hôm nay</h2>
          <router-link to="/driver/schedule"
            class="text-sm text-green-600 hover:text-green-700 font-medium transition-colors">
            Xem lịch đầy đủ →
          </router-link>
        </div>

        <!-- Empty state -->
        <div v-if="trips.length === 0" class="py-14 text-center">
          <div class="text-4xl mb-3">😴</div>
          <p class="font-medium text-gray-600">Không có chuyến hôm nay</p>
          <p class="text-sm text-gray-400 mt-1">Hệ thống sẽ thông báo khi có lịch mới</p>
        </div>

        <!-- Table -->
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-100">
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Giờ</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tuyến</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Số khách</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Xe</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Trạng thái</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Hành động</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="trip in trips" :key="trip.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-4 font-mono font-bold text-gray-900 tabular-nums">
                  {{ fmtTime(trip.depart_at) }}
                  <div class="text-xs text-gray-400 font-sans font-normal">→ {{ fmtTime(trip.arrive_at) }}</div>
                </td>
                <td class="px-5 py-4 font-semibold text-gray-900">
                  {{ trip.route?.origin_city }} → {{ trip.route?.dest_city }}
                </td>
                <td class="px-5 py-4">
                  <div class="flex items-center gap-1.5">
                    <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                      <div class="h-full bg-green-500 rounded-full transition-all"
                        :style="{ width: (trip.passengers_count / (trip.vehicle?.seat_count || 1) * 100) + '%' }" />
                    </div>
                    <span class="text-gray-700 font-medium">{{ trip.passengers_count }}/{{ trip.vehicle?.seat_count }}</span>
                  </div>
                </td>
                <td class="px-5 py-4">
                  <span class="font-mono text-xs bg-gray-100 px-2.5 py-1 rounded-lg text-gray-600">
                    {{ trip.vehicle?.plate_number }}
                  </span>
                </td>
                <td class="px-5 py-4">
                  <span :class="['inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold',
                    statusConfig[trip.status]?.cls ?? 'bg-gray-100 text-gray-500']">
                    {{ statusConfig[trip.status]?.label ?? trip.status }}
                  </span>
                </td>
                <td class="px-5 py-4">
                  <router-link :to="`/driver/trips/${trip.id}`"
                    :class="['inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors',
                      trip.status === 'in_progress'
                        ? 'bg-green-600 text-white hover:bg-green-700'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200']">
                    {{ trip.status === 'in_progress' ? '🚐 Đang chạy' : 'Xem chi tiết' }}
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ─── Thu nhập 7 ngày bar chart ────────────────────── -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
        <div class="flex items-center justify-between mb-5">
          <h2 class="font-bold text-gray-900">Thu nhập 7 ngày gần nhất</h2>
          <router-link to="/driver/earnings"
            class="text-sm text-green-600 hover:text-green-700 font-medium transition-colors">
            Xem chi tiết →
          </router-link>
        </div>

        <div v-if="earnings?.daily_amounts?.length" class="flex items-end gap-2 h-32 mb-3">
          <div v-for="(val, i) in earnings.daily_amounts" :key="i"
            class="flex-1 flex flex-col items-center gap-1.5">
            <span class="text-xs text-gray-400">
              {{ val > 0 ? (val >= 1000000 ? (val/1000000).toFixed(1)+'M' : (val/1000).toFixed(0)+'K') : '' }}
            </span>
            <div class="w-full rounded-t-lg transition-all"
              :class="i === (earnings.daily_amounts.length - 1) ? 'bg-green-600' : 'bg-green-200'"
              :style="{ height: Math.max((val / maxBar) * 96, val > 0 ? 8 : 4) + 'px' }" />
          </div>
        </div>
        <div v-else class="h-32 flex items-center justify-center text-gray-400 text-sm bg-gray-50 rounded-xl mb-3">
          Chưa có dữ liệu
        </div>

        <div class="flex">
          <span v-for="label in dayLabels" :key="label"
            class="flex-1 text-center text-xs text-gray-400 font-medium">{{ label }}</span>
        </div>

        <!-- Weekly total -->
        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between text-sm">
          <span class="text-gray-500">Tổng tuần này</span>
          <span class="font-black text-green-600 text-lg">{{ fmt(earnings?.total ?? 0) }}</span>
        </div>
      </div>

    </div>
  </div>
</template>
