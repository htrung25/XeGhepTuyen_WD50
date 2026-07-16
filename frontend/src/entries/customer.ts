import '../css/app.css';
import { SpeedInsights } from '@vercel/speed-insights/vue';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import CustomerApp from '@/CustomerApp.vue';
import customerRouter from '@/router/customer.router';

const app = createApp(CustomerApp);
app.component('SpeedInsights', SpeedInsights);
app.use(createPinia()).use(customerRouter).mount('#app');
