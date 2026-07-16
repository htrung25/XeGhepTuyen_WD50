import '../css/app.css';
import { SpeedInsights } from '@vercel/speed-insights/vue';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import OperatorApp from '@/OperatorApp.vue';
import operatorRouter from '@/router/operator.router';

const app = createApp(OperatorApp);
app.component('SpeedInsights', SpeedInsights);
app.use(createPinia());
app.use(operatorRouter);
app.mount('#app');
