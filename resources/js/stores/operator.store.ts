import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { operatorApi } from '@/api/operator.api'

interface Trip {
  id: string
  trip_code: string
  route_name: string
  depart_at: string
  status: string
  booking_count: number
  total_seats: number
  price: number
}

interface Route {
  id: string
  route_code: string
  name: string
  origin: string
  destination: string
  distance_km: number
  is_active: boolean
}

interface Vehicle {
  id: string
  plate: string
  type: string
  total_seats: number
  is_active: boolean
}

interface Driver {
  id: string
  full_name: string
  phone: string
  status: string
  current_vehicle_id: string | null
}

export const useOperatorStore = defineStore('operator', () => {
  const trips    = ref<Trip[]>([])
  const routes   = ref<Route[]>([])
  const vehicles = ref<Vehicle[]>([])
  const drivers  = ref<Driver[]>([])
  const isLoading = ref(false)

  const todayTrips = computed(() => {
    const today = new Date().toDateString()
    return trips.value.filter(t => new Date(t.depart_at).toDateString() === today)
  })

  const activeRoutes = computed(() => routes.value.filter(r => r.is_active))

  async function fetchTrips(params?: Record<string, unknown>) {
    isLoading.value = true
    const { data } = await operatorApi.getTrips(params)
    if (data) trips.value = data as Trip[]
    isLoading.value = false
  }

  async function fetchRoutes() {
    const { data } = await operatorApi.getRoutes()
    if (data) routes.value = data as Route[]
  }

  async function fetchVehicles() {
    const { data } = await operatorApi.getVehicles()
    if (data) vehicles.value = data as Vehicle[]
  }

  async function fetchDrivers() {
    const { data } = await operatorApi.getDrivers()
    if (data) drivers.value = data as Driver[]
  }

  function addTrip(trip: Trip) {
    trips.value.unshift(trip)
  }

  function removeTrip(id: string) {
    trips.value = trips.value.filter(t => t.id !== id)
  }

  return {
    trips, routes, vehicles, drivers, isLoading,
    todayTrips, activeRoutes,
    fetchTrips, fetchRoutes, fetchVehicles, fetchDrivers,
    addTrip, removeTrip,
  }
})
