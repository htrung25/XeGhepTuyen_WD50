<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { operatorApi } from '@/api/operator.api'

interface KpiData {
  gross_revenue: number
  net_revenue: number
  commission: number
  total_trips: number
  total_bookings: number
  avg_occupancy: number
}

interface TripRow {
  id: string
  depart_at: string
  route: { origin_city: string; dest_city: string }
  driver: { full_name: string } | null
  vehicle: { plate: string } | null
  booking_count: number
  total_seats: number
  status: string
}

interface OnboardingFleet {
  declared_fleet: Record<string, number>
  declared_summary: string
  declared_total: number
  actual_count: number
  remaining: number
  fleet_labels: Record<string, string>
}

const isLoading  = ref(true)
const errorMsg   = ref('')
const kpi        = ref<KpiData | null>(null)
const trips      = ref<TripRow[]>([])
const chartData  = ref<{ date: string; revenue: number }[]>([])
const onboarding = ref<OnboardingFleet | null>(null)

const loadOnboarding = async () => {
  const { data } = await operatorApi.getOnboardingFleet()
  onboarding.value = (data as OnboardingFleet) ?? null
}

const statusConfig: Record<string, { label: string; class: string }> = {
  scheduled:   { label: 'Đã lên lịch',    class: 'bg-slate-100 text-slate-600' },
  boarding:    { label: 'Đang đón khách', class: 'bg-blue-100 text-blue-700'  },
  in_progress: { label: 'Đang chạy',      class: 'bg-green-100 text-green-700' },
  completed:   { label: 'Hoàn thành',     class: 'bg-slate-800 text-white'    },
  cancelled:   { label: 'Đã hủy',         class: 'bg-red-100 text-red-700'    },
}

const fmt = (n: number) => new Intl.NumberFormat('vi-VN').format(n) + 'đ'

const load = async () => {
  isLoading.value = true
  errorMsg.value  = ''

  const [summaryRes, tripsRes] = await Promise.all([
    operatorApi.getRevenueSummary({ period: 'today' }),
    operatorApi.getTrips({ date: new Date().toISOString().slice(0, 10) }),
  ])

  if (summaryRes.error || tripsRes.error) {
    errorMsg.value = 'Không thể tải dữ liệu. Vui lòng thử lại.'
    isLoading.value = false
    return
  }

  kpi.value   = summaryRes.data
  trips.value = tripsRes.data ?? []
  isLoading.value = false
}

// Compute bar chart heights (relative to max)
const maxRevenue = ref(1)
const barPercent = (v: number) => Math.round((v / maxRevenue.value) * 100)

onMounted(() => {
  load()
  loadOnboarding()
})
</script>

