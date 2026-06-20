import { apiClient } from './client'
import {
  login, register, logout, me, updateProfile, changePassword,
} from '@/actions/App/Http/Controllers/Customer/AuthController'
// search/show/seats map a controller method bound to BOTH /public and /customer,
// so Wayfinder generates a URL-keyed object (not a callable). Indexing the key
// lets us pick the exact binding — e.g. keep getTripSeats on the /customer route.
import {
  search as tripSearch, show as tripShowMap, seats as tripSeatsMap,
} from '@/actions/App/Http/Controllers/Customer/TripSearchController'
import {
  lockSeats as lockSeatsRoute, index as bookingsIndex, store as bookingStore,
  show as bookingShow, cancel as bookingCancel,
} from '@/actions/App/Http/Controllers/Customer/BookingController'
import { trackByBooking } from '@/actions/App/Http/Controllers/Customer/TrackingController'
import { initiate as paymentInitiate, wallet as walletGet } from '@/actions/App/Http/Controllers/Customer/PaymentController'
import { apply as voucherApply } from '@/actions/App/Http/Controllers/Customer/VoucherController'
import { store as reviewStore } from '@/actions/App/Http/Controllers/Customer/ReviewController'
import { transactions as walletTransactions, topup as walletTopup } from '@/actions/App/Http/Controllers/Customer/WalletController'
import {
  index as notificationsIndex, markRead as notificationMarkRead, markAllRead as notificationMarkAllRead,
} from '@/actions/App/Http/Controllers/Customer/NotificationController'
import { store as partnerAppStore } from '@/actions/App/Http/Controllers/Public/PartnerApplicationController'

export const customerApi = {
  // ─── Auth ─────────────────────────────────────────────────────────────
  login:    (data: { phone: string; password: string }) => apiClient.send(login(), data),
  register: (data: { full_name: string; phone: string; email?: string; password: string; password_confirmation: string }) =>
    apiClient.send(register(), data),
  logout:   () => apiClient.send(logout()),
  me:       () => apiClient.send(me()),
  updateProfile: (data: { full_name?: string; email?: string }) => apiClient.send(updateProfile(), data),
  changePassword: (data: { old_password: string; new_password: string; new_password_confirmation: string }) =>
    apiClient.send(changePassword(), data),

  // ─── Public trip search ────────────────────────────────────────────────
  searchTrips: (params: { from_city: string; to_city: string; date: string; passengers?: number }) =>
    apiClient.send(tripSearch['/api/public/trips']({ query: params })),
  getPublicTrip: (id: string) =>
    apiClient.send(tripShowMap['/api/public/trips/{id}'](id)),
  // Điểm đón/trả lấy từ chi tiết chuyến (getPublicTrip → pickup_stops/dropoff_stops).

  // ─── Seat map ──────────────────────────────────────────────────────────
  getTripSeats: (tripId: string) =>
    apiClient.send(tripSeatsMap['/api/customer/trips/{id}/seats'](tripId)),
  lockSeats: (data: { trip_id: string; seat_ids: string[] }) =>
    apiClient.send(lockSeatsRoute(), data),

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
  }) => apiClient.send(bookingStore(), data),
  getBookings:   (params?: { status?: string; page?: number }) =>
    apiClient.send(bookingsIndex({ query: params })),
  getBooking:    (id: string) => apiClient.send(bookingShow(id)),
  cancelBooking: (id: string, reason?: string) =>
    apiClient.send(bookingCancel(id), { reason }),
  trackBooking:  (id: string) => apiClient.send(trackByBooking(id)),

  // ─── Payment ───────────────────────────────────────────────────────────
  initiatePayment: (data: { booking_id: string; method: string }) =>
    apiClient.send(paymentInitiate(), data),
  applyVoucher: (data: { code: string; trip_id: string; amount: number }) =>
    apiClient.send(voucherApply(), data),

  // ─── Reviews ───────────────────────────────────────────────────────────
  submitReview: (data: {
    booking_id: string
    driver_rating: number
    vehicle_rating: number
    service_rating: number
    comment?: string
    tags?: string[]
  }) => apiClient.send(reviewStore(), data),

  // ─── Wallet ────────────────────────────────────────────────────────────
  getWallet:         () => apiClient.send(walletGet()),
  getWalletHistory:  (params?: { page?: number }) =>
    apiClient.send(walletTransactions({ query: params })),
  topUpWallet: (data: { amount: number; method: string }) =>
    apiClient.send(walletTopup(), data),

  // ─── Đăng ký đối tác nhà xe (public) ───────────────────────────────────
  submitPartnerApplication: (formData: FormData) =>
    apiClient.sendForm(partnerAppStore(), formData),

  // ─── Notifications ─────────────────────────────────────────────────────
  getNotifications:  (params?: { page?: number; unread_only?: boolean }) =>
    apiClient.send(notificationsIndex({ query: params })),
  markNotificationRead: (id: string) =>
    apiClient.send(notificationMarkRead(id)),
  markAllRead: () =>
    apiClient.send(notificationMarkAllRead()),
}
