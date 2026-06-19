import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

interface DriverUser {
  id: string
  full_name: string
  phone: string
  email: string | null
  avatar_url: string | null
  rating_avg: number
  total_trips: number
  is_verified: boolean
}

interface DriverInfo {
  id: string
  status: string
  license_number: string
  license_expiry: string
  operator: { company_name: string }
}

export const useDriverAuthStore = defineStore('driverAuth', () => {
  const token  = ref<string | null>(localStorage.getItem('driver_token'))
  const user   = ref<DriverUser | null>(JSON.parse(localStorage.getItem('driver_user') ?? 'null'))
  const driver = ref<DriverInfo | null>(JSON.parse(localStorage.getItem('driver_info') ?? 'null'))
  const isOnline = ref<boolean>(localStorage.getItem('driver_online') === 'true')

  const isAuthenticated = computed(() => !!token.value)

  function setAuth(t: string, u: DriverUser, d: DriverInfo) {
    token.value  = t
    user.value   = u
    driver.value = d
    localStorage.setItem('driver_token', t)
    localStorage.setItem('driver_user', JSON.stringify(u))
    localStorage.setItem('driver_info', JSON.stringify(d))
  }

  function setOnline(v: boolean) {
    isOnline.value = v
    localStorage.setItem('driver_online', String(v))
  }

  function logout() {
    token.value  = null
    user.value   = null
    driver.value = null
    isOnline.value = false
    localStorage.removeItem('driver_token')
    localStorage.removeItem('driver_user')
    localStorage.removeItem('driver_info')
    localStorage.removeItem('driver_online')
  }

  return { token, user, driver, isOnline, isAuthenticated, setAuth, setOnline, logout }
})
