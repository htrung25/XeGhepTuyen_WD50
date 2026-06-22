<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { operatorApi } from '@/api/operator.api'

interface Stop {
  id?: string
  stop_name: string
  stop_address: string
  stop_order: number
  lat: string
  lng: string
  is_pickup: boolean
  is_dropoff: boolean
}

interface RouteRow {
  id: string
  route_code: string
  origin_city: string
  dest_city: string
  distance_km: number
  duration_hours: number
  stops_count?: number
  is_active: boolean
}

const routes   = ref<RouteRow[]>([])
const isLoading = ref(true)
const errorMsg  = ref('')

// Modal state
const showModal  = ref(false)
const saving     = ref(false)
const saveError  = ref('')

const form = ref({
  route_code:     '',
  origin_city:    'Hà Nội',
  dest_city:      'Hải Phòng',
  distance_km:    105,
  duration_hours: 2.5,
  description:    '',
  stops: [] as Stop[],
})

const addStop = () => {
  form.value.stops.push({
    stop_name:    '',
    stop_address: '',
    stop_order:   form.value.stops.length + 1,
    lat:          '',
    lng:          '',
    is_pickup:    true,
    is_dropoff:   false,
  })
}

const removeStop = (idx: number) => {
  form.value.stops.splice(idx, 1)
  form.value.stops.forEach((s, i) => (s.stop_order = i + 1))
}

const loadRoutes = async () => {
  isLoading.value = true
  errorMsg.value  = ''
  const { data, error } = await operatorApi.getRoutes()
  isLoading.value = false
  if (error) { errorMsg.value = 'Không thể tải danh sách tuyến đường'; return }
  routes.value = data ?? []
}

const openCreate = () => {
  form.value = { route_code: '', origin_city: 'Hà Nội', dest_city: 'Hải Phòng', distance_km: 105, duration_hours: 2.5, description: '', stops: [] }
  addStop()
  showModal.value = true
  saveError.value = ''
}

const saveRoute = async () => {
  if (!form.value.route_code || form.value.stops.length < 2) {
    saveError.value = 'Vui lòng nhập mã tuyến và ít nhất 2 điểm dừng'
    return
  }
  saving.value    = true
  saveError.value = ''
  const { error } = await operatorApi.createRoute(form.value)
  saving.value    = false
  if (error) { saveError.value = error; return }
  showModal.value = false
  await loadRoutes()
}

const deleteRoute = async (id: string) => {
  if (!confirm('Bạn có chắc muốn xóa tuyến đường này?')) return
  const { error } = await operatorApi.deleteRoute(id)
  if (error) { alert(error); return }
  await loadRoutes()
}

onMounted(() => loadRoutes())
</script>

