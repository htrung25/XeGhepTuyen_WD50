import '../css/app.css';
import { SpeedInsights } from '@vercel/speed-insights/vue';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import AdminApp from '@/AdminApp.vue';
import adminRouter from '@/router/admin.router';

const app = createApp(AdminApp);
app.component('SpeedInsights', SpeedInsights);
app.use(createPinia());
app.use(adminRouter);
app.mount('#app');
