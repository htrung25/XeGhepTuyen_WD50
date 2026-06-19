import { apiClient } from './client'

export const operatorApi = {
  // Auth
  login:  (data: { phone: string; password: string }) => apiClient.post('/operator/auth/login', data),
  logout: ()                                           => apiClient.post('/operator/auth/logout'),
  me:     ()                                           => apiClient.get('/operator/auth/me'),

  // Onboarding — tiến độ thêm xe so với cơ cấu đã khai lúc đăng ký
  getOnboardingFleet: () => apiClient.get('/operator/onboarding/fleet'),

  // Routes
  getRoutes:    ()                    => apiClient.get('/operator/routes'),
  getRoute:     (id: string)          => apiClient.get(`/operator/routes/${id}`),
  createRoute:  (data: unknown)       => apiClient.post('/operator/routes', data),
  updateRoute:  (id: string, d: unknown) => apiClient.put(`/operator/routes/${id}`, d),
  deleteRoute:  (id: string)          => apiClient.delete(`/operator/routes/${id}`),

  // Vehicles
  getVehicles:  ()              => apiClient.get('/operator/vehicles'),
  getVehicle:   (id: string)   => apiClient.get(`/operator/vehicles/${id}`),
  createVehicle:(data: FormData) => apiClient.postForm('/operator/vehicles', data),
  updateVehicle:(id: string, d: unknown) => apiClient.put(`/operator/vehicles/${id}`, d),

  // Drivers
  getDrivers:   ()              => apiClient.get('/operator/drivers'),
  getDriver:    (id: string)   => apiClient.get(`/operator/drivers/${id}`),
  createDriver: (data: FormData) => apiClient.postForm<{ phone: string }>('/operator/drivers', data),
  resetDriverPassword: (id: string) =>
    apiClient.post<{ phone: string; temp_password: string }>(`/operator/drivers/${id}/reset-password`),
  assignVehicle:(driverId: string, vehicleId: string) =>
    apiClient.put(`/operator/drivers/${driverId}/vehicle`, { vehicle_id: vehicleId }),

  // Trips
  getTrips:     (params?: Record<string, unknown>) => apiClient.get('/operator/trips', { params }),
  getTrip:      (id: string)                       => apiClient.get(`/operator/trips/${id}`),
  createTrip:   (data: unknown)                    => apiClient.post('/operator/trips', data),
  bulkCreateTrips: (trips: unknown[])              => apiClient.post('/operator/trips/bulk', { trips }),
  cancelTrip:   (id: string, reason: string)       => apiClient.post(`/operator/trips/${id}/cancel`, { reason }),
  completeTrip: (id: string)                        => apiClient.post(`/operator/trips/${id}/complete`),
  getTripManifest: (id: string)                    => apiClient.get(`/operator/trips/${id}/manifest`),
  exportManifestExcel: (id: string)                => apiClient.postBlob(`/operator/trips/${id}/manifest/export`),

  // Bookings
  getBookings:  (params?: Record<string, unknown>) => apiClient.get('/operator/bookings', { params }),
  getBooking:   (id: string)                       => apiClient.get(`/operator/bookings/${id}`),

  // Revenue
  getRevenueSummary: (params?: Record<string, unknown>) => apiClient.get('/operator/revenue/summary', { params }),
  getRevenueDaily:   (params?: Record<string, unknown>) => apiClient.get('/operator/revenue/daily', { params }),
  getRevenueByRoute: (params?: Record<string, unknown>) => apiClient.get('/operator/revenue/by-route', { params }),
  getRevenueByDriver:(params?: Record<string, unknown>) => apiClient.get('/operator/revenue/by-driver', { params }),
  getPayouts:        ()                                 => apiClient.get('/operator/revenue/payouts'),
  requestPayout:     ()                                 => apiClient.post('/operator/revenue/payout-request'),
}
