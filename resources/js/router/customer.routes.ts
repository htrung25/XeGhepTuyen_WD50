import type { RouteRecordRaw, Router } from 'vue-router'
import { useCustomerAuthStore } from '@/stores/customer.auth.store'

export const customerRoutes: RouteRecordRaw[] = [
  {
    path: '/login',
    component: () => import('@/pages/customer/auth/Login.vue'),
    meta: { requiresAuth: false, hideNav: true },
  },
  {
    path: '/register',
    component: () => import('@/pages/customer/auth/Register.vue'),
    meta: { requiresAuth: false, hideNav: true },
  },
  {
    path: '/',
    component: () => import('@/layouts/CustomerLayout.vue'),
    children: [
      { path: '',         redirect: '/home' },
      { path: 'home',     component: () => import('@/pages/customer/Home.vue'),           meta: { requiresAuth: false } },
      { path: 'partner',  component: () => import('@/pages/customer/partner/Register.vue'), meta: { requiresAuth: false } },
      { path: 'search',   component: () => import('@/pages/customer/trips/Results.vue'),  meta: { requiresAuth: false } },
      {
        path: 'trips/:id/seats',
        component: () => import('@/pages/customer/trips/SeatPicker.vue'),
        meta: { requiresAuth: false },
      },
      {
        path: 'booking/checkout',
        component: () => import('@/pages/customer/booking/Checkout.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'booking/payment',
        component: () => import('@/pages/customer/booking/Payment.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'booking/:id/confirmation',
        component: () => import('@/pages/customer/booking/Confirmation.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'bookings',
        component: () => import('@/pages/customer/booking/History.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'bookings/:id/track',
        component: () => import('@/pages/customer/tracking/LiveMap.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'bookings/:id/review',
        component: () => import('@/pages/customer/Review.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'profile',
        component: () => import('@/pages/customer/Profile.vue'),
        meta: { requiresAuth: true },
      },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/home' },
]

export function setupCustomerGuard(router: Router) {
  router.beforeEach((to) => {
    const auth = useCustomerAuthStore()
    if (to.meta.requiresAuth && !auth.isAuthenticated) {
      return { path: '/login', query: { redirect: to.fullPath } }
    }
  })
}
