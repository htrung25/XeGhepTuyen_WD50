import { createPinia } from 'pinia';
import { createApp } from 'vue';
import OperatorApp from '@/OperatorApp.vue';
import operatorRouter from '@/router/operator.router';

const app = createApp(OperatorApp);
app.use(createPinia());
app.use(operatorRouter);
app.mount('#app');
