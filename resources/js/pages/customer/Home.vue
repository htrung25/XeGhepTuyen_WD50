<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCustomerStore } from '@/stores/customer.store'

const router = useRouter()
const store  = useCustomerStore()

const tripType   = ref<'one_way' | 'round_trip'>('one_way')
const fromCity   = ref('Hà Nội')
const toCity     = ref('Hải Phòng')
const passengers = ref(1)
const travelDate = ref(new Date().toISOString().split('T')[0])
const loadingPopular = ref(true)

const popularRoutes = ref([
  { from: 'Hà Nội', to: 'Hải Phòng', price: 120000, duration: '~2.5 giờ', desc: 'Tuyến phổ biến nhất', trips: 48 },
  { from: 'Hải Phòng', to: 'Hà Nội', price: 120000, duration: '~2.5 giờ', desc: 'Chiều ngược lại', trips: 45 },
  { from: 'Hà Nội', to: 'Hải Phòng', price: 150000, duration: '~2 giờ', desc: 'Xe VIP 7 chỗ', trips: 12 },
])

const features = [
  {
    icon: '📍',
    title: 'Đón tận nơi',
    desc: 'Chọn từ 10+ điểm đón cố định trên tuyến, nhập địa chỉ cụ thể để tài xế tìm dễ hơn.',
  },
  {
    icon: '📡',
    title: 'Theo dõi GPS',
    desc: 'Xem vị trí xe real-time trên bản đồ, biết chính xác xe đến điểm đón sau bao nhiêu phút.',
  },
  {
    icon: '💳',
    title: 'Thanh toán online',
    desc: 'Thanh toán qua MoMo, VNPay hoặc ví XeGhep. Nhận vé QR điện tử ngay lập tức.',
  },
]

const minDate = computed(() => new Date().toISOString().split('T')[0])

function swapCities() {
  ;[fromCity.value, toCity.value] = [toCity.value, fromCity.value]
}

function adjustPassengers(delta: number) {
  const next = passengers.value + delta
  if (next >= 1 && next <= 4) passengers.value = next
}

function search() {
  if (!fromCity.value || !toCity.value || !travelDate.value) return
  store.searchParams = {
    from_city: fromCity.value,
    to_city: toCity.value,
    date: travelDate.value,
    passengers: passengers.value,
    trip_type: tripType.value,
  }
  router.push('/search')
}

function searchPopular(from: string, to: string) {
  fromCity.value = from
  toCity.value   = to
  search()
}

function fmt(v: number) {
  return new Intl.NumberFormat('vi-VN').format(v) + 'đ'
}

onMounted(() => { loadingPopular.value = false })
</script>

