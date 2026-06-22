<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { driverApi } from '@/api/driver.api'
import { useDriverStore } from '@/stores/driver.store'

const route  = useRoute()
const store  = useDriverStore()
const tripId = route.params.id as string

const trip        = ref<any>(null)
const passengers  = ref<any[]>([])
const isLoading   = ref(true)
const errorMsg    = ref('')
const arrivedLoading = ref(false)
const showIncident   = ref(false)
const incidentNote   = ref('')
const gpsActive      = ref(false)
const gpsLastUpdate  = ref<string | null>(null)
const currentPos     = ref({ lat: 21.0285, lng: 105.8542 }) // default Hanoi

let locationInterval: ReturnType<typeof setInterval> | null = null
let watchId: number | null = null
let mapInstance: any = null
let driverMarker: any = null
let mapRef: HTMLElement | null = null

const nextPassenger = computed(() => {
  return passengers.value.find(p => !p.checked_in && p.booking_status !== 'no_show') ?? null
})

const nextStop = computed(() => nextPassenger.value?.pickup_stop ?? null)

const checkedCount  = computed(() => passengers.value.filter(p => p.checked_in).length)
const pendingStops  = computed(() => passengers.value.filter(p => !p.checked_in && p.booking_status !== 'no_show'))

function openGoogleMaps() {
  const dest = nextStop.value
  if (!dest?.lat || !dest?.lng) {
    const query = encodeURIComponent((dest?.address ?? nextStop.value?.stop_name) || 'Hải Phòng')
    window.open(`https://maps.google.com/?q=${query}`, '_blank')
    return
  }
  window.open(`https://www.google.com/maps/dir/?api=1&destination=${dest.lat},${dest.lng}&travelmode=driving`, '_blank')
}

function initMap() {
  if (!(window as any).google || !mapRef) return
  const google = (window as any).google
  mapInstance = new google.maps.Map(mapRef, {
    center: currentPos.value,
    zoom: 13,
    mapTypeControl: false,
    fullscreenControl: false,
    streetViewControl: false,
    styles: [{ featureType: 'poi', stylers: [{ visibility: 'off' }] }],
  })
  driverMarker = new google.maps.Marker({
    position: currentPos.value,
    map: mapInstance,
    title: 'Vị trí của bạn',
    icon: {
      url: 'data:image/svg+xml,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36">' +
        '<circle cx="18" cy="18" r="16" fill="#16A34A" stroke="white" stroke-width="3"/>' +
        '<text x="18" y="23" text-anchor="middle" fill="white" font-size="14">🚐</text>' +
        '</svg>'
      ),
      scaledSize: new google.maps.Size(36, 36),
      anchor: new google.maps.Point(18, 18),
    },
  })
}

function updateMapPosition(lat: number, lng: number) {
  currentPos.value = { lat, lng }
  if (mapInstance && driverMarker) {
    driverMarker.setPosition({ lat, lng })
    mapInstance.panTo({ lat, lng })
  }
}

