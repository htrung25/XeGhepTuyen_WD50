import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export interface DriverTrip {
  id: string
  tracking_code: string
  depart_at: string
  arrive_at: string
  status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled'
  price: number
  available_seats: number
  route: { origin_city: string; dest_city: string; est_duration_min: number }
  vehicle: { plate_number: string; vehicle_type: string; seat_count: number }
  passengers_count: number
  checked_in_count: number
}

export interface Passenger {
  id: string
  booking_id: string
  passenger_name: string
  passenger_phone: string
  seat_codes: string[]
  pickup_stop: { stop_name: string; address: string }
  dropoff_stop: { stop_name: string; address: string }
  booking_status: string
  checked_in: boolean
  payment_method?: string
  payment_status?: string
  amount_due?: number
}

export interface EarningsData {
  today: number
  week: number
  month: number
  total_trips: number
  total_passengers: number
  total_km: number
  daily_breakdown: { date: string; amount: number; trips: number }[]
}

export interface DriverLocation {
  lat: number
  lng: number
  speed: number | null
  heading: number | null
  accuracy: number | null
  updated_at: string
}

export const useDriverStore = defineStore('driver', () => {
  // ─── Trips ─────────────────────────────────────────────────────────────
  const todayTrips      = ref<DriverTrip[]>([])
  const activeTrip      = ref<DriverTrip | null>(null)
  const passengers      = ref<Passenger[]>([])
  const todayTripsCount = ref(0)

  // ─── Legacy scalar earnings (kept for backward compat) ─────────────────
  const weekEarnings  = ref(0)
  const todayEarnings = ref(0)

  // ─── Structured earnings ────────────────────────────────────────────────
  const earnings = ref<EarningsData | null>(null)

  // ─── Online status & location ───────────────────────────────────────────
  const isOnline       = ref(false)
  const currentLocation = ref<DriverLocation | null>(null)

  // ─── Computed ───────────────────────────────────────────────────────────
  const activeOrNextTrip = computed(() =>
    todayTrips.value.find(t => t.status === 'in_progress') ??
    todayTrips.value.find(t => t.status === 'scheduled') ??
    null
  )

  // ─── Actions ────────────────────────────────────────────────────────────
  function setEarnings(data: EarningsData) {
    earnings.value      = data
    todayEarnings.value = data.today
    weekEarnings.value  = data.week
  }

  function setOnlineStatus(online: boolean) {
    isOnline.value = online
  }

  function updateLocation(loc: Omit<DriverLocation, 'updated_at'>) {
    currentLocation.value = { ...loc, updated_at: new Date().toISOString() }
  }

  return {
    // state
    todayTrips,
    activeTrip,
    passengers,
    todayTripsCount,
    weekEarnings,
    todayEarnings,
    earnings,
    isOnline,
    currentLocation,
    // computed
    activeOrNextTrip,
    // actions
    setEarnings,
    setOnlineStatus,
    updateLocation,
  }
})
