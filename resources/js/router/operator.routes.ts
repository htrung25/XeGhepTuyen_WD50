import type { RouteRecordRaw } from 'vue-router';
import { useOperatorAuthStore } from '@/stores/operator.auth.store';

export const operatorRoutes: RouteRecordRaw[] = [
    {
        path: '/operator/login',
        component: () => import('@/pages/operator/auth/Login.vue'),
        meta: { requiresAuth: false },
    },
    {
        path: '/operator',
        component: () => import('@/layouts/OperatorLayout.vue'),
        meta: { requiresAuth: true, guard: 'operator' },
        children: [
            { path: '', redirect: '/operator/dashboard' },
            {
                path: 'dashboard',
                component: () => import('@/pages/operator/Dashboard.vue'),
                meta: { title: 'Tổng quan' },
            },
            {
                path: 'routes',
                component: () => import('@/pages/operator/Routes/Index.vue'),
                meta: { title: 'Tuyến đường' },
            },
            {
                path: 'vehicles',
                component: () => import('@/pages/operator/Vehicles/Index.vue'),
                meta: { title: 'Xe & Tài xế' },
            },
            {
                path: 'bookings',
                component: () => import('@/pages/operator/Bookings/Index.vue'),
                meta: { title: 'Đặt chỗ' },
            },
            {
                path: 'trips',
                component: () => import('@/pages/operator/Trips/Schedule.vue'),
                meta: { title: 'Lịch chạy' },
            },
            {
                path: 'trips/:id/manifest',
                component: () => import('@/pages/operator/Trips/Manifest.vue'),
                meta: { title: 'Manifest chuyến' },
            },
            {
                path: 'revenue',
                component: () => import('@/pages/operator/Revenue/Report.vue'),
                meta: { title: 'Doanh thu' },
            },
        ],
    },
];

// Navigation guard — attach to router in operator entry
export function setupOperatorGuard(
    router: ReturnType<(typeof import('vue-router'))['createRouter']>,
) {
    router.beforeEach((to) => {
        const auth = useOperatorAuthStore();
        if (to.meta.requiresAuth && !auth.isAuthenticated) {
            return {
                path: '/operator/login',
                query: { redirect: to.fullPath },
            };
        }
        if (to.path === '/operator/login' && auth.isAuthenticated) {
            return { path: '/operator/dashboard' };
        }
    });
}
