import axios from 'axios'

const http = axios.create({
  baseURL: '/api',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

// Detect active portal from URL prefix
function currentPortal(): 'admin' | 'operator' | 'driver' | 'customer' {
  const p = window.location.pathname
  if (p.startsWith('/admin'))    return 'admin'
  if (p.startsWith('/operator')) return 'operator'
  if (p.startsWith('/driver'))   return 'driver'
  return 'customer'
}

// Attach token — pick the key matching the active portal
http.interceptors.request.use(config => {
  const portal = currentPortal()
  const token  = localStorage.getItem(`${portal}_token`)
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Global 401 handler — redirect to the right portal login
http.interceptors.response.use(
  res => res,
  err => {
    if (err.response?.status === 401) {
      const portal = currentPortal()
      localStorage.removeItem(`${portal}_token`)
      window.location.href = `/${portal}/login`
    }
    return Promise.reject(err)
  },
)

export const apiClient = {
  async get<T>(url: string, config = {}) {
    try {
      const res = await http.get<{ success: boolean; data: T }>(url, config)
      return { data: res.data.data, error: null }
    } catch (e: any) {
      return { data: null as T | null, error: e.response?.data?.message ?? 'Có lỗi xảy ra' }
    }
  },
  async post<T>(url: string, data?: unknown) {
    try {
      const res = await http.post<{ success: boolean; data: T; message?: string }>(url, data)
      return { data: res.data.data, message: res.data.message, error: null }
    } catch (e: any) {
      return { data: null as T | null, message: null, error: e.response?.data?.message ?? 'Có lỗi xảy ra' }
    }
  },
  async put<T>(url: string, data?: unknown) {
    try {
      const res = await http.put<{ success: boolean; data: T }>(url, data)
      return { data: res.data.data, error: null }
    } catch (e: any) {
      return { data: null as T | null, error: e.response?.data?.message ?? 'Có lỗi xảy ra' }
    }
  },
  async delete(url: string) {
    try {
      await http.delete(url)
      return { error: null }
    } catch (e: any) {
      return { error: e.response?.data?.message ?? 'Có lỗi xảy ra' }
    }
  },
  async postForm<T>(url: string, formData: FormData) {
    try {
      const res = await http.postForm<{ success: boolean; data: T; message?: string }>(url, formData)
      return { data: res.data.data, message: res.data.message, error: null }
    } catch (e: any) {
      return { data: null as T | null, message: null, error: e.response?.data?.message ?? 'Có lỗi xảy ra' }
    }
  },
  async postBlob(url: string, data?: unknown) {
    try {
      const res = await http.post(url, data, { responseType: 'blob' })
      return { data: res.data, error: null }
    } catch (e: any) {
      return { data: null, error: 'Có lỗi khi xuất file' }
    }
  },
}
