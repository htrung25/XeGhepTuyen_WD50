import type { RouteRecordRaw, Router } from 'vue-router';
import { useAdminAuthStore } from '@/stores/admin.auth.store';

export const adminRoutes: RouteRecordRaw[] = [
    {
        path: '/admin/login',
        component: () => import('@/pages/admin/auth/Login.vue'),
        meta: { requiresAuth: false },
    },
    {
        path: '/admin',
        component: () => import('@/layouts/AdminLayout.vue'),
        meta: { requiresAuth: true, guard: 'admin' },
        children: [
            { path: '', redirect: '/admin/dashboard' },
            {
                path: 'dashboard',
                component: () => import('@/pages/admin/Dashboard.vue'),
                meta: { title: 'Tổng quan', permission: 'dashboard.view' },
            },
            {
                path: 'operators',
                component: () => import('@/pages/admin/Operators/Approve.vue'),
                meta: { title: 'Nhà xe', permission: 'operators.view' },
            },
            {
                path: 'drivers',
                component: () => import('@/pages/admin/Drivers/Verify.vue'),
                meta: { title: 'Tài xế', permission: 'drivers.view' },
            },
            {
                path: 'users',
                component: () => import('@/pages/admin/Users/Index.vue'),
                meta: { title: 'Người dùng', permission: 'users.view' },
            },
            {
                path: 'bookings',
                component: () => import('@/pages/admin/Bookings/Index.vue'),
                meta: { title: 'Đặt vé', permission: 'bookings.view' },
            },
            {
                path: 'trips',
                component: () => import('@/pages/admin/Trips/Index.vue'),
                meta: { title: 'Chuyến đi', permission: 'trips.view' },
            },
            {
                path: 'trips/live',
                component: () => import('@/pages/admin/Trips/LiveMap.vue'),
                meta: { title: 'Chuyến đi trực tiếp', permission: 'trips.view' },
            },
            {
                path: 'finance',
                component: () => import('@/pages/admin/Finance/Overview.vue'),
                meta: { title: 'Tài chính', permission: 'finance.view' },
            },
            {
                path: 'vouchers',
                component: () => import('@/pages/admin/Vouchers/Index.vue'),
                meta: { title: 'Voucher', permission: 'vouchers.view' },
            },
            {
                path: 'audit-logs',
                component: () => import('@/pages/admin/AuditLogs/Index.vue'),
                meta: { title: 'Nhật ký hệ thống', permission: 'audit_logs.view' },
            },
            {
                path: 'roles',
                component: () => import('@/pages/admin/Roles/Index.vue'),
                meta: { title: 'Phân quyền', permission: 'admin_roles.view' },
            },
            {
                path: 'staff',
                component: () => import('@/pages/admin/Staff/Index.vue'),
                meta: { title: 'Nhân viên', permission: 'admin_staff.view' },
            },
            {
                path: 'profile',
                component: () => import('@/pages/admin/Profile.vue'),
                meta: { title: 'Thông tin cá nhân' },
            },
        ],
    },
];

// Thứ tự ưu tiên khi cần chuyển hướng tới trang đầu tiên mà admin có quyền.
const accessOrder: { path: string; permission: string }[] = [
    { path: '/admin/dashboard', permission: 'dashboard.view' },
    { path: '/admin/operators', permission: 'operators.view' },
    { path: '/admin/drivers', permission: 'drivers.view' },
    { path: '/admin/users', permission: 'users.view' },
    { path: '/admin/bookings', permission: 'bookings.view' },
    { path: '/admin/trips', permission: 'trips.view' },
    { path: '/admin/finance', permission: 'finance.view' },
    { path: '/admin/vouchers', permission: 'vouchers.view' },
    { path: '/admin/audit-logs', permission: 'audit_logs.view' },
    { path: '/admin/roles', permission: 'admin_roles.view' },
    { path: '/admin/staff', permission: 'admin_staff.view' },
];

function firstAllowedPath(can: (key: string) => boolean): string {
    return accessOrder.find((r) => can(r.permission))?.path ?? '/admin/profile';
}

export function setupAdminGuard(router: Router) {
    router.beforeEach((to) => {
        const auth = useAdminAuthStore();

        if (to.meta.requiresAuth && !auth.isAuthenticated) {
            return { path: '/admin/login', query: { redirect: to.fullPath } };
        }
        if (to.path === '/admin/login' && auth.isAuthenticated) {
            return { path: firstAllowedPath(auth.can) };
        }

        // Chặn route khi thiếu quyền → chuyển tới trang đầu tiên được phép.
        const permission = to.meta.permission as string | undefined;
        if (auth.isAuthenticated && permission && !auth.can(permission)) {
            return { path: firstAllowedPath(auth.can) };
        }
    });
}
