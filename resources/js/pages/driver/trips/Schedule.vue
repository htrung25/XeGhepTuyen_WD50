<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { driverApi } from '@/api/driver.api'
import { type DriverTrip } from '@/stores/driver.store'

const trips     = ref<DriverTrip[]>([])
const isLoading = ref(true)
const errorMsg  = ref('')
const weekOffset = ref(0) // 0 = current week, -1 = last week, 1 = next week
const selectedTrip = ref<DriverTrip | null>(null)

// Build week dates based on offset
const weekDates = computed(() => {
  const now  = new Date()
  const day  = now.getDay() // 0=Sun, 1=Mon...
  const diff = day === 0 ? -6 : 1 - day // adjust to Monday
  const monday = new Date(now)
  monday.setDate(now.getDate() + diff + weekOffset.value * 7)
  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date(monday)
    d.setDate(monday.getDate() + i)
    return d
  })
})

const weekLabel = computed(() => {
  const s = weekDates.value[0]
  const e = weekDates.value[6]
  const fmt = (d: Date) => d.toLocaleDateString('vi-VN', { day: 'numeric', month: 'numeric' })
  return `${fmt(s)} – ${fmt(e)}, ${s.getFullYear()}`
})

const dayNames = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN']

// Map trips to their calendar day (index 0-6)
const tripsByDay = computed(() => {
  const map: Record<number, DriverTrip[]> = {}
  weekDates.value.forEach((_, i) => { map[i] = [] })
  trips.value.forEach(trip => {
    const tripDate = new Date(trip.depart_at).toDateString()
    weekDates.value.forEach((d, i) => {
      if (d.toDateString() === tripDate) map[i].push(trip)
    })
  })
  return map
})

const isToday = (d: Date) => d.toDateString() === new Date().toDateString()
const isPast  = (d: Date) => d < new Date() && !isToday(d)

const statusColors = {
  scheduled:   'bg-blue-500',
  in_progress: 'bg-green-600',
  completed:   'bg-gray-400',
  cancelled:   'bg-red-400',
} as const

const statusLabels = {
  scheduled:   'Sắp tới',
  in_progress: 'Đang chạy',
  completed:   'Hoàn thành',
  cancelled:   'Đã hủy',
} as const

function fmtTime(iso: string) {
  return new Date(iso).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}
function fmtDate(d: Date) {
  return d.toLocaleDateString('vi-VN', { weekday: 'short', day: 'numeric', month: 'numeric' })
}

// Upcoming trips sorted
const upcomingTrips = computed(() =>
  trips.value
    .filter(t => t.status === 'scheduled' || t.status === 'in_progress')
    .sort((a, b) => new Date(a.depart_at).getTime() - new Date(b.depart_at).getTime())
)

async function load() {
  isLoading.value = true
  errorMsg.value  = ''
  const from = weekDates.value[0].toISOString().split('T')[0]
  const to   = weekDates.value[6].toISOString().split('T')[0]
  const { data, error } = await driverApi.getTrips({ date: undefined } as any)
  // Fallback: getTrips doesn't support from/to, use full list
  isLoading.value = false
  if (error) { errorMsg.value = error as string; return }
  trips.value = data ?? []
}

onMounted(load)
</script>

