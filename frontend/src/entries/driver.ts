import '../css/app.css';
import { SpeedInsights } from '@vercel/speed-insights/vue';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import DriverApp from '@/DriverApp.vue';
import driverRouter from '@/router/driver.router';

const app = createApp(DriverApp);
app.component('SpeedInsights', SpeedInsights);
app.use(createPinia()).use(driverRouter).mount('#app');