async function sendLocation(lat: number, lng: number) {
  await driverApi.updateLocation({ trip_id: tripId, lat, lng })
  gpsLastUpdate.value = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

function startTracking() {
  if (!navigator.geolocation) return
  gpsActive.value = true
  watchId = navigator.geolocation.watchPosition(
    (pos) => {
      updateMapPosition(pos.coords.latitude, pos.coords.longitude)
    },
    () => { gpsActive.value = false },
    { enableHighAccuracy: true, maximumAge: 5000, timeout: 10000 }
  )
  locationInterval = setInterval(async () => {
    await sendLocation(currentPos.value.lat, currentPos.value.lng)
  }, 15000)
  // Send immediately on start
  sendLocation(currentPos.value.lat, currentPos.value.lng)
}

async function loadData() {
  const [tripRes, passRes] = await Promise.all([
    driverApi.getTrip(tripId),
    driverApi.getPassengers(tripId),
  ])
  if (tripRes.error || passRes.error) {
    errorMsg.value = 'Không thể tải dữ liệu chuyến'
    return
  }
  trip.value       = tripRes.data
  passengers.value = passRes.data ?? []
  store.passengers = passengers.value
}

function fmtTime(iso: string) {
  return new Date(iso).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}

onMounted(async () => {
  isLoading.value = true
  await loadData()
  isLoading.value = false
  // Init map after DOM ready
  setTimeout(() => {
    mapRef = document.getElementById('nav-map')
    initMap()
  }, 100)
  startTracking()
})

onUnmounted(() => {
  if (locationInterval) clearInterval(locationInterval)
  if (watchId !== null) navigator.geolocation.clearWatch(watchId)
})
</script>

<template>
  <div class="p-6 max-w-[1400px] mx-auto">

    <!-- Header -->
    <div class="flex items-center justify-between mb-5">
      <div>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
          <router-link :to="`/driver/trips/${tripId}`" class="hover:text-green-600 transition-colors">← Chi tiết chuyến</router-link>
          <span>/</span>
          <span class="text-gray-700 font-medium">Điều hướng</span>
        </div>
        <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2">
          Điều hướng chuyến đi
          <span class="inline-flex items-center gap-1.5 text-sm font-normal px-3 py-1 rounded-full bg-green-100 text-green-700">
            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse" />
            Đang chạy
          </span>
        </h1>
      </div>

      <!-- GPS status -->
      <div :class="['flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium',
        gpsActive ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500']">
        <span :class="['w-2.5 h-2.5 rounded-full', gpsActive ? 'bg-green-500 animate-pulse' : 'bg-gray-400']" />
        <span>{{ gpsActive ? '🟢 GPS đang gửi' : '⚪ GPS tắt' }}</span>
        <span v-if="gpsLastUpdate" class="text-xs opacity-70">· {{ gpsLastUpdate }}</span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="grid grid-cols-[1fr_380px] gap-5">
      <div class="h-[600px] bg-gray-200 rounded-xl animate-pulse" />
      <div class="space-y-4">
        <div v-for="i in 3" :key="i" class="h-40 bg-gray-200 rounded-xl animate-pulse" />
      </div>
    </div>

    <!-- Error -->
    <div v-else-if="errorMsg"
      class="bg-red-50 border border-red-200 rounded-xl p-6 text-red-700 text-center">
      <p class="font-medium mb-3">{{ errorMsg }}</p>
      <button @click="loadData" class="text-sm font-semibold underline">Thử lại</button>
    </div>

    <!-- Content -->
    <div v-else class="grid grid-cols-[1fr_380px] gap-5 items-start">

      <!-- ─── LEFT: Map ─────────────────────────────────────── -->
      <div class="space-y-3">
        <!-- Map container -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div id="nav-map" class="w-full h-[560px] bg-slate-100">
            <!-- Fallback when Google Maps not available -->
            <div class="w-full h-full flex flex-col items-center justify-center text-gray-400">
              <div class="text-6xl mb-4 animate-bounce">🚐</div>
              <p class="font-semibold text-gray-600 mb-1">Đang điều hướng GPS</p>
              <p class="text-sm font-mono text-green-600">
                {{ currentPos.lat.toFixed(5) }}, {{ currentPos.lng.toFixed(5) }}
              </p>
              <p class="text-xs text-gray-400 mt-2">Cập nhật mỗi 15 giây</p>
            </div>
          </div>
        </div>

        <!-- Map action buttons -->
        <div class="flex gap-3">
          <button @click="openGoogleMaps"
            class="flex-1 flex items-center justify-center gap-2 py-3 bg-white border border-gray-200 shadow-sm rounded-xl text-sm font-semibold text-gray-700 hover:border-green-400 hover:text-green-700 transition-colors">
            🗺️ Mở Google Maps
          </button>
          <button @click="showIncident = true"
            class="flex items-center justify-center gap-2 px-5 py-3 bg-red-50 border border-red-200 rounded-xl text-sm font-semibold text-red-600 hover:bg-red-100 transition-colors">
            🚨 Báo sự cố
          </button>
        </div>
      </div>

      <!-- ─── RIGHT: Navigation panel ──────────────────────── -->
      <div class="space-y-4 self-start sticky top-6">

        <!-- Stop progress -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
          <div class="flex items-center justify-between mb-2 text-sm">
            <span class="text-gray-500 font-medium">Tiến độ đón khách</span>
            <span class="font-bold text-green-600">{{ checkedCount }}/{{ passengers.length }}</span>
          </div>
          <div class="w-full bg-gray-100 rounded-full h-2">
            <div class="bg-green-500 h-2 rounded-full transition-all duration-500"
              :style="{ width: passengers.length > 0 ? (checkedCount / passengers.length * 100) + '%' : '0%' }" />
          </div>
        </div>

        <!-- Current stop card -->
        <div v-if="nextStop" class="bg-white rounded-xl border-2 border-green-500 shadow-sm p-5">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-7 h-7 bg-green-600 rounded-full flex items-center justify-center text-white text-xs font-black flex-shrink-0">
              {{ checkedCount + 1 }}
            </div>
            <span class="text-xs font-semibold text-green-600 uppercase tracking-wider">Điểm đón tiếp theo</span>
          </div>

          <h2 class="text-xl font-black text-gray-900 mb-1">{{ nextStop.stop_name }}</h2>
          <p class="text-sm text-gray-500 mb-4">{{ nextStop.address }}</p>

          <!-- ETA (mock) -->
          <div class="flex items-center gap-4 mb-4 p-3 bg-green-50 rounded-xl">
            <div>
              <p class="text-2xl font-black text-green-700">~{{ Math.floor(Math.random() * 8 + 3) }} phút</p>
              <p class="text-xs text-green-600">{{ (Math.random() * 4 + 0.5).toFixed(1) }} km</p>
            </div>
            <div class="w-px h-10 bg-green-200" />
            <p class="text-xs text-green-600 flex-1">Dựa trên vị trí GPS hiện tại</p>
          </div>

          <!-- Passenger waiting -->
          <div v-if="nextPassenger" class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center font-bold text-green-700 shrink-0">
              {{ nextPassenger.passenger_name?.[0] ?? 'K' }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-gray-900 truncate">{{ nextPassenger.passenger_name }}</p>
              <p class="text-xs text-gray-400 font-mono">Ghế {{ nextPassenger.seat_codes?.join(', ') }}</p>
            </div>
            <a :href="`tel:${nextPassenger.passenger_phone}`"
              class="flex items-center gap-1.5 px-3 py-2 bg-green-600 text-white text-xs font-semibold rounded-lg hover:bg-green-700 transition-colors">
              📞 Gọi ngay
            </a>
          </div>

          <!-- Arrived button -->
          <router-link :to="`/driver/checkin/${tripId}`"
            class="block w-full py-3.5 bg-green-600 hover:bg-green-700 text-white text-center font-bold rounded-xl transition-colors">
            ✅ Đã đến — Quét QR
          </router-link>
        </div>

        <!-- All checked in -->
        <div v-else class="bg-green-50 border border-green-200 rounded-xl p-5 text-center">
          <div class="text-3xl mb-2">🎉</div>
          <p class="font-bold text-green-700">Đã đón hết hành khách!</p>
          <p class="text-sm text-green-600 mt-1">Tiếp tục đến điểm trả hàng</p>
        </div>

        <!-- Upcoming stops list -->
        <div v-if="pendingStops.length > 1" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Điểm đón còn lại</h3>
          </div>
          <div class="divide-y divide-gray-100">
            <div v-for="(p, i) in pendingStops.slice(1, 4)" :key="p.id"
              class="flex items-center gap-3 px-4 py-3">
              <div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600 shrink-0">
                {{ checkedCount + i + 2 }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">{{ p.pickup_stop?.stop_name }}</p>
                <p class="text-xs text-gray-400">{{ p.passenger_name }}</p>
              </div>
              <span class="text-xs text-gray-400">Ghế {{ p.seat_codes?.[0] }}</span>
            </div>
            <div v-if="pendingStops.length > 4"
              class="px-4 py-2 text-xs text-gray-400 text-center">
              +{{ pendingStops.length - 4 }} điểm đón nữa
            </div>
          </div>
        </div>

        <!-- Destination -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center gap-3">
          <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center text-red-600 shrink-0">🏁</div>
          <div>
            <p class="text-xs text-gray-400 font-medium">Điểm đến cuối</p>
            <p class="font-semibold text-gray-900">{{ trip?.route?.dest_city ?? 'Hải Phòng' }}</p>
          </div>
          <div class="ml-auto text-right">
            <p class="text-xs text-gray-400">ETA</p>
            <p class="font-bold text-gray-700">~{{ trip?.route?.est_duration_min ?? 120 }} phút</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ─── Incident modal ─────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showIncident"
        class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4"
        @click.self="showIncident = false">
        <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full">
          <h3 class="text-lg font-bold text-gray-900 mb-1">Báo sự cố</h3>
          <p class="text-sm text-gray-500 mb-4">Mô tả sự cố đang xảy ra</p>
          <textarea v-model="incidentNote" rows="3"
            placeholder="Ví dụ: Xe bị hỏng, tai nạn, kẹt xe..."
            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 resize-none mb-4" />
          <div class="flex gap-3">
            <button @click="showIncident = false"
              class="flex-1 py-3 border border-gray-200 text-gray-600 rounded-xl font-medium hover:bg-gray-50 transition-colors">
              Hủy
            </button>
            <button @click="showIncident = false"
              class="flex-1 py-3 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 transition-colors">
              Gửi báo cáo
            </button>
          </div>
          <a href="tel:18009999"
            class="block text-center mt-3 text-sm text-red-600 font-medium hover:underline">
            Gọi ngay hotline hỗ trợ: 1800-9999
          </a>
        </div>
      </div>
    </Teleport>
  </div>
</template>
