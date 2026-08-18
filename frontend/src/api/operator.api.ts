import {
    login,
    me,
    logout,
    updateProfile,
    changePassword,
} from '@/actions/App/Http/Controllers/Operator/AuthController';
import {
    index as bookingsIndex,
    show as bookingShow,
} from '@/actions/App/Http/Controllers/Operator/BookingController';
import {
    index as driversIndex,
    store as driverStore,
    show as driverShow,
    assignVehicle as assignVehicleRoute,
    resetPassword as driverResetPassword,
} from '@/actions/App/Http/Controllers/Operator/DriverController';
import {
    index as fareRatesIndex,
    save as fareRatesSave,
} from '@/actions/App/Http/Controllers/Operator/FareRateController';
import { fleet } from '@/actions/App/Http/Controllers/Operator/OnboardingController';
import {
    summary as revenueSummary,
    daily,
    byRoute,
    byDriver,
    payouts,
    requestPayout as requestPayoutRoute,
} from '@/actions/App/Http/Controllers/Operator/RevenueController';
import {
    index as routesIndex,
    store as routeStore,
    show as routeShow,
    update as routeUpdate,
    destroy as routeDestroy,
} from '@/actions/App/Http/Controllers/Operator/RouteController';
import {
    index as tripsIndex,
    store as tripStore,
    bulkStore,
    show as tripShow,
    cancel as tripCancel,
    complete as tripComplete,
    manifest,
    exportManifest,
} from '@/actions/App/Http/Controllers/Operator/TripController';
import {
    index as vehiclesIndex,
    store as vehicleStore,
    show as vehicleShow,
    update as vehicleUpdate,
} from '@/actions/App/Http/Controllers/Operator/VehicleController';

import type { QueryParams } from '@/wayfinder';
import { apiClient } from './client';

// Callers pass loose filter records; cast to Wayfinder's QueryParams at the boundary.
type Params = Record<string, unknown>;

/**
 * Tuyến gửi lên bằng MÃ tỉnh/huyện (BE đổi ra tên và tự tính giá vé theo bảng
 * giá km) — không còn base_price nhập tay, không còn điểm dừng.
 */
export interface OperatorRoutePayload {
    name: string;
    origin_province_code: string;
    origin_district_code: string;
    dest_province_code: string;
    dest_district_code: string;
    distance_km: number;
    est_duration_min: number;
    is_round_trip: boolean;
    is_active: boolean;
}

/** Đơn giá/km gán cho MỘT tuyến (bảng giá theo tuyến) */
export interface OperatorFareRatePayload {
    route_id: string;
    base_fare: number;
    price_per_km: number;
}

export const operatorApi = {
    // Auth
    login: (data: { phone: string; password: string }) =>
        apiClient.send(login(), data),
    logout: () => apiClient.send(logout()),
    me: () => apiClient.send(me()),
    updateProfile: (data: FormData) =>
        apiClient.sendForm(updateProfile(), data),
    changePassword: (data: unknown) => apiClient.send(changePassword(), data),

    // Onboarding — tiến độ thêm xe so với cơ cấu đã khai lúc đăng ký
    getOnboardingFleet: () => apiClient.send(fleet()),

    // Routes
    getRoutes: (params?: Params) =>
        apiClient.send(routesIndex({ query: params as QueryParams })),
    getRoute: (id: string) => apiClient.send(routeShow(id)),
    createRoute: (data: OperatorRoutePayload) =>
        apiClient.send(routeStore(), data),
    updateRoute: (id: string, data: Partial<OperatorRoutePayload>) =>
        apiClient.send(routeUpdate(id), data),
    deleteRoute: (id: string) => apiClient.send(routeDestroy(id)),

    // Bảng giá vé theo km (phân theo tỉnh/huyện điểm đi)
    getFareRates: () => apiClient.send(fareRatesIndex()),
    saveFareRates: (rates: OperatorFareRatePayload[]) =>
        apiClient.send(fareRatesSave(), { rates }),

    // Vehicles
    getVehicles: (params?: Params) =>
        apiClient.send(vehiclesIndex({ query: params as QueryParams })),
    getVehicle: (id: string) => apiClient.send(vehicleShow(id)),
    createVehicle: (data: FormData) => apiClient.sendForm(vehicleStore(), data),
    updateVehicle: (id: string, d: unknown) =>
        apiClient.send(vehicleUpdate(id), d),

    // Drivers
    getDrivers: () => apiClient.send(driversIndex()),
    getDriver: (id: string) => apiClient.send(driverShow(id)),
    createDriver: (data: FormData) =>
        apiClient.sendForm<{ phone: string }>(driverStore(), data),
    resetDriverPassword: (id: string) =>
        apiClient.send<{ phone: string; temp_password: string }>(
            driverResetPassword(id),
        ),
    assignVehicle: (driverId: string, vehicleId: string) =>
        apiClient.send(assignVehicleRoute(driverId), { vehicle_id: vehicleId }),

    // Trips
    getTrips: (params?: Params) =>
        apiClient.send(tripsIndex({ query: params as QueryParams })),
    getTrip: (id: string) => apiClient.send(tripShow(id)),
    createTrip: (data: unknown) => apiClient.send(tripStore(), data),
    bulkCreateTrips: (trips: unknown[]) =>
        apiClient.send(bulkStore(), { trips }),
    cancelTrip: (id: string, reason: string) =>
        apiClient.send(tripCancel(id), { reason }),
    completeTrip: (id: string) => apiClient.send(tripComplete(id)),
    reassignTripDriver: (id: string, driverId: string) =>
        apiClient.post(`/operator/trips/${id}/reassign-driver`, {
            driver_id: driverId,
        }),
    getTripManifest: (id: string) => apiClient.send(manifest(id)),
    exportManifestExcel: (id: string) =>
        apiClient.send(exportManifest(id), undefined, { blob: true }),

    // Bookings
    getBookings: (params?: Params) =>
        apiClient.send(bookingsIndex({ query: params as QueryParams })),
    getBooking: (id: string) => apiClient.send(bookingShow(id)),

    // Revenue
    getRevenueSummary: (params?: Params) =>
        apiClient.send(revenueSummary({ query: params as QueryParams })),
    getRevenueDaily: (params?: Params) =>
        apiClient.send(daily({ query: params as QueryParams })),
    getRevenueTransactions: (params?: Params) =>
        apiClient.get('/operator/revenue/transactions', { params }),
    getRevenueByRoute: (params?: Params) =>
        apiClient.send(byRoute({ query: params as QueryParams })),
    getRevenueByDriver: (params?: Params) =>
        apiClient.send(byDriver({ query: params as QueryParams })),
    getPayouts: () => apiClient.send(payouts()),
    requestPayout: () => apiClient.send(requestPayoutRoute()),

    // Notifications + badge công việc cần xử lý
    getNotifications: () => apiClient.get('/operator/notifications'),
    getPendingCounts: () => apiClient.get('/operator/pending-counts'),
    markNotificationRead: (id: string) =>
        apiClient.put(`/operator/notifications/${id}/read`),
    markAllNotificationsRead: () =>
        apiClient.put('/operator/notifications/read-all'),

    // Lịch sử vận hành của nhà xe
    getHistory: (params?: Params) =>
        apiClient.get('/operator/history', { params }),
    getDashboardMap: () => apiClient.get('/operator/dashboard/map'),
};
