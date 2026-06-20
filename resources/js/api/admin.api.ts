import { apiClient } from './client'
import { login, me, logout } from '@/actions/App/Http/Controllers/Admin/AuthController'
import { index as dashboard } from '@/actions/App/Http/Controllers/Admin/DashboardController'
import {
  index as operatorsIndex, show as operatorShow, approve as operatorApprove,
  reject as operatorReject, suspend as operatorSuspend, resetPassword as operatorResetPassword,
} from '@/actions/App/Http/Controllers/Admin/OperatorController'
import {
  index as partnerAppsIndex, approve as partnerAppApprove, reject as partnerAppReject,
} from '@/actions/App/Http/Controllers/Admin/PartnerApplicationController'
import {
  index as driversIndex, show as driverShow, approve as driverApprove,
  reject as driverReject, suspend as driverSuspend, resetPassword as driverResetPassword,
} from '@/actions/App/Http/Controllers/Admin/DriverController'
import { index as usersIndex, ban as userBan } from '@/actions/App/Http/Controllers/Admin/UserController'
import { index as bookingsIndex } from '@/actions/App/Http/Controllers/Admin/BookingController'
import {
  index as tripsIndex, monitor, autoResolve, show as tripShow, cancel as tripCancel,
} from '@/actions/App/Http/Controllers/Admin/TripController'
import {
  summary, transactions, refunds, commissions, payout,
} from '@/actions/App/Http/Controllers/Admin/FinanceController'
import {
  index as vouchersIndex, store as voucherStore, update as voucherUpdate,
  toggle as voucherToggle, destroy as voucherDestroy,
} from '@/actions/App/Http/Controllers/Admin/VoucherController'

import type { QueryParams } from '@/wayfinder'

// Callers pass loose filter records; cast to Wayfinder's QueryParams at the boundary.
type Params = Record<string, unknown>

export const adminApi = {
  // Auth
  login:  (data: { email: string; password: string }) => apiClient.send(login(), data),
  logout: ()                                           => apiClient.send(logout()),
  me:     ()                                           => apiClient.send(me()),

  // Dashboard
  getDashboard:    ()           => apiClient.send(dashboard()),
  // TODO: route BE `/admin/dashboard/map` chưa tồn tại — chưa có action Wayfinder. Giữ tạm.
  getDashboardMap: ()           => apiClient.get('/admin/dashboard/map'),

  // Operators
  getOperators:    (params?: Params) => apiClient.send(operatorsIndex({ query: params as QueryParams })),
  getOperator:     (id: string)      => apiClient.send(operatorShow(id)),
  verifyOperator:  (id: string, data: { commission_rate: number }) => apiClient.send(operatorApprove(id), data),
  rejectOperator:  (id: string, data: { reason: string })          => apiClient.send(operatorReject(id), data),
  suspendOperator: (id: string, data: { reason: string })          => apiClient.send(operatorSuspend(id), data),
  resetOperatorPassword: (id: string) =>
    apiClient.send<{ phone: string; temp_password: string }>(operatorResetPassword(id)),

  // Partner applications
  getPartnerApplications:    (params?: Params) => apiClient.send(partnerAppsIndex({ query: params as QueryParams })),
  approvePartnerApplication: (id: string, data: { commission_rate: number }) => apiClient.send(partnerAppApprove(id), data),
  rejectPartnerApplication:  (id: string, data: { reason: string })          => apiClient.send(partnerAppReject(id), data),

  // Drivers
  getDrivers:    (params?: Params) => apiClient.send(driversIndex({ query: params as QueryParams })),
  getDriver:     (id: string)      => apiClient.send(driverShow(id)),
  verifyDriver:  (id: string) => apiClient.send<{ phone: string; temp_password: string }>(driverApprove(id)),
  resetDriverPassword: (id: string) => apiClient.send<{ phone: string; temp_password: string }>(driverResetPassword(id)),
  rejectDriver:  (id: string, data: { reason: string }) => apiClient.send(driverReject(id), data),
  suspendDriver: (id: string, data: { reason: string }) => apiClient.send(driverSuspend(id), data),

  // Users
  getUsers: (params?: Params) => apiClient.send(usersIndex({ query: params as QueryParams })),
  banUser:  (id: string, data: { reason: string }) => apiClient.send(userBan(id), data),

  // Bookings
  getBookings: (params?: Params) => apiClient.send(bookingsIndex({ query: params as QueryParams })),

  // Trips
  getTrips:        (params?: Params) => apiClient.send(tripsIndex({ query: params as QueryParams })),
  getLiveTrips:    ()                => apiClient.send(monitor()),
  runAutoResolveTrips: ()            => apiClient.send<{ output: string }>(autoResolve()),
  getTrip:         (id: string)      => apiClient.send(tripShow(id)),
  cancelTrip:      (id: string, data: { reason: string }) => apiClient.send(tripCancel(id), data),

  // Finance
  getFinanceOverview:     (params?: Params) => apiClient.send(summary({ query: params as QueryParams })),
  getFinanceTransactions: (params?: Params) => apiClient.send(transactions({ query: params as QueryParams })),
  getFinanceRefunds:      (params?: Params) => apiClient.send(refunds({ query: params as QueryParams })),
  getCommissions:         (params?: Params) => apiClient.send(commissions({ query: params as QueryParams })),
  createPayout:           (data: unknown)   => apiClient.send(payout(), data),

  // Vouchers
  getVouchers:   (params?: Params)            => apiClient.send(vouchersIndex({ query: params as QueryParams })),
  createVoucher: (data: unknown)              => apiClient.send(voucherStore(), data),
  updateVoucher: (id: string, data: unknown)  => apiClient.send(voucherUpdate(id), data),
  deleteVoucher: (id: string)                 => apiClient.send(voucherDestroy(id)),
  toggleVoucher: (id: string)                 => apiClient.send(voucherToggle(id)),
}
