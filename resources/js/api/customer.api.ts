import { apiClient } from './client'

export const customerApi = {
  // ─── Auth ─────────────────────────────────────────────────────────────
  login:    (data: { phone: string; password: string }) =>
    apiClient.post('/customer/auth/login', data),
  register: (data: { full_name: string; phone: string; email?: string; password: string; password_confirmation: string }) =>
    apiClient.post('/customer/auth/register', data),
  logout:   () => apiClient.post('/customer/auth/logout'),
  me:       () => apiClient.get('/customer/auth/me'),
  updateProfile: (data: { full_name?: string; email?: string }) =>
    apiClient.put('/customer/auth/profile', data),
  changePassword: (data: { old_password: string; new_password: string; new_password_confirmation: string }) =>
    apiClient.post('/customer/auth/change-password', data),

  // ─── Public trip search ────────────────────────────────────────────────
  searchTrips: (params: { from_city: string; to_city: string; date: string; passengers?: number }) =>
    apiClient.get('/public/trips', { params }),
  getPublicTrip: (id: string) =>
    apiClient.get(`/public/trips/${id}`),
  // Điểm đón/trả lấy từ chi tiết chuyến (getPublicTrip → pickup_stops/dropoff_stops).
  // Không có endpoint /trips/{id}/stops riêng.

  // ─── Seat map ──────────────────────────────────────────────────────────
  getTripSeats: (tripId: string) =>
    apiClient.get(`/customer/trips/${tripId}/seats`),
  lockSeats: (data: { trip_id: string; seat_ids: string[] }) =>
    apiClient.post('/customer/bookings/lock-seats', data),

  // ─── Bookings ──────────────────────────────────────────────────────────
  createBooking: (data: {
    trip_id: string
    seat_ids: string[]
    pickup_stop_id: string
    dropoff_stop_id: string
    pickup_address?: string
    dropoff_address?: string
    note?: string
    voucher_code?: string
    passenger_count: number
    contact_name: string
    contact_phone: string
    payment_method: 'momo' | 'vnpay' | 'zalopay' | 'wallet' | 'cash'
    passengers: { full_name: string; phone?: string }[]
  }) => apiClient.post('/customer/bookings', data),
  getBookings:   (params?: { status?: string; page?: number }) =>
    apiClient.get('/customer/bookings', { params }),
  getBooking:    (id: string) => apiClient.get(`/customer/bookings/${id}`),
  cancelBooking: (id: string, reason?: string) =>
    apiClient.post(`/customer/bookings/${id}/cancel`, { reason }),
  trackBooking:  (id: string) => apiClient.get(`/customer/bookings/${id}/track`),

  // ─── Payment ───────────────────────────────────────────────────────────
  initiatePayment: (data: { booking_id: string; method: string }) =>
    apiClient.post('/customer/payments/initiate', data),
  applyVoucher: (data: { code: string; trip_id: string; amount: number }) =>
    apiClient.post('/customer/vouchers/apply', data),

  // ─── Reviews ───────────────────────────────────────────────────────────
  submitReview: (data: {
    booking_id: string
    driver_rating: number
    vehicle_rating: number
    service_rating: number
    comment?: string
    tags?: string[]
  }) => apiClient.post('/customer/reviews', data),

  // ─── Wallet ────────────────────────────────────────────────────────────
  getWallet:         () => apiClient.get('/customer/wallet'),
  getWalletHistory:  (params?: { page?: number }) =>
    apiClient.get('/customer/wallet/transactions', { params }),
  topUpWallet: (data: { amount: number; method: string }) =>
    apiClient.post('/customer/wallet/topup', data),

  // ─── Đăng ký đối tác nhà xe (public) ───────────────────────────────────
  submitPartnerApplication: (formData: FormData) =>
    apiClient.postForm('/public/partner-applications', formData),

  // ─── Notifications ─────────────────────────────────────────────────────
  getNotifications:  (params?: { page?: number; unread_only?: boolean }) =>
    apiClient.get('/customer/notifications', { params }),
  markNotificationRead: (id: string) =>
    apiClient.put(`/customer/notifications/${id}/read`),
  markAllRead: () =>
    apiClient.put('/customer/notifications/read-all'),
}
