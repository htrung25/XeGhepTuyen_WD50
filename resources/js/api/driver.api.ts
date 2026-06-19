import { apiClient } from './client'

export const driverApi = {
  // ─── Auth ─────────────────────────────────────────────────────────────
  login:  (data: { phone: string; password: string }) =>
    apiClient.post('/driver/auth/login', data),
  logout: () => apiClient.post('/driver/auth/logout'),
  me:     () => apiClient.get('/driver/auth/me'),
  updateProfile: (data: { full_name?: string; email?: string }) =>
    apiClient.put('/driver/auth/profile', data),
  changePassword: (data: { old_password: string; new_password: string; new_password_confirmation: string }) =>
    apiClient.put('/driver/auth/password', data),
  uploadDocument: (type: string, file: File) => {
    const form = new FormData()
    form.append('type', type)
    form.append('file', file)
    return apiClient.post('/driver/documents', form)
  },

  // ─── Status ────────────────────────────────────────────────────────────
  setStatus: (data: { is_online: boolean }) =>
    apiClient.put('/driver/auth/status', data),

  // ─── Trips ─────────────────────────────────────────────────────────────
  getTrips:       (params?: { date?: string; from_date?: string; to_date?: string; status?: string }) =>
    apiClient.get('/driver/trips', { params }),
  getTrip:        (id: string) => apiClient.get(`/driver/trips/${id}`),
  getPassengers:  (tripId: string) =>
    apiClient.get(`/driver/trips/${tripId}/passengers`),
  startTrip:      (tripId: string) =>
    apiClient.post(`/driver/trips/${tripId}/start`),
  completeTrip:   (tripId: string) =>
    apiClient.post(`/driver/trips/${tripId}/complete`),
  getTripHistory: (params?: { page?: number }) =>
    apiClient.get('/driver/trips/history', { params }),

  // ─── Check-in ──────────────────────────────────────────────────────────
  checkin:    (data: { qr_token: string; cash_collected?: boolean }) =>
    apiClient.post('/driver/checkin', data),
  markAbsent: (data: { trip_id: string; booking_id: string }) =>
    apiClient.post('/driver/checkin/absent', data),

  // ─── Location ──────────────────────────────────────────────────────────
  updateLocation: (data: { trip_id: string; lat: number; lng: number; speed?: number; heading?: number; accuracy?: number }) =>
    apiClient.post('/driver/location', data),

  // ─── Earnings (bảng kê chỉ-xem) ────────────────────────────────────────
  getEarnings:     (params?: { period?: 'today' | 'week' | 'month' }) =>
    apiClient.get('/driver/earnings', { params }),
  getTransactions: (params?: { page?: number }) =>
    apiClient.get('/driver/earnings/transactions', { params }),

  // ─── Notifications ─────────────────────────────────────────────────────
  getNotifications: (params?: { page?: number; unread_only?: boolean }) =>
    apiClient.get('/driver/notifications', { params }),
  markNotificationRead: (id: string) =>
    apiClient.put(`/driver/notifications/${id}/read`),
}
