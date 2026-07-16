import type { RouteRecordRaw, Router } from 'vue-router';
import { useDriverAuthStore } from '@/stores/driver.auth.store';

export const driverRoutes: RouteRecordRaw[] = [
    {
        path: '/driver/login',
        component: () => import('@/pages/driver/auth/Login.vue'),
        meta: { requiresAuth: false, hideNav: true },
    },
    {
        // Trang đổi mật khẩu bắt buộc — cần đăng nhập, nhưng không cần đã đổi MK
        path: '/driver/change-password',
        component: () => import('@/pages/driver/auth/ChangePassword.vue'),
        meta: { requiresAuth: true, hideNav: true, skipPasswordCheck: true },
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

        // 1. Chưa đăng nhập → về trang login
        if (to.meta.requiresAuth && !auth.isAuthenticated) {
            return { path: '/driver/login', query: { redirect: to.fullPath } };
        }

        // 2. Đã đăng nhập mà cố vào login → về dashboard
        if (to.path === '/driver/login' && auth.isAuthenticated) {
            return { path: '/driver/dashboard' };
        }

        // 3. Đang dùng mật khẩu tạm thời → bắt buộc về trang đổi mật khẩu
        //    (bỏ qua nếu route đích đã đánh dấu skipPasswordCheck)
        if (
            auth.isAuthenticated &&
            auth.mustChangePassword &&
            !to.meta.skipPasswordCheck
        ) {
            return { path: '/driver/change-password' };
        }

        // 4. Đã đổi mật khẩu mà cố vào /change-password → về dashboard
        if (
            to.path === '/driver/change-password' &&
            auth.isAuthenticated &&
            !auth.mustChangePassword
        ) {
            return { path: '/driver/dashboard' };
        }
    });
}
