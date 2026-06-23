<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { adminApi } from '@/api/admin.api'

interface Trip {
  id: string
  tracking_code: string
  status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled'
  depart_at: string
  arrive_at: string
  route: { origin_city: string; dest_city: string }
  vehicle: { plate_number: string; vehicle_type: string }
  driver: { full_name: string; phone: string } | null
  operator: { company_name: string }
  passengers_count: number
  available_seats: number
  total_seats: number
}

const router    = useRouter()
const trips     = ref<Trip[]>([])
const loading   = ref(true)
const error     = ref('')
const search    = ref('')
const statusTab = ref<'all' | 'scheduled' | 'in_progress' | 'completed' | 'cancelled'>('all')
const dateFrom  = ref('')
const dateTo    = ref('')
const page      = ref(1)
const totalPages = ref(1)
const totalCount = ref(0)

const cancelModal  = ref(false)
const cancelTarget = ref<Trip | null>(null)
const cancelReason = ref('')
const cancelLoading = ref(false)

const statusConfig: Record<string, { label: string; cls: string }> = {
  scheduled:   { label: 'Đã lên lịch',   cls: 'bg-blue-50 text-blue-700' },
  in_progress: { label: 'Đang chạy',     cls: 'bg-green-50 text-green-700' },
  completed:   { label: 'Hoàn thành',    cls: 'bg-gray-100 text-gray-700' },
  cancelled:   { label: 'Đã huỷ',        cls: 'bg-red-50 text-red-700' },
}

const tabs = [
  { v: 'all',         l: 'Tất cả' },
  { v: 'scheduled',   l: 'Đã lên lịch' },
  { v: 'in_progress', l: 'Đang chạy' },
  { v: 'completed',   l: 'Hoàn thành' },
  { v: 'cancelled',   l: 'Đã huỷ' },
]

async function fetchTrips() {
  loading.value = true
  error.value   = ''
  const params: Record<string, unknown> = { page: page.value }
  if (search.value.trim())    params.search    = search.value.trim()
  if (statusTab.value !== 'all') params.status = statusTab.value
  if (dateFrom.value)         params.from_date = dateFrom.value
  if (dateTo.value)           params.to_date   = dateTo.value
  const { data, error: err } = await adminApi.getTrips(params)
  loading.value = false
  if (err) { error.value = err; return }
  trips.value      = data.data ?? data
  totalPages.value = data.meta?.last_page ?? 1
  totalCount.value = data.meta?.total ?? trips.value.length
}

function onFilter() {
  page.value = 1
  fetchTrips()
}

function openCancelModal(trip: Trip) {
  cancelTarget.value = trip
  cancelReason.value = ''
  cancelModal.value  = true
}

async function confirmCancel() {
  if (!cancelTarget.value || !cancelReason.value.trim()) return
  cancelLoading.value = true
  const { error: err } = await adminApi.cancelTrip(cancelTarget.value.id, { reason: cancelReason.value })
  cancelLoading.value = false
  if (err) { alert(err); return }
  cancelModal.value = false
  fetchTrips()
}

function fmtDateTime(d: string) {
  return new Date(d).toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' })
}

onMounted(fetchTrips)
</script>

