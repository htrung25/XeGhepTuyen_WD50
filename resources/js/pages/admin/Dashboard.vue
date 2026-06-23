<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { adminApi } from '@/api/admin.api'

interface DashboardStats {
  bookings_today: number
  revenue_today: number
  active_trips: number
  new_users_today: number
  pending_operators: number
  pending_drivers: number
}

interface RecentBooking {
  code: string
  customer: string
  route: string
  amount: number
  status: string
  created_at: string
}

const stats = ref<DashboardStats | null>(null)
const recentBookings = ref<RecentBooking[]>([])
const isLoading = ref(true)
const errorMsg = ref('')
let refreshTimer: ReturnType<typeof setInterval> | null = null

const statusMap: Record<string, { label: string; class: string }> = {
  pending:    { label: 'Chờ xử lý',  class: 'bg-yellow-100 text-yellow-700' },
  confirmed:  { label: 'Đã xác nhận', class: 'bg-blue-100 text-blue-700' },
  in_progress:{ label: 'Đang đi',    class: 'bg-green-100 text-green-700' },
  completed:  { label: 'Hoàn thành', class: 'bg-gray-100 text-gray-600' },
  cancelled:  { label: 'Đã hủy',    class: 'bg-red-100 text-red-700' },
}

function formatCurrency(val: number) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val)
}

async function loadDashboard() {
  const { data, error } = await adminApi.getDashboard()
  if (error) { errorMsg.value = error; isLoading.value = false; return }
  stats.value = data.stats
  recentBookings.value = data.recent_bookings ?? []
  isLoading.value = false
}

onMounted(() => {
  loadDashboard()
  refreshTimer = setInterval(loadDashboard, 30_000)
})
onUnmounted(() => { if (refreshTimer) clearInterval(refreshTimer) })
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Tổng quan hệ thống</h1>
        <p class="text-sm text-gray-500 mt-0.5">Dữ liệu thời gian thực — tự động cập nhật mỗi 30s</p>
      </div>
      <div class="flex items-center gap-2 text-xs text-gray-500">
        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse inline-block" />
        Đang theo dõi trực tiếp
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div v-for="i in 4" :key="i"
          class="bg-white rounded-xl border border-slate-200 p-5 animate-pulse h-28" />
      </div>
      <div class="grid grid-cols-2 gap-4 mb-6">
        <div v-for="i in 2" :key="i"
          class="bg-white rounded-xl border border-slate-200 p-5 animate-pulse h-24" />
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-5 animate-pulse h-64" />
    </div>

    <!-- Error -->
    <div v-else-if="errorMsg"
      class="bg-red-50 border border-red-200 rounded-xl p-5 text-red-700 flex items-center gap-3">
      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span>{{ errorMsg }}</span>
      <button @click="loadDashboard" class="ml-auto text-sm underline">Thử lại</button>
    </div>

    <template v-else-if="stats">
      <!-- KPI Row 1 -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Booking hôm nay</p>
          <p class="text-3xl font-bold text-gray-900 mt-1">{{ stats.bookings_today }}</p>
          <p class="text-xs text-green-600 mt-1">Tổng đơn đặt vé trong ngày</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Doanh thu hôm nay</p>
          <p class="text-2xl font-bold text-red-600 mt-1">{{ formatCurrency(stats.revenue_today) }}</p>
          <p class="text-xs text-gray-400 mt-1">Trước phí hoa hồng</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Xe đang chạy</p>
          <p class="text-3xl font-bold text-blue-600 mt-1">{{ stats.active_trips }}</p>
          <p class="text-xs text-gray-400 mt-1">Chuyến đang trong lộ trình</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">User mới hôm nay</p>
          <p class="text-3xl font-bold text-gray-900 mt-1">{{ stats.new_users_today }}</p>
          <p class="text-xs text-gray-400 mt-1">Tài khoản đăng ký mới</p>
        </div>
      </div>

      <!-- KPI Row 2 — Pending approvals -->
      <div class="grid grid-cols-2 gap-4 mb-6">
        <router-link to="/admin/operators"
          class="bg-white rounded-xl border border-orange-200 shadow-sm p-5 flex items-center gap-4 hover:border-orange-300 transition-colors group">
          <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
            </svg>
          </div>
          <div>
            <p class="text-xs text-gray-500 font-medium">Nhà xe chờ duyệt</p>
            <div class="flex items-center gap-2 mt-0.5">
              <p class="text-2xl font-bold text-gray-900">{{ stats.pending_operators }}</p>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
                Cần xử lý
              </span>
            </div>
          </div>
          <svg class="w-4 h-4 text-gray-400 ml-auto group-hover:text-orange-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </router-link>

        <router-link to="/admin/drivers"
          class="bg-white rounded-xl border border-orange-200 shadow-sm p-5 flex items-center gap-4 hover:border-orange-300 transition-colors group">
          <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div>
            <p class="text-xs text-gray-500 font-medium">Tài xế chờ duyệt</p>
            <div class="flex items-center gap-2 mt-0.5">
              <p class="text-2xl font-bold text-gray-900">{{ stats.pending_drivers }}</p>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
                Cần xử lý
              </span>
            </div>
          </div>
          <svg class="w-4 h-4 text-gray-400 ml-auto group-hover:text-orange-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </router-link>
      </div>

      <!-- Map placeholder + Recent bookings -->
      <div class="grid grid-cols-5 gap-6">
        <!-- Map placeholder -->
        <div class="col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Theo dõi xe trực tiếp</h3>
            <router-link to="/admin/trips/live"
              class="text-xs text-red-600 hover:text-red-700 font-medium">Xem bản đồ đầy đủ →</router-link>
          </div>
          <div class="bg-slate-100 h-56 flex flex-col items-center justify-center gap-2 text-gray-400">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            <p class="text-sm font-medium">Google Maps</p>
            <p class="text-xs">{{ stats.active_trips }} xe đang hoạt động</p>
            <router-link to="/admin/trips/live"
              class="mt-1 px-3 py-1.5 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700">
              Mở bản đồ
            </router-link>
          </div>
        </div>

        <!-- Recent bookings -->
        <div class="col-span-3 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-gray-900">Booking gần đây</h3>
          </div>
          <div v-if="recentBookings.length === 0" class="py-12 text-center text-gray-400 text-sm">
            Chưa có booking nào hôm nay
          </div>
          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Mã vé</th>
                  <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Khách hàng</th>
                  <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Tuyến</th>
                  <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Giá vé</th>
                  <th class="px-4 py-2.5 text-center text-xs font-medium text-gray-500 uppercase tracking-wide">Trạng thái</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="b in recentBookings" :key="b.code" class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 font-mono text-xs text-gray-900">{{ b.code }}</td>
                  <td class="px-4 py-3 text-gray-700">{{ b.customer }}</td>
                  <td class="px-4 py-3 text-gray-600 text-xs">{{ b.route }}</td>
                  <td class="px-4 py-3 text-right font-medium text-gray-900">{{ formatCurrency(b.amount) }}</td>
                  <td class="px-4 py-3 text-center">
                    <span :class="['inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                      statusMap[b.status]?.class ?? 'bg-gray-100 text-gray-600']">
                      {{ statusMap[b.status]?.label ?? b.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
