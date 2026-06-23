import { createRouter, createWebHistory } from 'vue-router';
import { customerRoutes, setupCustomerGuard } from './customer.routes';

const router = createRouter({
    history: createWebHistory(),
    routes: customerRoutes,
    scrollBehavior: () => ({ top: 0 }),
});

setupCustomerGuard(router);

export default router;
