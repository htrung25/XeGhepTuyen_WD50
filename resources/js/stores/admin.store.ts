import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adminApi } from '@/api/admin.api'

interface DashboardStats {
  bookings_today: number
  revenue_today: number
  active_trips: number
  new_users_today: number
  pending_operators: number
  pending_drivers: number
}

interface PendingOperator {
  id: string
  company_name: string
  owner_name: string
  status: string
  created_at: string
}

interface PendingDriver {
  id: string
  full_name: string
  phone: string
  operator_name: string
  status: string
  created_at: string
}

export const useAdminStore = defineStore('admin', () => {
  const stats            = ref<DashboardStats | null>(null)
  const pendingOperators = ref<PendingOperator[]>([])
  const pendingDrivers   = ref<PendingDriver[]>([])
  const isLoading        = ref(false)

  async function fetchDashboard() {
    isLoading.value = true
    const { data } = await adminApi.getDashboard()
    if (data) {
      stats.value = data.stats ?? null
    }
    isLoading.value = false
  }

  async function fetchPendingOperators() {
    const { data } = await adminApi.getOperators({ status: 'pending' })
    if (data) pendingOperators.value = data as PendingOperator[]
  }

  async function fetchPendingDrivers() {
    const { data } = await adminApi.getDrivers({ status: 'pending' })
    if (data) pendingDrivers.value = data as PendingDriver[]
  }

  function decrementPendingOperators() {
    if (stats.value) stats.value.pending_operators = Math.max(0, stats.value.pending_operators - 1)
  }

  function decrementPendingDrivers() {
    if (stats.value) stats.value.pending_drivers = Math.max(0, stats.value.pending_drivers - 1)
  }

  return {
    stats, pendingOperators, pendingDrivers, isLoading,
    fetchDashboard, fetchPendingOperators, fetchPendingDrivers,
    decrementPendingOperators, decrementPendingDrivers,
  }
})
