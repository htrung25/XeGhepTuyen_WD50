import {
    index as adminStaffIndex,
    store as adminStaffStore,
    show as adminStaffShow,
    update as adminStaffUpdate,
    ban as adminStaffBan,
    unban as adminStaffUnban,
    resetPassword as adminStaffResetPassword,
} from '@/actions/App/Http/Controllers/Admin/AdminStaffController';
import {
    index as auditLogsIndex,
    show as auditLogShow,
} from '@/actions/App/Http/Controllers/Admin/AuditLogController';
import {
    login,
    me,
    logout,
    updateProfile as adminUpdateProfile,
    changePassword as adminChangePassword,
} from '@/actions/App/Http/Controllers/Admin/AuthController';
import { index as bookingsIndex } from '@/actions/App/Http/Controllers/Admin/BookingController';
import {
    index as dashboard,
    map as dashboardMap,
} from '@/actions/App/Http/Controllers/Admin/DashboardController';
import {
    index as driversIndex,
    show as driverShow,
    approve as driverApprove,
    reject as driverReject,
    suspend as driverSuspend,
    resetPassword as driverResetPassword,
} from '@/actions/App/Http/Controllers/Admin/DriverController';
import {
    summary,
    transactions,
    refunds,
    commissions,
    payout,
    refund as financeRefund,
} from '@/actions/App/Http/Controllers/Admin/FinanceController';
import {
    index as operatorsIndex,
    show as operatorShow,
    approve as operatorApprove,
    reject as operatorReject,
    suspend as operatorSuspend,
    restore as operatorRestore,
    resetPassword as operatorResetPassword,
} from '@/actions/App/Http/Controllers/Admin/OperatorController';
import {
    index as partnerAppsIndex,
    approve as partnerAppApprove,
    reject as partnerAppReject,
} from '@/actions/App/Http/Controllers/Admin/PartnerApplicationController';
import {
    permissions as rolesPermissions,
    index as rolesIndex,
    store as roleStore,
    show as roleShow,
    update as roleUpdate,
    destroy as roleDestroy,
} from '@/actions/App/Http/Controllers/Admin/RoleController';
import {
    index as tripsIndex,
    monitor,
    autoResolve,
    show as tripShow,
    cancel as tripCancel,
} from '@/actions/App/Http/Controllers/Admin/TripController';
import {
    index as usersIndex,
    ban as userBan,
    show as userShow,
    unban as userUnban,
} from '@/actions/App/Http/Controllers/Admin/UserController';
import {
    index as vouchersIndex,
    store as voucherStore,
    update as voucherUpdate,
    toggle as voucherToggle,
    destroy as voucherDestroy,
} from '@/actions/App/Http/Controllers/Admin/VoucherController';

import type { QueryParams } from '@/wayfinder';
import { apiClient } from './client';

// Callers pass loose filter records; cast to Wayfinder's QueryParams at the boundary.
type Params = Record<string, unknown>;

