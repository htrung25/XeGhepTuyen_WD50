import { createRouter, createWebHistory } from 'vue-router'
import { operatorRoutes, setupOperatorGuard } from './operator.routes'

const router = createRouter({
  history: createWebHistory(),
  routes: operatorRoutes,
  scrollBehavior: () => ({ top: 0 }),
})

setupOperatorGuard(router)

export default router