<template>
  <div>
    <!-- ─── Hero Section ─────────────────────────────────────── -->
    <section class="relative bg-gradient-to-br from-blue-700 via-blue-600 to-blue-800 py-20 overflow-hidden">
      <!-- Background decoration -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-10 left-10 w-40 h-40 bg-white rounded-full blur-3xl" />
        <div class="absolute bottom-10 right-20 w-60 h-60 bg-blue-300 rounded-full blur-3xl" />
      </div>

      <div class="relative max-w-5xl mx-auto px-6">
        <!-- Hero text -->
        <div class="text-center mb-10">
          <h1 class="text-4xl md:text-5xl font-bold text-white mb-4 leading-tight">
            Đặt xe ghép<br>
            <span class="text-blue-200">Hà Nội ↔ Hải Phòng</span>
          </h1>
          <p class="text-blue-100 text-lg max-w-lg mx-auto">
            Đón tận nơi · Theo dõi GPS real-time · Thanh toán điện tử
          </p>
        </div>

        <!-- Search Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-6 md:p-8 max-w-2xl mx-auto">
          <!-- Trip type tabs -->
          <div class="flex gap-1 bg-gray-100 rounded-xl p-1 mb-6 w-fit">
            <button
              v-for="tab in [{ key: 'one_way', label: 'Một chiều' }, { key: 'round_trip', label: 'Khứ hồi' }]"
              :key="tab.key"
              @click="tripType = tab.key as any"
              :class="['px-5 py-2 rounded-lg text-sm font-medium transition-all',
                tripType === tab.key
                  ? 'bg-white text-blue-600 shadow-sm font-semibold'
                  : 'text-gray-500 hover:text-gray-700']">
              {{ tab.label }}
            </button>
          </div>

          <!-- From / To row -->
          <div class="grid grid-cols-[1fr_auto_1fr] gap-3 items-center mb-4">
            <div class="border border-gray-200 rounded-xl p-3 bg-gray-50 hover:border-blue-300 transition-colors">
              <label class="block text-xs font-medium text-gray-500 mb-1">Điểm đi</label>
              <select v-model="fromCity"
                class="w-full bg-transparent text-gray-900 font-semibold text-sm focus:outline-none appearance-none cursor-pointer">
                <option value="Hà Nội">Hà Nội</option>
                <option value="Hải Phòng">Hải Phòng</option>
              </select>
            </div>

            <button @click="swapCities"
              class="w-10 h-10 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-full flex items-center justify-center transition-colors shrink-0">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
              </svg>
            </button>

            <div class="border border-gray-200 rounded-xl p-3 bg-gray-50 hover:border-blue-300 transition-colors">
              <label class="block text-xs font-medium text-gray-500 mb-1">Điểm đến</label>
              <select v-model="toCity"
                class="w-full bg-transparent text-gray-900 font-semibold text-sm focus:outline-none appearance-none cursor-pointer">
                <option value="Hà Nội">Hà Nội</option>
                <option value="Hải Phòng">Hải Phòng</option>
              </select>
            </div>
          </div>

          <!-- Date + Passengers + Button -->
          <div class="grid grid-cols-[1fr_auto_auto] gap-3 items-end">
            <div class="border border-gray-200 rounded-xl p-3 bg-gray-50 hover:border-blue-300 transition-colors">
              <label class="block text-xs font-medium text-gray-500 mb-1">Ngày đi</label>
              <input v-model="travelDate" type="date" :min="minDate"
                class="w-full bg-transparent text-gray-900 font-semibold text-sm focus:outline-none cursor-pointer" />
            </div>

            <div class="border border-gray-200 rounded-xl p-3 bg-gray-50">
              <label class="block text-xs font-medium text-gray-500 mb-1">Hành khách</label>
              <div class="flex items-center gap-2">
                <button @click="adjustPassengers(-1)" :disabled="passengers <= 1"
                  class="w-6 h-6 rounded-full border border-gray-300 flex items-center justify-center text-gray-600 hover:border-blue-400 disabled:opacity-40 text-sm font-bold transition-colors">−</button>
                <span class="w-4 text-center font-bold text-gray-900 text-sm">{{ passengers }}</span>
                <button @click="adjustPassengers(1)" :disabled="passengers >= 4"
                  class="w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center text-white hover:bg-blue-700 disabled:opacity-40 text-sm font-bold transition-colors">+</button>
              </div>
            </div>

            <button @click="search"
              class="h-full min-h-[64px] px-8 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-xl transition-colors shadow-lg shadow-blue-200 flex items-center gap-2 whitespace-nowrap">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              Tìm chuyến
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- ─── Features ─────────────────────────────────────────── -->
    <section class="py-16 bg-white">
      <div class="max-w-5xl mx-auto px-6">
        <h2 class="text-2xl font-bold text-gray-900 text-center mb-10">Tại sao chọn XeGhep.vn?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div v-for="f in features" :key="f.title"
            class="text-center p-6 rounded-2xl bg-gray-50 hover:bg-blue-50 transition-colors group">
            <div class="text-4xl mb-4">{{ f.icon }}</div>
            <h3 class="font-bold text-gray-900 mb-2 group-hover:text-blue-700 transition-colors">{{ f.title }}</h3>
            <p class="text-sm text-gray-500 leading-relaxed">{{ f.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ─── Popular Routes ────────────────────────────────────── -->
    <section class="py-16 bg-slate-50">
      <div class="max-w-5xl mx-auto px-6">
        <div class="flex items-center justify-between mb-8">
          <h2 class="text-2xl font-bold text-gray-900">Chuyến phổ biến</h2>
          <router-link to="/search" class="text-sm font-medium text-blue-600 hover:underline">
            Xem tất cả →
          </router-link>
        </div>

        <!-- Skeletons -->
        <div v-if="loadingPopular" class="grid grid-cols-1 md:grid-cols-3 gap-5">
          <div v-for="i in 3" :key="i" class="bg-white rounded-2xl p-5 h-40 animate-pulse">
            <div class="h-5 bg-gray-200 rounded mb-3 w-3/4" />
            <div class="h-3 bg-gray-100 rounded mb-2 w-1/2" />
            <div class="h-8 bg-gray-100 rounded mt-6" />
          </div>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-5">
          <div v-for="r in popularRoutes" :key="r.from + r.to + r.desc"
            class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-blue-200 hover:shadow-md transition-all cursor-pointer group"
            @click="searchPopular(r.from, r.to)">
            <div class="flex items-center justify-between mb-2">
              <span class="font-bold text-gray-900 text-base group-hover:text-blue-700 transition-colors">
                {{ r.from }} → {{ r.to }}
              </span>
              <span class="text-xs bg-blue-50 text-blue-600 font-semibold px-2.5 py-1 rounded-full">
                {{ fmt(r.price) }}
              </span>
            </div>
            <p class="text-xs text-gray-400 mb-1">{{ r.duration }}</p>
            <p class="text-xs text-gray-500">{{ r.desc }}</p>
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
              <span class="text-xs text-gray-400">{{ r.trips }} chuyến/ngày</span>
              <span class="text-xs text-blue-600 font-medium group-hover:underline">Xem chuyến →</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ─── CTA Banner ────────────────────────────────────────── -->
    <section class="py-16 bg-blue-600">
      <div class="max-w-5xl mx-auto px-6 text-center">
        <h2 class="text-2xl md:text-3xl font-bold text-white mb-4">Sẵn sàng lên đường?</h2>
        <p class="text-blue-100 mb-8 max-w-md mx-auto">Đặt vé ngay hôm nay, được đón tận nơi, không cần ra bến xe.</p>
        <button @click="search"
          class="px-8 py-4 bg-white text-blue-700 font-bold rounded-xl hover:bg-blue-50 transition-colors shadow-lg text-sm">
          Tìm chuyến ngay
        </button>
      </div>
    </section>
  </div>
</template>
