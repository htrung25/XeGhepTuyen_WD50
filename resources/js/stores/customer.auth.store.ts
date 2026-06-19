import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

interface CustomerUser {
  id: string
  full_name: string
  phone: string
  email: string | null
  avatar_url: string | null
}

export const useCustomerAuthStore = defineStore('customerAuth', () => {
  const token = ref<string | null>(localStorage.getItem('customer_token'))
  const user  = ref<CustomerUser | null>(
    JSON.parse(localStorage.getItem('customer_user') ?? 'null')
  )

  const isAuthenticated = computed(() => !!token.value)

  function setAuth(t: string, u: CustomerUser) {
    token.value = t
    user.value  = u
    localStorage.setItem('customer_token', t)
    localStorage.setItem('customer_user', JSON.stringify(u))
  }

  function logout() {
    token.value = null
    user.value  = null
    localStorage.removeItem('customer_token')
    localStorage.removeItem('customer_user')
  }

  return { token, user, isAuthenticated, setAuth, logout }
})
