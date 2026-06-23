import type { RouteRecordRaw, Router } from 'vue-router';
import { useDriverAuthStore } from '@/stores/driver.auth.store';

export const driverRoutes: RouteRecordRaw[] = [
    {
        path: '/driver/login',
        component: () => import('@/pages/driver/auth/Login.vue'),
        meta: { requiresAuth: false, hideNav: true },
    },
    {
        path: '/driver',
        component: () => import('@/layouts/DriverLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: '/driver/dashboard' },
            {
                path: 'dashboard',
                component: () => import('@/pages/driver/Dashboard.vue'),
            },
            {
                path: 'schedule',
                component: () => import('@/pages/driver/trips/Schedule.vue'),
            },
            {
                path: 'trips/:id',
                component: () => import('@/pages/driver/trips/TripDetail.vue'),
                meta: { hideNav: true },
            },
            {
                path: 'trips/:id/navigate',
                component: () => import('@/pages/driver/trips/Navigation.vue'),
                meta: { hideNav: true },
            },
            {
                path: 'checkin/:tripId',
                component: () => import('@/pages/driver/checkin/QrScanner.vue'),
                meta: { hideNav: true },
            },
            {
                path: 'earnings',
                component: () => import('@/pages/driver/earnings/Summary.vue'),
            },
            {
                path: 'profile',
                component: () => import('@/pages/driver/Profile.vue'),
            },
        ],
    },
    { path: '/driver/:pathMatch(.*)*', redirect: '/driver/dashboard' },
];

export function setupDriverGuard(router: Router) {
    router.beforeEach((to) => {
        const auth = useDriverAuthStore();
        if (to.meta.requiresAuth && !auth.isAuthenticated) {
            return { path: '/driver/login', query: { redirect: to.fullPath } };
        }
        if (to.path === '/driver/login' && auth.isAuthenticated) {
            return { path: '/driver/dashboard' };
        }
    });
}