<template>
  <div class="p-6 space-y-6">

    <!-- Page title -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-slate-800">Tổng quan</h1>
        <p class="text-sm text-slate-500 mt-0.5">{{ new Date().toLocaleDateString('vi-VN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
      </div>
      <button
        class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-600 hover:bg-slate-50 transition-colors"
        @click="load"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Làm mới
      </button>
    </div>

    <!-- Onboarding: nhắc thêm xe so với cơ cấu đã khai lúc đăng ký -->
    <div v-if="onboarding && onboarding.remaining > 0"
      class="bg-amber-50 border border-amber-200 rounded-xl p-5">
      <div class="flex items-start gap-4">
        <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center shrink-0">
          <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-amber-900">Hoàn tất khai báo đội xe</p>
          <p class="text-sm text-amber-800 mt-0.5">
            Hồ sơ đăng ký của bạn: <strong>{{ onboarding.declared_total }} xe</strong> ({{ onboarding.declared_summary }}).
            Bạn đã thêm <strong>{{ onboarding.actual_count }}/{{ onboarding.declared_total }}</strong> xe —
            hãy khai đầy đủ biển số &amp; giấy tờ để bắt đầu nhận chuyến.
          </p>
          <div class="mt-3 flex items-center gap-3">
            <div class="h-1.5 flex-1 max-w-xs bg-amber-100 rounded-full overflow-hidden">
              <div class="h-full bg-amber-500 rounded-full transition-all duration-500"
                :style="{ width: Math.min(100, Math.round(onboarding.actual_count / (onboarding.declared_total || 1) * 100)) + '%' }" />
            </div>
            <span class="text-xs font-medium text-amber-700">Còn {{ onboarding.remaining }} xe</span>
          </div>
        </div>
        <router-link to="/operator/vehicles"
          class="shrink-0 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors">
          Thêm xe
        </router-link>
      </div>
    </div>

    <!-- Loading skeleton -->
    <div v-if="isLoading" class="space-y-4">
      <div class="grid grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="animate-pulse bg-white rounded-xl h-28 border border-slate-200" />
      </div>
      <div class="animate-pulse bg-white rounded-xl h-56 border border-slate-200" />
      <div class="animate-pulse bg-white rounded-xl h-64 border border-slate-200" />
    </div>

    <!-- Error state -->
    <div v-else-if="errorMsg"
         class="bg-red-50 border border-red-200 rounded-xl p-5 flex items-center gap-4">
      <svg class="w-8 h-8 text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <div>
        <p class="font-medium text-red-700">Lỗi tải dữ liệu</p>
        <p class="text-sm text-red-600 mt-0.5">{{ errorMsg }}</p>
      </div>
      <button class="ml-auto px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors" @click="load">Thử lại</button>
    </div>

    <template v-else>
      <!-- KPI Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- Doanh thu hôm nay -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Doanh thu hôm nay</p>
              <p class="text-2xl font-bold text-slate-800 mt-2">{{ kpi ? fmt(kpi.gross_revenue ?? 0) : '—' }}</p>
            </div>
            <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <p class="text-xs text-green-600 font-medium mt-3 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
            </svg>
            +12% so với hôm qua
          </p>
        </div>

        <!-- Chuyến hôm nay -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Chuyến hôm nay</p>
              <p class="text-2xl font-bold text-slate-800 mt-2">{{ kpi?.total_trips ?? trips.length }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
          <p class="text-xs text-slate-400 mt-3">
            {{ trips.filter(t => t.status === 'in_progress').length }} chuyến đang chạy
          </p>
        </div>

        <!-- Số khách -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Số khách hôm nay</p>
              <p class="text-2xl font-bold text-slate-800 mt-2">{{ kpi?.total_bookings ?? '—' }}</p>
            </div>
            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
          </div>
          <p class="text-xs text-slate-400 mt-3">Hành khách đã xác nhận</p>
        </div>

        <!-- Tỷ lệ lấp đầy -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Tỷ lệ lấp đầy</p>
              <p class="text-2xl font-bold text-slate-800 mt-2">{{ kpi?.avg_occupancy ?? '—' }}%</p>
            </div>
            <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
          </div>
          <div class="mt-3">
            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
              <div
                class="h-full bg-amber-500 rounded-full transition-all duration-500"
                :style="{ width: (kpi?.avg_occupancy ?? 0) + '%' }"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Trips Table -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <h2 class="font-semibold text-slate-800">Chuyến đi hôm nay</h2>
          <router-link to="/operator/trips" class="text-sm text-amber-600 hover:text-amber-700 font-medium">
            Xem tất cả →
          </router-link>
        </div>

        <!-- Empty state -->
        <div v-if="trips.length === 0" class="py-16 flex flex-col items-center text-slate-400">
          <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <p class="font-medium">Chưa có chuyến nào hôm nay</p>
          <p class="text-sm mt-1">Tạo lịch chạy để bắt đầu</p>
          <router-link to="/operator/trips" class="mt-3 px-4 py-2 bg-amber-500 text-white text-sm rounded-lg hover:bg-amber-600 transition-colors">
            Tạo chuyến mới
          </router-link>
        </div>

        <!-- Table -->
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Giờ xuất phát</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tuyến</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tài xế</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Xe</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Khách / Chỗ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Trạng thái</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="trip in trips" :key="trip.id" class="hover:bg-slate-50 transition-colors">
                <td class="px-6 py-4 text-sm font-medium text-slate-800">
                  {{ new Date(trip.depart_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) }}
                </td>
                <td class="px-6 py-4 text-sm text-slate-700">{{ trip.route?.origin_city }} → {{ trip.route?.dest_city }}</td>
                <td class="px-6 py-4 text-sm text-slate-700">{{ trip.driver?.full_name ?? '—' }}</td>
                <td class="px-6 py-4 text-sm text-slate-600">{{ trip.vehicle?.plate ?? '—' }}</td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <div class="h-1.5 w-20 bg-slate-100 rounded-full overflow-hidden">
                      <div
                        class="h-full bg-amber-500 rounded-full"
                        :style="{ width: ((trip.booking_count ?? 0) / (trip.total_seats || 1) * 100) + '%' }"
                      />
                    </div>
                    <span class="text-sm text-slate-600">{{ trip.booking_count ?? 0 }}/{{ trip.total_seats }}</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="statusConfig[trip.status]?.class ?? 'bg-slate-100 text-slate-600'"
                  >
                    {{ statusConfig[trip.status]?.label ?? trip.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>
