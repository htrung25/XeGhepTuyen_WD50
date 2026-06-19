import { apiClient } from './client'

export const adminApi = {
  // Auth
  login:  (data: { email: string; password: string }) => apiClient.post('/admin/auth/login', data),
  logout: ()                                           => apiClient.post('/admin/auth/logout'),
  me:     ()                                           => apiClient.get('/admin/auth/me'),

  // Dashboard
  getDashboard:    ()                                  => apiClient.get('/admin/dashboard'),
  getDashboardMap: ()                                  => apiClient.get('/admin/dashboard/map'),

  // Operators
  getOperators:    (params?: Record<string, unknown>)  => apiClient.get('/admin/operators', { params }),
  getOperator:     (id: string)                        => apiClient.get(`/admin/operators/${id}`),
  verifyOperator:  (id: string, data: { commission_rate: number }) =>
    apiClient.post(`/admin/operators/${id}/approve`, data),
  rejectOperator:  (id: string, data: { reason: string }) =>
    apiClient.post(`/admin/operators/${id}/reject`, data),
  suspendOperator: (id: string, data: { reason: string }) =>
    apiClient.post(`/admin/operators/${id}/suspend`, data),
  resetOperatorPassword: (id: string) =>
    apiClient.post<{ phone: string; temp_password: string }>(`/admin/operators/${id}/reset-password`),

  // Partner applications (đơn đăng ký đối tác chờ duyệt)
  getPartnerApplications:    (params?: Record<string, unknown>) =>
    apiClient.get('/admin/partner-applications', { params }),
  approvePartnerApplication: (id: string, data: { commission_rate: number }) =>
    apiClient.post(`/admin/partner-applications/${id}/approve`, data),
  rejectPartnerApplication:  (id: string, data: { reason: string }) =>
    apiClient.post(`/admin/partner-applications/${id}/reject`, data),

  // Drivers
  getDrivers:      (params?: Record<string, unknown>)  => apiClient.get('/admin/drivers', { params }),
  getDriver:       (id: string)                        => apiClient.get(`/admin/drivers/${id}`),
  verifyDriver:    (id: string) =>
    apiClient.post<{ phone: string; temp_password: string }>(`/admin/drivers/${id}/approve`),
  resetDriverPassword: (id: string) =>
    apiClient.post<{ phone: string; temp_password: string }>(`/admin/drivers/${id}/reset-password`),
  rejectDriver:    (id: string, data: { reason: string }) =>
    apiClient.post(`/admin/drivers/${id}/reject`, data),
  suspendDriver:   (id: string, data: { reason: string }) =>
    apiClient.post(`/admin/drivers/${id}/suspend`, data),

  // Users
  getUsers:        (params?: Record<string, unknown>)  => apiClient.get('/admin/users', { params }),
  banUser:         (id: string, data: { reason: string }) =>
    apiClient.post(`/admin/users/${id}/ban`, data),

  // Bookings
  getBookings:     (params?: Record<string, unknown>)  => apiClient.get('/admin/bookings', { params }),

  // Trips
  getTrips:        (params?: Record<string, unknown>)  => apiClient.get('/admin/trips', { params }),
  getLiveTrips:    ()                                  => apiClient.get('/admin/trips/monitor'),
  runAutoResolveTrips: ()                              => apiClient.post<{ output: string }>('/admin/trips/auto-resolve'),
  getTrip:         (id: string)                        => apiClient.get(`/admin/trips/${id}`),
  cancelTrip:      (id: string, data: { reason: string }) =>
    apiClient.post(`/admin/trips/${id}/cancel`, data),

  // Finance
  getFinanceOverview:   (params?: Record<string, unknown>) => apiClient.get('/admin/finance/summary', { params }),
  getFinanceTransactions: (params?: Record<string, unknown>) => apiClient.get('/admin/finance/transactions', { params }),
  getFinanceRefunds:    (params?: Record<string, unknown>) => apiClient.get('/admin/finance/refunds', { params }),
  getCommissions:       (params?: Record<string, unknown>) => apiClient.get('/admin/finance/commissions', { params }),
  createPayout:         (data: unknown)               => apiClient.post('/admin/finance/payouts', data),

  // Vouchers
  getVouchers:     (params?: Record<string, unknown>)  => apiClient.get('/admin/vouchers', { params }),
  createVoucher:   (data: unknown)                     => apiClient.post('/admin/vouchers', data),
  updateVoucher:   (id: string, data: unknown)         => apiClient.put(`/admin/vouchers/${id}`, data),
  deleteVoucher:   (id: string)                        => apiClient.delete(`/admin/vouchers/${id}`),
  toggleVoucher:   (id: string)                        => apiClient.post(`/admin/vouchers/${id}/toggle`),
}
