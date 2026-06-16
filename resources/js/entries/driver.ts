import { createApp } from 'vue'
import { createPinia } from 'pinia'
import DriverApp from '@/DriverApp.vue'
import driverRouter from '@/router/driver.router'

const app = createApp(DriverApp)
app.use(createPinia()).use(driverRouter).mount('#app')
