import { createRouter, createWebHistory } from 'vue-router';
import { adminRoutes, setupAdminGuard } from './admin.routes';

const router = createRouter({
    history: createWebHistory(),
    routes: adminRoutes,
    scrollBehavior: () => ({ top: 0 }),
});

setupAdminGuard(router);

export default router;