<template>
  <div class="p-6 space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-slate-800">Quản lý tuyến đường</h1>
        <p class="text-sm text-slate-500 mt-0.5">Thiết lập tuyến và điểm dừng</p>
      </div>
      <button
        class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"
        @click="openCreate"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Thêm tuyến mới
      </button>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="animate-pulse bg-white rounded-xl h-16 border border-slate-200" />
    </div>

    <!-- Error -->
    <div v-else-if="errorMsg"
         class="bg-red-50 border border-red-200 rounded-xl p-5 flex items-center gap-4 text-red-700">
      <svg class="w-6 h-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      {{ errorMsg }}
      <button class="ml-auto underline text-sm" @click="loadRoutes">Thử lại</button>
    </div>

    <template v-else>
      <!-- Empty -->
      <div v-if="routes.length === 0"
           class="bg-white rounded-xl border border-slate-200 py-16 flex flex-col items-center text-slate-400">
        <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
        </svg>
        <p class="font-medium">Chưa có tuyến đường nào</p>
        <button class="mt-3 px-4 py-2 bg-amber-500 text-white text-sm rounded-lg hover:bg-amber-600 transition-colors" @click="openCreate">
          Tạo tuyến đầu tiên
        </button>
      </div>

      <!-- Table -->
      <div v-else class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tên tuyến</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Điểm đi</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Điểm đến</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Số điểm dừng</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Khoảng cách</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Trạng thái</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Hành động</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="route in routes" :key="route.id" class="hover:bg-slate-50 transition-colors">
              <td class="px-6 py-4 font-medium text-slate-800">{{ route.origin_city }} → {{ route.dest_city }}</td>
              <td class="px-6 py-4 text-sm text-slate-700">{{ route.origin_city }}</td>
              <td class="px-6 py-4 text-sm text-slate-700">{{ route.dest_city }}</td>
              <td class="px-6 py-4 text-sm text-slate-600">{{ route.stops_count ?? '—' }} điểm</td>
              <td class="px-6 py-4 text-sm text-slate-600">{{ route.distance_km }} km</td>
              <td class="px-6 py-4">
                <span :class="route.is_active
                  ? 'bg-green-100 text-green-700'
                  : 'bg-slate-100 text-slate-500'"
                  class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium">
                  {{ route.is_active ? 'Đang hoạt động' : 'Tạm ngừng' }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button class="text-amber-600 hover:text-amber-700 text-sm font-medium transition-colors">
                    Chỉnh sửa
                  </button>
                  <span class="text-slate-300">|</span>
                  <button
                    class="text-red-500 hover:text-red-600 text-sm font-medium transition-colors"
                    @click="deleteRoute(route.id)"
                  >
                    Xóa
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- Create Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">

          <!-- Modal header -->
          <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between flex-shrink-0">
            <h2 class="text-lg font-semibold text-slate-800">Thêm tuyến đường mới</h2>
            <button class="text-slate-400 hover:text-slate-600 transition-colors" @click="showModal = false">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Modal body -->
          <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4">

            <!-- Error -->
            <div v-if="saveError" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700">
              {{ saveError }}
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Mã tuyến *</label>
                <input v-model="form.route_code" placeholder="VD: HNHP"
                  class="w-full px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20" />
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Khoảng cách (km)</label>
                <input v-model.number="form.distance_km" type="number"
                  class="w-full px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20" />
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Điểm đi</label>
                <input v-model="form.origin_city"
                  class="w-full px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20" />
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Điểm đến</label>
                <input v-model="form.dest_city"
                  class="w-full px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20" />
              </div>
            </div>

            <!-- Stops timeline -->
            <div>
              <div class="flex items-center justify-between mb-3">
                <label class="text-sm font-semibold text-slate-700">Điểm dừng</label>
                <button
                  class="text-sm text-amber-600 hover:text-amber-700 font-medium flex items-center gap-1"
                  @click="addStop"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  Thêm điểm dừng
                </button>
              </div>

              <div class="space-y-3">
                <div v-for="(stop, idx) in form.stops" :key="idx"
                     class="flex gap-3 items-start">
                  <!-- Timeline dot -->
                  <div class="flex flex-col items-center pt-3 flex-shrink-0">
                    <div :class="idx === 0 ? 'bg-green-500' : idx === form.stops.length - 1 ? 'bg-red-500' : 'bg-amber-500'"
                         class="w-3 h-3 rounded-full" />
                    <div v-if="idx < form.stops.length - 1" class="w-0.5 h-8 bg-slate-200 mt-1" />
                  </div>

                  <!-- Stop form -->
                  <div class="flex-1 bg-slate-50 rounded-lg p-3 space-y-2">
                    <div class="flex gap-2">
                      <input v-model="stop.stop_name" placeholder="Tên điểm dừng"
                        class="flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-amber-500" />
                      <button class="text-slate-400 hover:text-red-500 transition-colors" @click="removeStop(idx)">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                    <input v-model="stop.stop_address" placeholder="Địa chỉ"
                      class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-amber-500" />
                    <div class="flex gap-4">
                      <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                        <input v-model="stop.is_pickup" type="checkbox" class="accent-amber-500" />
                        Điểm đón
                      </label>
                      <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                        <input v-model="stop.is_dropoff" type="checkbox" class="accent-amber-500" />
                        Điểm trả
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal footer -->
          <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-3 flex-shrink-0">
            <button
              class="px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors"
              @click="showModal = false"
            >
              Hủy
            </button>
            <button
              :disabled="saving"
              class="px-5 py-2.5 text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 disabled:bg-amber-300 rounded-lg transition-colors flex items-center gap-2"
              @click="saveRoute"
            >
              <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              {{ saving ? 'Đang lưu...' : 'Lưu tuyến đường' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
