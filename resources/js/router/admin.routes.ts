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
                meta: { title: 'Tổng quan' },
            },
            {
                path: 'operators',
                component: () => import('@/pages/admin/Operators/Approve.vue'),
                meta: { title: 'Nhà xe' },
            },
            {
                path: 'drivers',
                component: () => import('@/pages/admin/Drivers/Verify.vue'),
                meta: { title: 'Tài xế' },
            },
            {
                path: 'users',
                component: () => import('@/pages/admin/Users/Index.vue'),
                meta: { title: 'Người dùng' },
            },
            {
                path: 'bookings',
                component: () => import('@/pages/admin/Bookings/Index.vue'),
                meta: { title: 'Đặt vé' },
            },
            {
                path: 'trips',
                component: () => import('@/pages/admin/Trips/Index.vue'),
                meta: { title: 'Chuyến đi' },
            },
            {
                path: 'trips/live',
                component: () => import('@/pages/admin/Trips/LiveMap.vue'),
                meta: { title: 'Chuyến đi trực tiếp' },
            },
            {
                path: 'finance',
                component: () => import('@/pages/admin/Finance/Overview.vue'),
                meta: { title: 'Tài chính' },
            },
            {
                path: 'vouchers',
                component: () => import('@/pages/admin/Vouchers/Index.vue'),
                meta: { title: 'Voucher' },
            },
        ],
    },
];

export function setupAdminGuard(router: Router) {
    router.beforeEach((to) => {
        const auth = useAdminAuthStore();
        if (to.meta.requiresAuth && !auth.isAuthenticated) {
            return { path: '/admin/login', query: { redirect: to.fullPath } };
        }
        if (to.path === '/admin/login' && auth.isAuthenticated) {
            return { path: '/admin/dashboard' };
        }
    });
}