export const adminApi = {
    // Auth
    login: (data: { email: string; password: string }) =>
        apiClient.send(login(), data),
    logout: () => apiClient.send(logout()),
    me: () => apiClient.send(me()),
    updateProfile: (data: unknown) => apiClient.send(adminUpdateProfile(), data),
    changePassword: (data: unknown) => apiClient.send(adminChangePassword(), data),

    // Dashboard
    getDashboard: () => apiClient.send(dashboard()),
    getDashboardMap: () => apiClient.send(dashboardMap()),

    // Operators
    getOperators: (params?: Params) =>
        apiClient.send(operatorsIndex({ query: params as QueryParams })),
    getOperator: (id: string) => apiClient.send(operatorShow(id)),
    verifyOperator: (id: string, data: { commission_rate: number }) =>
        apiClient.send(operatorApprove(id), data),
    rejectOperator: (id: string, data: { reason: string }) =>
        apiClient.send(operatorReject(id), data),
    suspendOperator: (id: string, data: { reason: string }) =>
        apiClient.send(operatorSuspend(id), data),
    restoreOperator: (id: string) =>
        apiClient.send(operatorRestore(id)),
    resetOperatorPassword: (id: string) =>
        apiClient.send<{ phone: string }>(operatorResetPassword(id)),

    // Partner applications
    getPartnerApplications: (params?: Params) =>
        apiClient.send(partnerAppsIndex({ query: params as QueryParams })),
    approvePartnerApplication: (
        id: string,
        data: { commission_rate: number },
    ) => apiClient.send(partnerAppApprove(id), data),
    rejectPartnerApplication: (id: string, data: { reason: string }) =>
        apiClient.send(partnerAppReject(id), data),

    // Drivers
    getDrivers: (params?: Params) =>
        apiClient.send(driversIndex({ query: params as QueryParams })),
    getDriver: (id: string) => apiClient.send(driverShow(id)),
    verifyDriver: (id: string) =>
        apiClient.send<{ phone: string }>(driverApprove(id)),
    resetDriverPassword: (id: string) =>
        apiClient.send<{ phone: string }>(driverResetPassword(id)),
    rejectDriver: (id: string, data: { reason: string }) =>
        apiClient.send(driverReject(id), data),
    suspendDriver: (id: string, data: { reason: string }) =>
        apiClient.send(driverSuspend(id), data),

    // Users
    getUsers: (params?: Params) =>
        apiClient.send(usersIndex({ query: params as QueryParams })),
    getUser: (id: string) =>
        apiClient.send(userShow(id)),
    banUser: (id: string, data: { reason: string }) =>
        apiClient.send(userBan(id), data),
    unbanUser: (id: string) =>
        apiClient.send(userUnban(id)),

    // Bookings
    getBookings: (params?: Params) =>
        apiClient.send(bookingsIndex({ query: params as QueryParams })),

    // Trips
    getTrips: (params?: Params) =>
        apiClient.send(tripsIndex({ query: params as QueryParams })),
    getLiveTrips: () => apiClient.send(monitor()),
    runAutoResolveTrips: () =>
        apiClient.send<{ output: string }>(autoResolve()),
    getTrip: (id: string) => apiClient.send(tripShow(id)),
    cancelTrip: (id: string, data: { reason: string }) =>
        apiClient.send(tripCancel(id), data),

    // Finance
    getFinanceOverview: (params?: Params) =>
        apiClient.send(summary({ query: params as QueryParams })),
    getFinanceTransactions: (params?: Params) =>
        apiClient.send(transactions({ query: params as QueryParams })),
    getFinanceRefunds: (params?: Params) =>
        apiClient.send(refunds({ query: params as QueryParams })),
    getCommissions: (params?: Params) =>
        apiClient.send(commissions({ query: params as QueryParams })),
    createPayout: (data: unknown) => apiClient.send(payout(), data),
    refundBooking: (bookingId: string, data: { amount: number; reason: string }) =>
        apiClient.send<{ amount: number }>(financeRefund(bookingId), data),

    // Vouchers
    getVouchers: (params?: Params) =>
        apiClient.send(vouchersIndex({ query: params as QueryParams })),
    createVoucher: (data: unknown) => apiClient.send(voucherStore(), data),
    updateVoucher: (id: string, data: unknown) =>
        apiClient.send(voucherUpdate(id), data),
    deleteVoucher: (id: string) => apiClient.send(voucherDestroy(id)),
    toggleVoucher: (id: string) => apiClient.send(voucherToggle(id)),

    // Audit Logs
    getAuditLogs: (params?: Params) =>
        apiClient.send(auditLogsIndex({ query: params as QueryParams })),
    getAuditLog: (id: string) =>
        apiClient.send(auditLogShow(id)),

    // Roles (phân quyền)
    getPermissionCatalog: () => apiClient.send(rolesPermissions()),
    getRoles: () => apiClient.send(rolesIndex()),
    getRole: (id: string) => apiClient.send(roleShow(id)),
    createRole: (data: unknown) => apiClient.send(roleStore(), data),
    updateRole: (id: string, data: unknown) =>
        apiClient.send(roleUpdate(id), data),
    deleteRole: (id: string) => apiClient.send(roleDestroy(id)),

    // Admin staff (nhân viên admin)
    getAdminStaff: (params?: Params) =>
        apiClient.send(adminStaffIndex({ query: params as QueryParams })),
    getAdminStaffMember: (id: string) => apiClient.send(adminStaffShow(id)),
    createAdminStaff: (data: unknown) =>
        apiClient.send<{ staff: unknown; temp_password: string }>(
            adminStaffStore(),
            data,
        ),
    updateAdminStaff: (id: string, data: unknown) =>
        apiClient.send(adminStaffUpdate(id), data),
    banAdminStaff: (id: string) => apiClient.send(adminStaffBan(id)),
    unbanAdminStaff: (id: string) => apiClient.send(adminStaffUnban(id)),
    resetAdminStaffPassword: (id: string) =>
        apiClient.send<{ temp_password: string }>(
            adminStaffResetPassword(id),
        ),
};
