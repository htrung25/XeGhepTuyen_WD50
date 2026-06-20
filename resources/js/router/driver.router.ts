import { createRouter, createWebHistory } from 'vue-router'
import { driverRoutes, setupDriverGuard } from './driver.routes'

const router = createRouter({
  history: createWebHistory(),
  routes: driverRoutes,
  scrollBehavior: () => ({ top: 0 }),
})

setupDriverGuard(router)

export default router
