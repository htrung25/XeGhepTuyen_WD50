import { apiClient } from './client'
import { login, me, logout } from '@/actions/App/Http/Controllers/Operator/AuthController'
import { fleet } from '@/actions/App/Http/Controllers/Operator/OnboardingController'
import {
  index as routesIndex, store as routeStore, show as routeShow,
  update as routeUpdate, destroy as routeDestroy,
} from '@/actions/App/Http/Controllers/Operator/RouteController'
import {
  index as vehiclesIndex, store as vehicleStore, show as vehicleShow, update as vehicleUpdate,
} from '@/actions/App/Http/Controllers/Operator/VehicleController'
import {
  index as driversIndex, store as driverStore, show as driverShow,
  assignVehicle as assignVehicleRoute, resetPassword as driverResetPassword,
} from '@/actions/App/Http/Controllers/Operator/DriverController'
import {
  index as tripsIndex, store as tripStore, bulkStore, show as tripShow,
  cancel as tripCancel, complete as tripComplete, manifest, exportManifest,
} from '@/actions/App/Http/Controllers/Operator/TripController'
import { index as bookingsIndex, show as bookingShow } from '@/actions/App/Http/Controllers/Operator/BookingController'
import {
  summary as revenueSummary, daily, byRoute, byDriver, payouts, requestPayout as requestPayoutRoute,
} from '@/actions/App/Http/Controllers/Operator/RevenueController'

import type { QueryParams } from '@/wayfinder'

// Callers pass loose filter records; cast to Wayfinder's QueryParams at the boundary.
type Params = Record<string, unknown>

export const operatorApi = {
  // Auth
  login:  (data: { phone: string; password: string }) => apiClient.send(login(), data),
  logout: ()                                           => apiClient.send(logout()),
  me:     ()                                           => apiClient.send(me()),

  // Onboarding — tiến độ thêm xe so với cơ cấu đã khai lúc đăng ký
  getOnboardingFleet: () => apiClient.send(fleet()),

  // Routes
  getRoutes:    ()                       => apiClient.send(routesIndex()),
  getRoute:     (id: string)             => apiClient.send(routeShow(id)),
  createRoute:  (data: unknown)          => apiClient.send(routeStore(), data),
  updateRoute:  (id: string, d: unknown) => apiClient.send(routeUpdate(id), d),
  deleteRoute:  (id: string)             => apiClient.send(routeDestroy(id)),

  // Vehicles
  getVehicles:  ()                       => apiClient.send(vehiclesIndex()),
  getVehicle:   (id: string)             => apiClient.send(vehicleShow(id)),
  createVehicle:(data: FormData)         => apiClient.sendForm(vehicleStore(), data),
  updateVehicle:(id: string, d: unknown) => apiClient.send(vehicleUpdate(id), d),

  // Drivers
  getDrivers:   ()             => apiClient.send(driversIndex()),
  getDriver:    (id: string)   => apiClient.send(driverShow(id)),
  createDriver: (data: FormData) => apiClient.sendForm<{ phone: string }>(driverStore(), data),
  resetDriverPassword: (id: string) =>
    apiClient.send<{ phone: string; temp_password: string }>(driverResetPassword(id)),
  assignVehicle:(driverId: string, vehicleId: string) =>
    apiClient.send(assignVehicleRoute(driverId), { vehicle_id: vehicleId }),

  // Trips
  getTrips:     (params?: Params) => apiClient.send(tripsIndex({ query: params as QueryParams })),
  getTrip:      (id: string)      => apiClient.send(tripShow(id)),
  createTrip:   (data: unknown)   => apiClient.send(tripStore(), data),
  bulkCreateTrips: (trips: unknown[]) => apiClient.send(bulkStore(), { trips }),
  cancelTrip:   (id: string, reason: string) => apiClient.send(tripCancel(id), { reason }),
  completeTrip: (id: string)      => apiClient.send(tripComplete(id)),
  getTripManifest: (id: string)   => apiClient.send(manifest(id)),
  exportManifestExcel: (id: string) => apiClient.send(exportManifest(id), undefined, { blob: true }),

  // Bookings
  getBookings:  (params?: Params) => apiClient.send(bookingsIndex({ query: params as QueryParams })),
  getBooking:   (id: string)      => apiClient.send(bookingShow(id)),

  // Revenue
  getRevenueSummary: (params?: Params) => apiClient.send(revenueSummary({ query: params as QueryParams })),
  getRevenueDaily:   (params?: Params) => apiClient.send(daily({ query: params as QueryParams })),
  getRevenueByRoute: (params?: Params) => apiClient.send(byRoute({ query: params as QueryParams })),
  getRevenueByDriver:(params?: Params) => apiClient.send(byDriver({ query: params as QueryParams })),
  getPayouts:        ()                => apiClient.send(payouts()),
  requestPayout:     ()                => apiClient.send(requestPayoutRoute()),
}
