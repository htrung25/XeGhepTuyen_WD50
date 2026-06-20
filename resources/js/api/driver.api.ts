import { apiClient } from './client'
import { login, me, logout, updateStatus } from '@/actions/App/Http/Controllers/Driver/AuthController'
import {
  index as tripsIndex, show as tripShow, passengers, start, complete, history,
} from '@/actions/App/Http/Controllers/Driver/TripController'
import { checkin, absent } from '@/actions/App/Http/Controllers/Driver/CheckinController'
import { update as locationUpdate } from '@/actions/App/Http/Controllers/Driver/LocationController'
import { index as earningsIndex, transactions as earningsTransactions } from '@/actions/App/Http/Controllers/Driver/EarningController'

export const driverApi = {
  // ─── Auth ─────────────────────────────────────────────────────────────
  login:  (data: { phone: string; password: string }) => apiClient.send(login(), data),
  logout: () => apiClient.send(logout()),
  me:     () => apiClient.send(me()),

  // TODO: route BE `/driver/auth/profile` chưa tồn tại — giữ tạm (xem chú thích migration Wayfinder).
  updateProfile: (data: { full_name?: string; email?: string }) =>
    apiClient.put('/driver/auth/profile', data),
  // TODO: route BE `/driver/auth/password` chưa tồn tại — giữ tạm.
  changePassword: (data: { old_password: string; new_password: string; new_password_confirmation: string }) =>
    apiClient.put('/driver/auth/password', data),
  // TODO: route BE `/driver/documents` chưa tồn tại — giữ tạm.
  uploadDocument: (type: string, file: File) => {
    const form = new FormData()
    form.append('type', type)
    form.append('file', file)
    return apiClient.post('/driver/documents', form)
  },

  // ─── Status ────────────────────────────────────────────────────────────
  setStatus: (data: { is_online: boolean }) => apiClient.send(updateStatus(), data),

  // ─── Trips ─────────────────────────────────────────────────────────────
  getTrips:       (params?: { date?: string; from_date?: string; to_date?: string; status?: string }) =>
    apiClient.send(tripsIndex({ query: params })),
  getTrip:        (id: string) => apiClient.send(tripShow(id)),
  getPassengers:  (tripId: string) => apiClient.send(passengers(tripId)),
  startTrip:      (tripId: string) => apiClient.send(start(tripId)),
  completeTrip:   (tripId: string) => apiClient.send(complete(tripId)),
  getTripHistory: (params?: { page?: number }) => apiClient.send(history({ query: params })),

  // ─── Check-in ──────────────────────────────────────────────────────────
  checkin:    (data: { qr_token: string; cash_collected?: boolean }) => apiClient.send(checkin(), data),
  markAbsent: (data: { trip_id: string; booking_id: string }) => apiClient.send(absent(), data),

  // ─── Location ──────────────────────────────────────────────────────────
  updateLocation: (data: { trip_id: string; lat: number; lng: number; speed?: number; heading?: number; accuracy?: number }) =>
    apiClient.send(locationUpdate(), data),

  // ─── Earnings (bảng kê chỉ-xem) ────────────────────────────────────────
  getEarnings:     (params?: { period?: 'today' | 'week' | 'month' }) =>
    apiClient.send(earningsIndex({ query: params })),
  getTransactions: (params?: { page?: number }) =>
    apiClient.send(earningsTransactions({ query: params })),

  // ─── Notifications ─────────────────────────────────────────────────────
  // TODO: route BE `/driver/notifications` chưa tồn tại — giữ tạm.
  getNotifications: (params?: { page?: number; unread_only?: boolean }) =>
    apiClient.get('/driver/notifications', { params }),
  // TODO: route BE `/driver/notifications/{id}/read` chưa tồn tại — giữ tạm.
  markNotificationRead: (id: string) =>
    apiClient.put(`/driver/notifications/${id}/read`),
}
