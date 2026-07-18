import '../css/app.css';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import { validateStoredSession } from '@/api/client';
import { driverApi } from '@/api/driver.api';
import DriverApp from '@/DriverApp.vue';
import driverRouter from '@/router/driver.router';

const app = createApp(DriverApp);
app.use(createPinia()).use(driverRouter).mount('#app');

// Token cũ trong localStorage phải được server xác nhận, không tin mù quáng.
void validateStoredSession('driver', driverApi.me);