<template>
  <div class="p-6">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Lịch chạy của tôi</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ weekLabel }}</p>
      </div>

      <!-- Week navigation -->
      <div class="flex items-center gap-2">
        <button @click="weekOffset--; load()"
          class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
          ‹
        </button>
        <button @click="weekOffset = 0; load()"
          :class="['px-3 py-2 text-sm font-medium rounded-lg transition-colors',
            weekOffset === 0
              ? 'bg-green-600 text-white'
              : 'border border-gray-200 text-gray-600 hover:bg-gray-50']">
          Tuần này
        </button>
        <button @click="weekOffset++; load()"
          class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
          ›
        </button>
      </div>
    </div>

    <!-- Error -->
    <div v-if="errorMsg"
      class="mb-5 bg-red-50 border border-red-200 rounded-xl p-4 text-red-600 text-sm flex items-center justify-between">
      <span>{{ errorMsg }}</span>
      <button @click="load" class="font-semibold underline text-sm">Thử lại</button>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-4">
      <div class="grid grid-cols-7 gap-2">
        <div v-for="i in 7" :key="i" class="h-60 bg-gray-200 rounded-xl animate-pulse" />
      </div>
      <div class="h-48 bg-gray-200 rounded-xl animate-pulse" />
    </div>

    <div v-else class="space-y-5">

      <!-- ─── Weekly calendar grid ──────────────────────────── -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

        <!-- Day headers -->
        <div class="grid grid-cols-7 border-b border-gray-100">
          <div v-for="(date, i) in weekDates" :key="i"
            :class="['px-3 py-3 text-center border-r border-gray-100 last:border-r-0',
              isToday(date) ? 'bg-green-50' : isPast(date) ? 'bg-gray-50/50' : '']">
            <p :class="['text-xs font-semibold uppercase tracking-wider',
              isToday(date) ? 'text-green-600' : 'text-gray-400']">
              {{ dayNames[i] }}
            </p>
            <p :class="['text-lg font-black mt-0.5',
              isToday(date) ? 'text-green-600' : isPast(date) ? 'text-gray-400' : 'text-gray-800']">
              {{ date.getDate() }}
            </p>
            <p class="text-xs text-gray-400">{{ date.toLocaleDateString('vi-VN', { month: 'numeric' }) }}/{{ date.getFullYear().toString().slice(-2) }}</p>
          </div>
        </div>

        <!-- Day columns with trip blocks -->
        <div class="grid grid-cols-7 min-h-[200px]">
          <div v-for="(date, dayIdx) in weekDates" :key="dayIdx"
            :class="['px-2 py-3 border-r border-gray-100 last:border-r-0 space-y-1.5',
              isToday(date) ? 'bg-green-50/40' : isPast(date) ? 'bg-gray-50/20' : '']">

            <!-- Trip blocks for this day -->
            <div v-if="tripsByDay[dayIdx]?.length > 0">
              <div v-for="trip in tripsByDay[dayIdx]" :key="trip.id"
                @click="selectedTrip = trip"
                :class="['rounded-lg px-2 py-2 cursor-pointer transition-all hover:shadow-sm hover:scale-[1.02]',
                  statusColors[trip.status] ?? 'bg-gray-400',
                  'text-white']">
                <p class="text-xs font-black leading-tight">{{ fmtTime(trip.depart_at) }}</p>
                <p class="text-xs font-medium leading-tight truncate mt-0.5 opacity-90">
                  HN→HP
                </p>
                <p class="text-xs opacity-70 mt-0.5">{{ trip.passengers_count }} khách</p>
              </div>
            </div>

            <!-- Empty day -->
            <div v-else class="flex items-center justify-center h-16">
              <span class="text-gray-300 text-xs">—</span>
            </div>
          </div>
        </div>

        <!-- Legend -->
        <div class="px-4 py-3 border-t border-gray-100 flex items-center gap-4">
          <span class="text-xs text-gray-400 font-medium">Trạng thái:</span>
          <div v-for="(color, key) in statusColors" :key="key" class="flex items-center gap-1.5">
            <div :class="['w-3 h-3 rounded-sm', color]" />
            <span class="text-xs text-gray-500">{{ statusLabels[key as keyof typeof statusLabels] }}</span>
          </div>
        </div>
      </div>

      <!-- ─── Upcoming trips table ───────────────────────────── -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="font-bold text-gray-900">Lịch sắp tới</h2>
          <span class="text-sm text-gray-400">{{ upcomingTrips.length }} chuyến</span>
        </div>

        <div v-if="upcomingTrips.length === 0" class="py-10 text-center text-gray-400">
          <div class="text-3xl mb-2">📅</div>
          <p>Không có chuyến sắp tới trong tuần này</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-100">
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ngày</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Giờ</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tuyến</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Xe</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Số khách đặt</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Trạng thái</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="trip in upcomingTrips" :key="trip.id"
                @click="$router.push(`/driver/trips/${trip.id}`)"
                class="hover:bg-gray-50 transition-colors cursor-pointer">
                <td class="px-5 py-4 text-gray-600">
                  {{ new Date(trip.depart_at).toLocaleDateString('vi-VN', { weekday: 'short', day: 'numeric', month: 'numeric' }) }}
                </td>
                <td class="px-5 py-4 font-mono font-bold text-gray-900">{{ fmtTime(trip.depart_at) }}</td>
                <td class="px-5 py-4 font-semibold text-gray-900">
                  {{ trip.route?.origin_city }} → {{ trip.route?.dest_city }}
                </td>
                <td class="px-5 py-4">
                  <span class="font-mono text-xs bg-gray-100 px-2.5 py-1 rounded-lg text-gray-600">
                    {{ trip.vehicle?.plate_number }}
                  </span>
                </td>
                <td class="px-5 py-4">
                  <div class="flex items-center gap-2">
                    <div class="w-20 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                      <div class="h-full bg-green-500 rounded-full"
                        :style="{ width: (trip.passengers_count / (trip.vehicle?.seat_count || 1) * 100) + '%' }" />
                    </div>
                    <span class="text-gray-700 font-medium">{{ trip.passengers_count }}/{{ trip.vehicle?.seat_count }}</span>
                  </div>
                </td>
                <td class="px-5 py-4">
                  <span :class="['inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold',
                    trip.status === 'in_progress' ? 'bg-green-100 text-green-700' : 'bg-blue-50 text-blue-600']">
                    {{ statusLabels[trip.status] ?? trip.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ─── Trip detail slide-over ─────────────────────────── -->
    <Teleport to="body">
      <div v-if="selectedTrip"
        class="fixed inset-0 z-50 bg-black/30 flex items-center justify-end"
        @click.self="selectedTrip = null">
        <div class="bg-white w-full max-w-sm h-full shadow-2xl p-6 overflow-y-auto">
          <div class="flex items-center justify-between mb-5">
            <h3 class="font-bold text-gray-900 text-lg">Chi tiết chuyến</h3>
            <button @click="selectedTrip = null" class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="space-y-4 text-sm">
            <div class="p-4 bg-green-50 rounded-xl">
              <p class="text-xl font-black text-gray-900">
                {{ selectedTrip.route?.origin_city }} → {{ selectedTrip.route?.dest_city }}
              </p>
              <p class="text-green-700 font-semibold mt-1">
                {{ fmtTime(selectedTrip.depart_at) }} → {{ fmtTime(selectedTrip.arrive_at) }}
              </p>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="bg-gray-50 rounded-xl p-3">
                <p class="text-gray-400 text-xs">Biển số xe</p>
                <p class="font-mono font-bold text-gray-900 mt-0.5">{{ selectedTrip.vehicle?.plate_number }}</p>
              </div>
              <div class="bg-gray-50 rounded-xl p-3">
                <p class="text-gray-400 text-xs">Số hành khách</p>
                <p class="font-bold text-gray-900 mt-0.5">{{ selectedTrip.passengers_count }}/{{ selectedTrip.vehicle?.seat_count }}</p>
              </div>
            </div>
            <span :class="['inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold',
              selectedTrip.status === 'in_progress' ? 'bg-green-100 text-green-700' :
              selectedTrip.status === 'completed'   ? 'bg-gray-100 text-gray-600' :
              selectedTrip.status === 'cancelled'   ? 'bg-red-100 text-red-600' :
              'bg-blue-50 text-blue-600']">
              {{ statusLabels[selectedTrip.status] ?? selectedTrip.status }}
            </span>
          </div>

          <div class="mt-6 space-y-3">
            <router-link :to="`/driver/trips/${selectedTrip.id}`"
              @click="selectedTrip = null"
              class="block w-full py-3 bg-green-600 hover:bg-green-700 text-white text-center font-bold rounded-xl transition-colors">
              Xem chi tiết →
            </router-link>
            <button @click="selectedTrip = null"
              class="block w-full py-3 border border-gray-200 text-gray-600 text-center font-medium rounded-xl hover:bg-gray-50 transition-colors">
              Đóng
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