<template>
  <div class="p-6 max-w-7xl mx-auto">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Quản lý chuyến đi</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ totalCount }} chuyến trong hệ thống</p>
      </div>
      <button @click="$router.push('/admin/trips/live')"
        class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-colors">
        <span class="w-2 h-2 bg-green-200 rounded-full animate-pulse"/>
        Xem trực tiếp
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-5 space-y-3">
      <!-- Status tabs -->
      <div class="flex gap-1 bg-gray-100 rounded-lg p-1 w-fit">
        <button v-for="t in tabs" :key="t.v"
          @click="statusTab = t.v as typeof statusTab.value; onFilter()"
          :class="['px-3 py-1.5 text-xs font-medium rounded-md transition-colors',
            statusTab === t.v
              ? 'bg-white text-gray-900 shadow-sm'
              : 'text-gray-500 hover:text-gray-700']">
          {{ t.l }}
        </button>
      </div>

      <div class="flex flex-wrap gap-3 items-center">
        <!-- Search -->
        <div class="relative flex-1 min-w-[220px]">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <input v-model="search" type="text" placeholder="Tìm mã chuyến, tuyến đường..."
            class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
            @keyup.enter="onFilter" />
        </div>

        <!-- Date range -->
        <div class="flex items-center gap-2">
          <input v-model="dateFrom" type="date"
            class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" />
          <span class="text-gray-400 text-sm">–</span>
          <input v-model="dateTo" type="date"
            class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" />
        </div>

        <button @click="onFilter"
          class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
          Lọc
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="bg-white rounded-xl border border-gray-200 p-16 text-center">
      <div class="w-8 h-8 border-2 border-red-600 border-t-transparent rounded-full animate-spin mx-auto mb-3"/>
      <p class="text-sm text-gray-500">Đang tải...</p>
    </div>

    <div v-else-if="error" class="bg-white rounded-xl border border-gray-200 p-12 text-center">
      <p class="text-red-500 text-sm mb-4">{{ error }}</p>
      <button @click="fetchTrips" class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg">Thử lại</button>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mã / Tuyến</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Giờ chạy</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nhà xe</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tài xế / Xe</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Khách</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Trạng thái</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="trips.length === 0">
              <td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">Không có chuyến đi nào</td>
            </tr>
            <tr v-for="trip in trips" :key="trip.id" class="hover:bg-gray-50 transition-colors">
              <!-- Code / Route -->
              <td class="px-4 py-3">
                <p class="font-mono text-xs text-gray-500">{{ trip.tracking_code }}</p>
                <p class="font-medium text-gray-900 mt-0.5">
                  {{ trip.route.origin_city }} → {{ trip.route.dest_city }}
                </p>
              </td>
              <!-- Time -->
              <td class="px-4 py-3 text-gray-700">
                <p>{{ fmtDateTime(trip.depart_at) }}</p>
                <p class="text-xs text-gray-400">→ {{ fmtDateTime(trip.arrive_at) }}</p>
              </td>
              <!-- Operator -->
              <td class="px-4 py-3 text-gray-700">{{ trip.operator?.company_name ?? '—' }}</td>
              <!-- Driver / Vehicle -->
              <td class="px-4 py-3">
                <p class="text-gray-800">{{ trip.driver?.full_name ?? '—' }}</p>
                <p class="text-xs text-gray-400">{{ trip.vehicle?.plate_number }}</p>
              </td>
              <!-- Seats -->
              <td class="px-4 py-3 text-center">
                <span class="font-medium text-gray-800">{{ trip.passengers_count ?? 0 }}</span>
                <span class="text-gray-400 text-xs"> / {{ trip.total_seats ?? '?' }}</span>
              </td>
              <!-- Status -->
              <td class="px-4 py-3 text-center">
                <span :class="['inline-flex px-2.5 py-1 rounded-full text-xs font-semibold',
                  statusConfig[trip.status]?.cls ?? 'bg-gray-100 text-gray-600']">
                  {{ statusConfig[trip.status]?.label ?? trip.status }}
                </span>
              </td>
              <!-- Actions -->
              <td class="px-4 py-3 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <button v-if="trip.status === 'in_progress'"
                    @click="router.push('/admin/trips/live')"
                    class="px-2.5 py-1.5 bg-green-50 hover:bg-green-100 text-green-700 text-xs font-medium rounded-lg transition-colors">
                    Live
                  </button>
                  <button v-if="['scheduled','in_progress'].includes(trip.status)"
                    @click="openCancelModal(trip)"
                    class="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-medium rounded-lg transition-colors">
                    Huỷ
                  </button>
                  <span v-if="trip.status === 'cancelled'" class="text-xs text-gray-400">—</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200">
        <p class="text-xs text-gray-500">Trang {{ page }} / {{ totalPages }}</p>
        <div class="flex gap-2">
          <button :disabled="page <= 1" @click="page--; fetchTrips()"
            class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50">← Trước</button>
          <button :disabled="page >= totalPages" @click="page++; fetchTrips()"
            class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50">Sau →</button>
        </div>
      </div>
    </div>

    <!-- Cancel Modal -->
    <Teleport to="body">
      <div v-if="cancelModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="cancelModal = false" />
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-1">Huỷ chuyến đi</h3>
          <p class="text-sm text-gray-500 mb-4">
            Huỷ chuyến <strong>{{ cancelTarget?.tracking_code }}</strong>
            ({{ cancelTarget?.route?.origin_city }} → {{ cancelTarget?.route?.dest_city }})?
          </p>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              Lý do huỷ <span class="text-red-500">*</span>
            </label>
            <textarea v-model="cancelReason" rows="3" placeholder="Nhập lý do huỷ chuyến..."
              class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm resize-none
                     focus:outline-none focus:ring-2 focus:ring-red-500" />
          </div>
          <div class="flex gap-3 justify-end">
            <button @click="cancelModal = false"
              class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50">Huỷ</button>
            <button @click="confirmCancel" :disabled="!cancelReason.trim() || cancelLoading"
              class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded-xl font-medium
                     flex items-center gap-2 transition-colors">
              <svg v-if="cancelLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              Xác nhận huỷ
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
